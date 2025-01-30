<?php
include_once 'functions/functions.php';
include_once '_inc/Parsedown.php';
include_once '_inc/ParsedownExtra.php';
$params = '';
if (isset($_GET['params'])) {
    $params = '/' . $_GET['params'];
}

// $archivesdir = '../../archives';

$archivesdir = dirname(__FILE__) . "/archives";
$currentdir = $archivesdir . $params;

// markdown!
$Parsedown = new ParsedownExtra();

// Start timer for cached size calculation
//$startCached = startTimer();

$results = array();
$cool_extensions = array('jpg', 'png', 'pdf', 'gif', 'webp', 'html', 'zip', 'css', 'js');
$forbidden_extensions = array('psd', 'tif', 'tiff', 'ai', 'indd');

// browse currentdir, looking for subdirs or index*
if (is_dir($currentdir)) {
    foreach (new DirectoryIterator($currentdir) as $fileinfo) {
        if ($fileinfo->isDot()) continue; // Ignore . et ..

        if ($fileinfo->isDir()) { // Subfolder find
            $folderPath = $fileinfo->getPathname();
            $has_forbidden = false;

            // Check if the folder contains forbidden files / add true do the boolean $has_forbidden if it does 
            foreach (new DirectoryIterator($folderPath) as $subfileinfo) {
                if ($subfileinfo->isDot()) continue;
                elseif (in_array($subfileinfo->getExtension(), $forbidden_extensions)) {
                    $has_forbidden = true;
                    break;
                }
            }

            $results[] = [
                'path' =>  $fileinfo->getFilename() . '/',
                'name' => $fileinfo->getFilename() . '/',
                'is_empty' => isEmpty($folderPath),
                'size' => sizeFilter(getCachedFolderSize($folderPath)),
                'has_forbidden' => $has_forbidden
            ];
        } elseif (in_array($fileinfo->getExtension(), $cool_extensions)) {
            $results[] = [
                'path' => $fileinfo->getFilename(),
                'name' => $fileinfo->getFilename(),
                'is_empty' => false,
                'size' => sizeFilter(filesize($fileinfo->getPathname())), // Taille des fichiers
                'has_forbidden' => false
            ];
        }
    }
}

define('WARNING_GLYPH', '⚠');
define('EMPTY_GLYPH', '●');

//TODO :au niveau des warnings mettre une erreur lors de l'ouverture du dossier contenant un fichier "interdit" ? 
//TODO : Finir de corriger les erreurs validator (8 errors 1 warning actuellement)
//TODO : ouvrir un dossier uqi contient un fichier .html (ouvrir directement le fichier)
//TODO : verifier la meilleure solution pour la taille des dossiers/fichiers

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="" width=device-width, initial-scale="1.0" />
    <title>Archives ESAD</title>
    <link rel="stylesheet" href="<?= str_replace("index.php", "style/style.css", $_SERVER['SCRIPT_NAME']) ?>">
</head>

<body>
    <main class="pane active" id="content">
        <h1>Archives</h1>
        <nav class="archives-nav">
            <p>☺</p>
            <?php
            echo "<ul class='parentFolder'>";
            if ($params) {
                $up = dirname($currentdir);
                $upname = basename($up);
                $currentFolderName = basename($currentdir);
                echo "<li><a href='../'/>← $upname / $currentFolderName</a></li>";
            }

            // Sort and display the directory content
            rsort($results);
            echo "</ul>

      <div class='displayFolders'>
      <ul style='list-style:none'>";
            foreach ($results as $dir) {
                $glyphs = '';
                if ($dir['has_forbidden']) {
                    $glyphs .= WARNING_GLYPH . ' ';
                }
                if ($dir['is_empty']) {
                    $glyphs .= EMPTY_GLYPH . ' ';
                }
                echo "<li class='file-info'>
                 <span class='glyphs'>{$glyphs}</span>
                    <a href='{$dir['path']}'>{$dir['name']}</a>
                    <p>({$dir['size']})</p>
                </li>";
            }
            echo "</ul>";
            echo "</div>";

            // Display the content of index.md if it exists
            $mdindex = hasMDIndex($currentdir);
            if ($mdindex) {
                echo "<hr>";
                echo $Parsedown->text(file_get_contents($mdindex));
            }
            ?>
        </nav>
    </main>
</body>

</html>