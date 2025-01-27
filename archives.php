<?php 
include_once 'functions/functions.php';
include_once '_inc/Parsedown.php';
include_once '_inc/ParsedownExtra.php';

$params = '';
if (isset($_GET['params'])) {
  $params = '/' . $_GET['params'];
}

// $archivesdir = '../../archives';

//Maybe but this on a variable to be able to change it simplier if needed
$archivesdir = dirname(__FILE__) . "/archives";
$currentdir = $archivesdir . $params; 

// markdown!
$Parsedown = new ParsedownExtra();

// Start timer for cached size calculation
//$startCached = startTimer();

$results = array();
$cool_extensions = Array('jpg','png','pdf','gif','webp','html','zip', 'css', 'js');

// browse currentdir, looking for subdirs or index*
  if(is_dir($currentdir)){
foreach (new DirectoryIterator($currentdir) as $fileinfo) {
  if ($fileinfo->isDot()) continue; // Ignore . et ..
                
  if ($fileinfo->isDir()) {// Subfolder find 
    $folderPath = $fileinfo->getPathname();
    $results[] = [
        'path' => $fileinfo->getFilename() . '/',
        'name' => $fileinfo->getFilename() . '/',
        'is_empty' => isEmpty($folderPath),
        'size' => sizeFilter(getCachedFolderSize($folderPath))
    ];
  
  } elseif (in_array($fileinfo->getExtension(), $cool_extensions)) {
    $results[] = [
        'path' => $fileinfo->getFilename(),
        'name' => $fileinfo->getFilename(),
        'is_empty' => false,
        'size' => sizeFilter(filesize($fileinfo->getPathname())) // Taille des fichiers
    ];
  }
}
} elseif (is_file($currentdir)){
  if(pathinfo($currentdir, PATHINFO_EXTENSION) === 'html') {
    header("Location" . $_GET['params']);
    exit;
  } else{
    echo " Error : THe specified path is not a directory or a valid HTML File.";
    exit;
  }
}

?> 

<main class="pane active" id="content">
  <link rel="stylesheet" href="/lister/style/style.css">
  <h1>Archives</h1> 
  <nav class="archives-nav">
    <!-- L’archivisme est un exercice délicat ☺<br><br> -->
    <p>☺</p>
    
    <?php
    
      echo "<ul class='parentFolder'>";
      if ($params) {
        $up = dirname($currentdir);
        $upname = basename($up);
        echo "<li><a href='../'/>← $upname</a></li>";
      }
      // Sort and display the directory content
      rsort($results);
      echo "</ul>

      <div class='displayFolders'>
      <ul style='list-style:none'>";
      foreach ($results as $dir) {
        
          // if (pathinfo($dir['path'], flags: PATHINFO_EXTENSION) == 'html') {
          //   //on vérifie le lien html
          //   $filePath = $currentdir . $dir['path'];
          //   echo "<li class='file-info'><a href='" . $dir['path'] . "' target='_blank'>" . $dir['name'] . "</a> <p>(" . $dir['size'] . ")</p></li>";
          //   //echo "<script>console.log('Opening file: " . $currentdir . "/" . $dir['path'] . "');</script>";
          // } else 
          {
            echo "<li class='file-info'><a href='" . $dir['path'] . "'>" . $dir['name'] . "</a> <p>(" . $dir['size'] . ")</p></li>";
          }
        }
      
      echo "</ul>
      </div>";

      // Display the content of index.md if it exists
      $mdindex = hasMDIndex($currentdir);
      if ($mdindex) {
          echo "<hr>";
          echo $Parsedown->text(file_get_contents($mdindex));
      }
    ?>
  </nav>
</main>