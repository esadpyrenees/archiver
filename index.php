<?php
include_once 'functions/functions.php';
include_once '_inc/Parsedown.php';
include_once '_inc/ParsedownExtra.php';
include_once '_inc/ParsedownExtraPlugin.php';

$params = '';
if (isset($_GET['params'])) {
    $params = '/' . $_GET['params'];
}

// $archivesdir = '../../archives';
$rootdir = dirname(__FILE__);
$archivesdir = $rootdir . "/archives";
$currentdir = $archivesdir . $params;
$root_url =  str_replace("index.php", "", $_SERVER['SCRIPT_NAME']);

// markdown!
$Parsedown = new ParsedownExtraPlugin();
$Parsedown->figuresEnabled = true;

// Start timer for cached size calculation
//$startCached = startTimer();

$results = array();
$cool_extensions = array('jpg', 'png', 'pdf', 'gif', 'webp', 'html', 'zip', 'css', 'js');
$forbidden_extensions = array('psd', 'tif', 'tiff', 'ai', 'indd');

// browse currentdir, looking for subdirs or index*
if (is_dir($currentdir)) {
    foreach (new DirectoryIterator($currentdir) as $fileinfo) {
        if ($fileinfo->isDot()) continue; // Ignore . et ..
        if (str_starts_with($fileinfo->getFilename(), ".")) continue;

        if ($fileinfo->isDir()) { // Subfolder find
            $folderPath = $fileinfo->getPathname();
            $folderInfo = getCachedFolderInfo($folderPath);
            $has_forbidden = false;
            $html_file = false;
            $html_filename = "";

            foreach (new DirectoryIterator($folderPath) as $subfileinfo) {
                if ($subfileinfo->isDot()) continue;

                $extension = $subfileinfo->getExtension();

                //verifier les fichiers interdits
                if (in_array($extension, $forbidden_extensions)) {
                    $has_forbidden = true;
                }

                if ($extension == 'html') {
                    $html_file = true;
                    $html_filename = $subfileinfo->getFileName(); //saving the HTML file name
                }
            }
            $results[] = [
                'path' =>  $fileinfo->getFilename() . '/' . ($html_file ? $html_filename : ''), //adding the path to the html file which is into the folder 
                'name' => $fileinfo->getFilename() ,
                'is_empty' => isEmpty($folderPath),
                'size' => sizeFilter($folderInfo['size']),
                'last_modified' => $folderInfo['last_modified'],
                'has_forbidden' => $has_forbidden,
                'has_html' => $html_file
            ];
        } elseif (in_array($fileinfo->getExtension(), $cool_extensions)) {
            $results[] = [
                'path' => $fileinfo->getFilename(),
                'name' => $fileinfo->getFilename(),
                'is_empty' => false,
                'size' => sizeFilter($fileinfo->getSize()),
                'has_forbidden' => false,
                'has_html' => ($fileinfo->getExtension() == 'html')
            ];
        }
    }
}

define('WARNING_GLYPH', '▲');
define('EMPTY_GLYPH', '●');

// build a breadcrumb
if ($params) {
    $breadcrumb = [];
    $path = str_replace($rootdir, "", $currentdir);
    $parts =  explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $currentPath = '';
    foreach ($parts as $part) {
        $currentPath .= $part . DIRECTORY_SEPARATOR ;
        $breadcrumb[] = "<a href=\"$root_url$currentPath\">$part</a>";
    }
    $breadcrumb = implode(" / ", $breadcrumb);
} 

// Sort and display the directory content
rsort($results);

//TODO : Finir de corriger les erreurs validator (8 errors 1 warning actuellement)
//TODO : gérer la durée du cache (la fréquence d'actualisation)
//TODO : Commenter et ré arranger le code 

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives ESAD</title>
    <link rel="stylesheet" href="<?= $root_url ?>/style/style.css">    
</head>

<body>
    <main id="archives">
        <nav class="archives-nav">
            <p class='parentFolder'><?= $breadcrumb ?></p>
            <ul class='displayFolders'>
                <?php foreach ($results as $dir) :                        
                    if ($dir['has_forbidden'] || $dir['is_empty']) {
                        $glyphs =  ($dir['has_forbidden'] ? WARNING_GLYPH : "") . " " . ($dir['is_empty'] ? EMPTY_GLYPH : "");
                    } else {
                        $glyphs = '';
                    }
                ?>
                <li class='file-info'>
                    <a href="<?= $dir['path'] ?>"><?= $dir['name'] ?></a>
                    <span class="glyphs"><?= $glyphs ?></span>
                    <span class="size"><?= $dir['size'] ?></span>
                    <span class="date"><?= $dir['last_modified'] ?></span>
                </li>
                <?php endforeach ?>
            </ul>
        </nav>
        <?php 
        // Display the content of index.md if it exists
        $mdindex = hasMDIndex($currentdir);
        if ($mdindex) :?>
            <div class="markdown">
                <?= $Parsedown->text(file_get_contents($mdindex)) ?>
            </div>
        <?php endif  ?>
    </main>
</body>

</html>