<?php
include '_inc/Parsedown.php';
include '_inc/ParsedownExtra.php';
include '_inc/ParsedownExtraPlugin.php';
require_once 'classes/DirectoryNavigator.php';
require_once 'classes/FileHandler.php';


$root_url =  str_replace("index.php", "", $_SERVER['SCRIPT_NAME']);

// markdown!
$Parsedown = new ParsedownExtraPlugin();
$Parsedown->figuresEnabled = true;

$params = '';
if (isset($_GET['params'])) {
    $params = '/' . $_GET['params'];
}

// $archivesdir = '../../archives';
$rootdir = dirname(__FILE__);
$archivesdir = $rootdir . "/archives";
$currentdir = $archivesdir . $params;


define('WARNING_GLYPH', '▲');
define('EMPTY_GLYPH', '●');
define('SPACE_GLYPH', '␣');


// build a breadcrumb
if ($params) {
    $breadcrumb = [];
    $path = str_replace($rootdir, "", $currentdir);
    $parts =  explode("/", trim($path, "/"));
    $currentPath = '';
    foreach ($parts as $part) {
        $currentPath .= $part . "/";
        $breadcrumb[] = "<a href='" . $root_url . $currentPath . "'>$part</a>";
    }
    $breadcrumb = implode(" / ", $breadcrumb);
}

$navigator = new DirectoryNavigator($archivesdir, $params);
$results = $navigator->getFilesAndFolders();
rsort($results);

$fileHandler = new FileHandler();
if ($fileHandler->hasHTMLIndex($currentdir)) {
    exit;
}

//TODO : Finir de corriger les erreurs validator (8 errors 1 warning actuellement)


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
                    $glyphs = '';
                    $title = '';
                    if ($dir['has_forbidden']) {
                        $glyphs = $glyphs . WARNING_GLYPH;
                        $title = $title . 'Ce dossier contient un fichier avec une extension interdite';
                    }
                    if ($dir['is_empty']) {
                        $glyphs = $glyphs . ' ' . EMPTY_GLYPH;
                        $title = $title . ' Ce dossier est vide';
                    }
                    if ($dir['has_spaces']) {
                        $glyphs = $glyphs . ' ' . SPACE_GLYPH;
                        $title = $title . ' Ce fichier a un espace dans son nom . Merci de corriger cela :) ';
                    }
                ?>
                    <li class='file-info'>
                        <a href="<?= $dir['path'] ?>"><?= $dir['name'] ?></a>
                        <span class="glyphs" title="<?= $title ?>"><?= $glyphs ?></span>
                        <span class="size"><?= $dir['size'] ?></span>
                        <span class="date"><?= $dir['last_modified'] ?></span>
                    </li>
                <?php endforeach ?>
            </ul>
        </nav>


        <?php
        // Display the content of index.md if it exists
        $mdindex = $fileHandler->hasMDIndex($currentdir);
        if ($mdindex) : ?>
            <div class="markdown">
                <?= $Parsedown->text(file_get_contents($mdindex)) ?>
            </div>
        <?php endif ?>
    </main>
</body>

</html>