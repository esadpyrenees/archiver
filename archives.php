
<?php 
include_once 'functions/functions.php';
include_once '_inc/Parsedown.php';
include_once '_inc/ParsedownExtra.php';

  $params = '';
  if (isset($_GET['params'])) {
    $params = '/' . $_GET['params'];
  }

  // $archivesdir = '../../archives';
  $archivesdir = dirname(__FILE__) . "/root";
  $currentdir = $archivesdir . $params; 
  
  // markdown!
  $Parsedown = new ParsedownExtra();
?> 

  <main class="pane active" id="content">
    <link rel="stylesheet" href="style/style.css">
  <h1>Archives</h1>
    <nav class="archives-nav">
      <!-- L’archivisme est un exercice délicat ☺<br><br> -->
      <p>☺</p>
      <?php
        $results = array();
        $cool_extensions = Array('jpg','png','pdf','gif','webp','html','zip', 'css', 'js');

        // browse currentdir, looking for subdirs or index*
        foreach (new DirectoryIterator($currentdir) as $fileinfo) {
          if ($fileinfo->isDot()) continue; // Ignore . et ..
          if ($fileinfo->isDir()) {
              // Sous-dossier trouvé
              $results[] = [
                  'path' => $fileinfo->getFilename() . '/',
                  'name' => $fileinfo->getFilename() . '/',
                  'is_empty' => isEmpty($fileinfo->getPathname())
              ];
          } elseif (in_array($fileinfo->getExtension(), $cool_extensions)) {
              // Fichier trouvé (avec une extension cool)
              $results[] = [
                  'path' => $fileinfo->getFilename(),
                  'name' => $fileinfo->getFilename(),
                  'is_empty' => false
              ];
          }
        }

        echo "<ul style='list-style:none'>";
        if ($params) {
          $up = dirname($currentdir);
          $upname = basename($up);
          echo "<li><a href='../'/>← $upname</a></li>";
        }
          // Sort and display the directory content
rsort($results);
echo "</ul>



<ul>";
foreach ($results as $dir) {
  if ($dir['is_empty']) {
  echo "<li><a href='" . $dir['path'] . "'>" . $dir['name'] . "</a></li>";
}
else {
echo "<li><a href='" . $dir['path'] . "'>" . $dir['name'] . "</a></li>";

}
}
echo "</ul>";




// Display the content of index.md if it exists
$mdindex = hasMDIndex($currentdir);
if ($mdindex) {
    echo "<hr>";
    echo $Parsedown->text(file_get_contents($mdindex));
}
      ?>
    </nav>
  </main>
