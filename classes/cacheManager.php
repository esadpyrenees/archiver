<?php

class CacheManager
{
  private static $cacheFile;

  public static function init()
  {
    self::$cacheFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'folder_info_cache.json';
  }


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
      error_log("Utilisation du cache pour le dossier : " . $dir);
      return $cacheData[$dir];
    }

    error_log("Recalcul des informations pour le dossier : " . $dir);
    $info = [
      'size' => self::calculateFolderSize($dir),
      'last_modified' => self::getLastModifiedDate($dir),
      'timestamp' => time()
    ];
    $cacheData[$dir] = $info;
    file_put_contents(self::$cacheFile, json_encode($cacheData));
    return $info;
  }


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
