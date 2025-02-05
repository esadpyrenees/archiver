<?php


/**
 * Summary of CacheManager
 * Gère la mise en cache des infos des dossiers/fichiers dans un format json pour alléger et améliorer les performances.
 * Le cache est actualisé toute les 24H.
 */
class CacheManager
{
  private static $cacheFile;

  /**
   *On initialise le cache en précisant le repertoire et le nom du fichier de cache
   */
  public static function init()
  {
    self::$cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'folder_info_cache.json';
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

    if (file_exists(self::$cacheFile)) {
      $cacheData = json_decode(file_get_contents(self::$cacheFile), true);
    } else {
      $cacheData = [];
    }

    // Vérifier si le cache pour ce dossier est valide
    if (isset($cacheData[$dir]) && (time() - $cacheData[$dir]['timestamp'] < 86400)) {
      //debug dans le fichier php_error
      error_log("Utilisation du cache pour le dossier : " . $dir);
      return $cacheData[$dir];
    }
    //debug dans le fichier php_error
    error_log("Recalcul des informations pour le dossier : " . $dir);
    $info = [
      'size' => self::calculateFolderSize($dir),
      'last_modified' => self::getLastModifiedDate($dir),
      'timestamp' => time()
    ];
    $cacheData[$dir] = $info;
    file_put_contents(self::$cacheFile, json_encode($cacheData, JSON_PRETTY_PRINT));
    return $info;
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
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
      if ($file->isFile()) {
        $size += $file->getSize();
      }
    }
    return $size;
  }

  /**
   * Summary of getLastModifiedDate
   * Cette fonction parcourt tous les fichiers du dossier spécifié et retourne la date de dernière modification
   * la plus récente parmi tous les fichiers.
   * @param string $dir Chemin du dossier
   * @return string Date de dernière modification formatée dans notre fuseau horraire
   */
  private static function getLastModifiedDate($dir)
  {
    date_default_timezone_set('Europe/Paris');
    $lastModified = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
      if ($file->isFile()) {
        $fileModified = $file->getMTime();
        if ($fileModified > $lastModified) {
          $lastModified = $fileModified;
        }
      }
    }
    return $lastModified ? date('Y-m-d H:i:s', $lastModified) : 'N/A';
  }
}
