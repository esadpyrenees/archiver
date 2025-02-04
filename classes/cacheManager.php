<?php

class CacheManager
{
  private string $cacheFile;

  public function __construct()
  {
    $this->cacheFile = sys_get_temp_dir() . "/folder_info_cache.json";
  }

  /**
   * Récupère les informations du cache si elles sont valides
   */
  public function getCachedInfo(string $dir): ?array
  {
    if (!file_exists($this->cacheFile)) {
      return null;
    }

    $cacheData = json_decode(file_get_contents($this->cacheFile), true);

    if (isset($cacheData[$dir]) && (time() - $cacheData[$dir]['timestamp'] < 3600)) {
      return $cacheData[$dir];
    }

    return null;
  }

  /**
   * Sauvegarde les informations du dossier en cache
   */
  public function saveToCache(string $dir, array $info): void
  {
    $cacheData = [];

    if (file_exists($this->cacheFile)) {
      $cacheData = json_decode(file_get_contents($this->cacheFile), true);
    }

    $info['timestamp'] = time();
    $cacheData[$dir] = $info;
    file_put_contents($this->cacheFile, json_encode($cacheData));
  }
}
