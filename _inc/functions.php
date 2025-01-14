<?php

//find on  https://gist.github.com/eusonlito/5099936

// return the size of a folder including subfolders 
function folderSize ($dir)
{
    $size = 0;

    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : folderSize($each);
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