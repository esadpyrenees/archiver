<?php

/**
 * Gère la mise en cache des infos des dossiers/fichiers dans SQLite.
 * La validité du cache repose sur le mtime du dossier, pas sur un TTL fixe.
 */
class CacheManager
{
  private const MAX_SCAN_SECONDS = 10.0;
  private static $dbFile;
  private static $db;
  private static $initialized = false;
  private static $metrics = [
    'cache_hits' => 0,
    'cache_stale' => 0,
    'cache_miss' => 0,
    'compute_time_ms' => 0.0,
  ];


  /**
   * Initialise la connexion SQLite et crée le schéma si nécessaire.
   */
  public static function init()
  {
    if (self::$initialized) return;
    self::$dbFile = self::getCacheFilePath();
    self::$db = new SQLite3(self::$dbFile);
    self::createSchema();
    if (method_exists(__CLASS__, 'flushMetrics')) {
      register_shutdown_function(array(__CLASS__, 'flushMetrics'));
    }
    self::$initialized = true;
  }

  /**
   * Retourne le chemin absolu du fichier de cache SQLite.
   *
   * @return string
   */
  public static function getCacheFilePath()
  {
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'folder_info_cache.sqlite';
  }

  /**
   * Récupère les informations mises en cache pour un dossier.
   * Si l'entrée est invalide mais existe, retourne les données stale.
   * Si l'entrée n'existe pas, calcule les statistiques en mode bloquant.
   *
   * @param string $dir Chemin du dossier
   * @param bool $allowAsyncRefresh Autorise le déclenchement d'un refresh asynchrone
   * @return array Informations sur le dossier (inclut status)
   */
  public static function getCachedFolderInfo($dir, $allowAsyncRefresh = true)
  {
    self::init();
    $realPath = realpath($dir);
    if (!$realPath || !is_dir($realPath)) {
        return ['error' => 'Invalid directory'];
    }

    $cacheEntry = self::getCacheEntry($realPath);
    $dirMtime = filemtime($realPath);
    $isFresh = $cacheEntry
      && isset($cacheEntry['dir_mtime'], $cacheEntry['size'], $cacheEntry['last_modified'])
      && ((int)$cacheEntry['dir_mtime'] === (int)$dirMtime);

    if ($isFresh) {
        self::$metrics['cache_hits']++;
        return $cacheEntry + ['status' => 'fresh'];
    }

    // Utilise les données stale si disponibles et déclenche un refresh asynchrone.
    if ($cacheEntry && isset($cacheEntry['size'], $cacheEntry['last_modified'])) {
        self::$metrics['cache_stale']++;

        if ($allowAsyncRefresh) {
          self::triggerBackgroundUpdate();
        }

        return $cacheEntry + ['status' => 'stale'];
    }

    // Aucun cache disponible: calcul immédiat (bloquant).
    self::$metrics['cache_miss']++;
    $start = microtime(true);
    $stats = self::collectDirectoryStats($realPath, self::MAX_SCAN_SECONDS);
    $info = [
        'size' => $stats['size'],
        'last_modified' => $stats['last_modified'],
        'dir_mtime' => (int)$dirMtime,
        'timestamp' => time()
    ];
    self::$metrics['compute_time_ms'] += (microtime(true) - $start) * 1000;
    self::saveCacheEntry($realPath, $info);
    return $info + ['status' => 'fresh'];
  }

  /**
   * Parcourt récursivement un arbre de dossiers et met à jour le cache si besoin.
   * Retourne les informations de chaque dossier traité.
   *
   * @param string $rootDir Répertoire racine à parcourir
   * @return array
   */
  public static function processDirectoryTree($rootDir)
  {
    self::init();
    $results = [];

    try {
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
        RecursiveIteratorIterator::CATCH_GET_CHILD
      );

      // Met à jour/récupère les infos de cache pour chaque dossier.
      foreach ($iterator as $file) {
        if ($file->isDir()) {
          $dirPath = $file->getRealPath();
          if ($dirPath !== false) {
            $results[$dirPath] = self::getCachedFolderInfo($dirPath, false);
          }
        }
      }
    } catch (UnexpectedValueException $e) {
      error_log('[archiver-cache] processDirectoryTree skipped unreadable path under: ' . $rootDir);
    }

    // Traite aussi le dossier racine.
    $rootReal = realpath($rootDir);
    if ($rootReal && is_dir($rootReal)) {
      $results[$rootReal] = self::getCachedFolderInfo($rootReal, false);
    }

    return $results;
  }

  /**
   * Scan récursif en un seul passage avec budget de temps strict.
   * Évite les fatals max_execution_time sur les très grosses arborescences.
   *
   * @param string $dir
   * @param float $maxSeconds Budget maximal de scan
   * @return array{size: int, last_modified: string}
   */
  private static function collectDirectoryStats($dir, $maxSeconds)
  {
    $lastModified = 0;
    $size = 0;
    $deadline = microtime(true) + (float)$maxSeconds;

    try {
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
        RecursiveIteratorIterator::CATCH_GET_CHILD
      );

      foreach ($iterator as $file) {
        if (microtime(true) >= $deadline) {
          error_log('[archiver-cache] scan timeout budget reached in: ' . $dir);
          break;
        }

        if ($file->isFile()) {
          $size += $file->getSize();
          $fileModified = $file->getMTime();
          if ($fileModified > $lastModified) {
            $lastModified = $fileModified;
          }
        }
      }
    } catch (UnexpectedValueException $e) {
      error_log('[archiver-cache] collectDirectoryStats skipped unreadable path in: ' . $dir);
    }

    return [
      'size' => $size,
      'last_modified' => $lastModified ? date('Y-m-d H:i:s', $lastModified) : 'N/A',
    ];
  }

  /**
   * Déclenche une mise à jour asynchrone du cache (debounce global).
   */
  private static function triggerBackgroundUpdate()
  {
    $lockFile = sys_get_temp_dir() . '/cache_update_global.lock';

    // Si un lock récent existe (5 min), un worker tourne déjà.
    if (file_exists($lockFile) && (time() - filemtime($lockFile) < 300)) {
        // Mise à jour déjà en cours.
        return;
    }

    // Crée ou rafraîchit le lock.
    touch($lockFile);

    // Construit et lance la commande.
    // Débounce global: un seul worker rafraîchit depuis la racine archives.
    $script = escapeshellarg(__DIR__ . '/update_cache.php');
    $dirArg = escapeshellarg(self::getArchivesRootPath());
    $cmd = "php $script $dirArg > /dev/null 2>&1 &";
    exec($cmd);
  }

  /**
   * Retourne le chemin absolu de la racine des archives.
   *
   * @return string
   */
  private static function getArchivesRootPath()
  {
    $archivesPath = realpath(__DIR__ . '/../archives');
    if ($archivesPath && is_dir($archivesPath)) {
      return $archivesPath;
    }

    // Fallback: conserve le comportement précédent si la racine archives est introuvable.
    return dirname(__DIR__);
  }

  /**
   * Crée la table SQLite de cache si elle n'existe pas.
   *
   * @return void
   */
  private static function createSchema()
  {
    self::$db->exec(
      'CREATE TABLE IF NOT EXISTS folder_cache (
        path TEXT PRIMARY KEY,
        size INTEGER NOT NULL,
        last_modified TEXT NOT NULL,
        dir_mtime INTEGER NOT NULL,
        timestamp INTEGER NOT NULL
      )'
    );
  }

  /**
   * Lit une entrée de cache SQLite pour un chemin donné.
   *
   * @param string $path
   * @return array|null
   */
  private static function getCacheEntry($path)
  {
    $stmt = self::$db->prepare(
      'SELECT size, last_modified, dir_mtime, timestamp FROM folder_cache WHERE path = :path LIMIT 1'
    );
    if ($stmt === false) {
      error_log('[archiver-cache] getCacheEntry prepare failed: ' . self::$db->lastErrorMsg());
      return null;
    }
    $stmt->bindValue(':path', $path, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result ? $result->fetchArray(SQLITE3_ASSOC) : false;
    if ($result) {
      $result->finalize();
    }

    return $row ?: null;
  }

  /**
   * Enregistre ou remplace une entrée de cache SQLite.
   *
   * @param string $path
   * @param array $info
   * @return void
   */
  private static function saveCacheEntry($path, $info)
  {
    $stmt = self::$db->prepare(
      'INSERT OR REPLACE INTO folder_cache (path, size, last_modified, dir_mtime, timestamp)
       VALUES (:path, :size, :last_modified, :dir_mtime, :timestamp)'
    );
    if ($stmt === false) {
      error_log('[archiver-cache] saveCacheEntry prepare failed: ' . self::$db->lastErrorMsg());
      return;
    }
    $stmt->bindValue(':path', $path, SQLITE3_TEXT);
    $stmt->bindValue(':size', (int)$info['size'], SQLITE3_INTEGER);
    $stmt->bindValue(':last_modified', (string)$info['last_modified'], SQLITE3_TEXT);
    $stmt->bindValue(':dir_mtime', (int)$info['dir_mtime'], SQLITE3_INTEGER);
    $stmt->bindValue(':timestamp', (int)$info['timestamp'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    if ($result instanceof SQLite3Result) {
      $result->finalize();
    }
  }

  /**
   * Écrit les métriques de cache en log et ferme la connexion SQLite.
   *
   * @return void
   */
  public static function flushMetrics()
  {
    if (!self::$initialized) {
      return;
    }

    $calls = self::$metrics['cache_hits'] + self::$metrics['cache_stale'] + self::$metrics['cache_miss'];
    if ($calls === 0) {
      if (self::$db instanceof SQLite3) {
        self::$db->close();
      }
      return;
    }

    error_log('[archiver-cache] ' . json_encode(self::$metrics));

    if (self::$db instanceof SQLite3) {
      self::$db->close();
    }
  }
}
