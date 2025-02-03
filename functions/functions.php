<?php

function hasIndex($dir)
{
  $archivesdir = dirname(__FILE__) . "./root";
  $archivesdir = $archivesdir . "/" . $dir;
  $s = scandir($dir);
  $fs = array_diff($s, array(".", ".."));
  foreach ($fs as $f) {
    if (in_array($f, ["index.html", "index.php", "index.htm"])) {
      return $f;
    }
  }
  return false;
}

// Does the dir has an index.md file 
function hasMDIndex($dir)
{
  foreach (glob($dir . '/index.md', GLOB_BRACE) as $file) {
    return $file;
  }
  return false;
}

function isEmpty($dir)
{
  return !(new \FilesystemIterator($dir))->valid();
}

function hasSubDirectories($dir)
{
  foreach (new DirectoryIterator($dir) as $fileinfo) {
    if ($fileinfo->isDir() && !$fileinfo->isDot()) {
      return true;
    }
  }
  return false;
}
function hasHtmlFile($dir)
{
  foreach (new DirectoryIterator($dir) as $fileinfo) {
    if ($fileinfo->isDot()) continue;
    if ($fileinfo->getExtension() == 'html') {
      return $fileinfo->getPathname();
    }
  }
  return false;
}


//convert bytes 
function sizeFilter($bytes)
{
  $label = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

  for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++);

  return (round($bytes, 2) . " " . $label[$i]);
}

function calculateFolderSize($dir)
{
  $size = 0;
  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
    if ($file->isFile()) {
      $size += $file->getSize();
    }
  }
  return $size;
}

function getLastModifiedDate($dir)
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


function getCachedFolderInfo($dir)
{
  $cacheFile = sys_get_temp_dir() . "/folder_info_cache.json";
  $cacheData = [];

  // Charger le cache existant
  if (file_exists($cacheFile)) {
    $cacheData = json_decode(file_get_contents($cacheFile), true);
  }

  // Vérifier si le cache pour ce dossier est valide
  if (isset($cacheData[$dir]) && (time() - $cacheData[$dir]['timestamp'] < 3600)) {
    error_log("Utilisation du cache pour le dossier : " . $dir);
    return $cacheData[$dir];
  } else {
    error_log("Recalcul des informations pour le dossier : " . $dir);
    $info = [
      'size' => calculateFolderSize($dir),
      'last_modified' => getLastModifiedDate($dir),
      'timestamp' => time() // Ajouter un timestamp pour gérer l'expiration
    ];
    $cacheData[$dir] = $info;
    file_put_contents($cacheFile, json_encode($cacheData));
    return $info;
  }
}


// function getCachedFolderSize($dir)
// {
//   $cacheFile = sys_get_temp_dir() . "folder_size_cache_" . md5($dir)  . '.txt';
//   if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
//     return file_get_contents($cacheFile);
//   } else {
//     $size = calculateFolderSize($dir);
//     file_put_contents($cacheFile, $size);
//     return $size;
//   }
// }

// //function to clear cache
// function clearCache($dir)
// {
//   $cacheFile = sys_get_temp_dir() . "/folder_size_cache_" . md5($dir) . '.txt';
//   if (file_exists($cacheFile)) {
//     unlink($cacheFile);
//   }
// }
