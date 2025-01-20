<?php

function hasIndex($dir){
  $archivesdir = dirname(__FILE__) . "./root";    
  $archivesdir = $archivesdir . "/" . $dir;
  $s = scandir( $dir);
  $fs = array_diff( $s, array(".", "..") );
  foreach($fs as $f){
    if(in_array($f, ["index.html", "index.php", "index.htm"])){        
      return $f;
    } 
  }
  return false;
}

// Does the dir has an index.md file 
  function hasMDIndex($dir){
    foreach(glob($dir.'/index.md',GLOB_BRACE) as $file){
      return $file;
    }
    return false;
  }

    function isEmpty($dir){
    return !(new \FilesystemIterator($dir))->valid();
  }
   
    


  function hasSubDirectories($dir){
    foreach (new DirectoryIterator($dir) as $fileinfo) {
      if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        return true;
      }
    }return false;
  }
  

  function calculateFolderSize ($dir)
{
    $size = 0;

    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : calculateFolderSize($each);
    }

    return $size;
}





//convert bytes 
function sizeFilter($bytes)
    {
        $label = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

        for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++) ;

        return (round($bytes, 2) . " " . $label[$i]);
    }


    


  ?>