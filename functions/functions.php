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


function calculateFolderSize($dir)
{
  $size = 0;

  foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
    $size += is_file($each) ? filesize($each) : calculateFolderSize($each);
  }

  return $size;
}





//convert bytes 
function sizeFilter($bytes)
{
  $label = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

  for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++);

  return (round($bytes, 2) . " " . $label[$i]);
}


//trying this function to cache the folders size to optimise application
function getCachedFolderSize($dir)
{
  $cacheFile = sys_get_temp_dir() . "folder_size_cache_" . md5($dir)  . '.txt';
  if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
    return file_get_contents($cacheFile);
  } else {
    $size = calculateFolderSize($dir);
    file_put_contents($cacheFile, $size);
    return $size;
  }
}

//function to clear cache
function clearCache($dir)
{
  $cacheFile = sys_get_temp_dir() . "/folder_size_cache_" . md5($dir) . '.txt';
  if (file_exists($cacheFile)) {
    unlink($cacheFile);
  }
}


/*fonctions pour pouvoir tester la performance de la mise en cache et de l'affichage des tailles des dossiers

  function startTimer() {
  return microTime(true);
  }

  function endTimer($start) {
    return microTime(true) - $start;
  }
  

  */
