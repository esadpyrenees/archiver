<?php

/**
 * Summary of CacheManager
 * Gère la mise en cache des infos des dossiers/fichiers dans un format json pour alléger et améliorer les performances.
 * Le cache est actualisé toute les 24H.
 */
class CacheManager
{
  private static $cacheFile;
  private static $cacheData = [];
  private static $initialized = false;
  private const FRESHNESS_THRESHOLD = 86400; // 24 hours


  /**
   * On initialise le cache en précisant le repertoire et le nom du fichier de cache
   */
  public static function init()
  {
    if (self::$initialized) return;
    self::$cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'folder_info_cache.json';    
    if (file_exists(self::$cacheFile)) {
      self::$cacheData = json_decode(file_get_contents(self::$cacheFile), true) ?? [];
    }
    self::$initialized = true;
  }

  /**
   * Summary of getCachedFolderInfo
   * Récupère les informations mises en caches pour un dossier
   * @param string $dir Chemin du dossier
   * @return array Informations sur le dossier
   */
  public static function getCachedFolderInfo($dir)
  {
    self::init(); // Initialiser le chemin du fichier cache

    $realPath = realpath($dir);
    if (!$realPath) return null;

    // Vérifier si le cache pour ce dossier est valide
    $isFresh = isset(self::$cacheData[$realPath]) && (time() - self::$cacheData[$realPath]['timestamp'] < self::FRESHNESS_THRESHOLD);
    if ($isFresh) {
      return self::$cacheData[$realPath] + ['status' => 'fresh'];
    }

    // Use stale cached data if available
    if (isset(self::$cacheData[$realPath])) {
      self::triggerBackgroundUpdate($realPath); // fire & forget
      return self::$cacheData[$realPath] + ['status' => 'stale'];
    }

    //debug dans le fichier php_error
    //error_log("Recalcul des informations pour le dossier : " . $dir);
    $info = [
      'size' => self::calculateFolderSize($realPath),
      'last_modified' => self::getLastModifiedDate($realPath),
      'timestamp' => time(),
      'status' => 'fresh'
    ];

    self::$cacheData[$realPath] = $info;
    self::saveCache();
    return $info;
  }

  /**
   * Recursively process a directory tree and update the cache if needed.
   * Returns an array of all processed directory infos.
   */
  public static function processDirectoryTree($rootDir)
  {
    self::init();
    $results = [];

    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
      if ($file->isDir()) {
        $dirPath = $file->getRealPath();
        $results[$dirPath] = self::getCachedFolderInfo($dirPath);
      }
    }

    // Also process the root directory itself
    $rootReal = realpath($rootDir);
    if ($rootReal && is_dir($rootReal)) {
      $results[$rootReal] = self::getCachedFolderInfo($rootReal);
    }

    return $results;
  }


  /**
   * Summary of calculateFolderSize
   * Calcule récursif de  la taille d'un dossier pour calculer sa taille totale
   * @param string $dir chemin du dossier
   * @return int taille du dossier en octets
   */
  private static function calculateFolderSize($dir)
  {
    $size = 0;
    $iterator = new DirectoryIterator($dir);

    foreach ($iterator as $fileinfo) {
      if ($fileinfo->isDot()) continue;

      $path = $fileinfo->getPathname();

      if ($fileinfo->isFile()) {
        $size += $fileinfo->getSize();

      } elseif ($fileinfo->isDir()) {
        // Check if cached info for subdir is fresh
        $cached = self::getCachedFolderInfo($path); // will refresh if stale
        if ($cached && isset($cached['size'])) {
          $size += $cached['size'];
        }
      }
    }

    return $size;
  }

  /**
   * Summary of getLastModifiedDate
   * Cette fonction parcourt tous les fichiers du dossier spécifié et retourne la date de dernière modification
   * la plus récente parmi tous les fichiers.
   * @param string $dir Chemin du dossier
   * @return string Date de dernière modification formatée dans notre fuseau horraire ou 'N/A' si pas de date
   */
  private static function getLastModifiedDate($dir)
  {
    date_default_timezone_set('Europe/Paris');
    $lastModified = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
      if ($file->isFile()) {
        $fileModified = $file->getMTime();
        if ($fileModified > $lastModified) {
          $lastModified = $fileModified;
        }
      }
    }
    return $lastModified ? date('Y-m-d H:i:s', $lastModified) : 'N/A';
  }

  /**
   * Async update of cache
   */
  private static function triggerBackgroundUpdate($path)
  {
      $script = escapeshellarg(__DIR__ . '/update_cache.php');
      $dirArg = escapeshellarg($path);

      $cmd = "php $script $dirArg > /dev/null 2>&1 &";

      // error_log("Triggering cache update: $cmd");

      exec($cmd);
  }

  /**
   * Save cache
   */
  private static function saveCache()
  {
    file_put_contents(self::$cacheFile, json_encode(self::$cacheData, JSON_PRETTY_PRINT));
  }
}
