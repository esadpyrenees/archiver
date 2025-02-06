<?php
include '_inc/Parsedown.php';
include '_inc/ParsedownExtra.php';
include '_inc/ParsedownExtraPlugin.php';
require_once __DIR__ . '/classes/DirectoryNavigator.php';
require_once __DIR__ .  '/classes/FileHandler.php';

//On définit l'url racine
$root_url =  str_replace("index.php", "", $_SERVER['SCRIPT_NAME']);

// markdown!
$Parsedown = new ParsedownExtraPlugin();
$Parsedown->figuresEnabled = true;


//On récupère les paramètres de l'URL
$params = '';
if (isset($_GET['params'])) {
    $params = '/' . $_GET['params'];
}

//On définit les chemins des repertoires dans lesquels naviguer
$rootdir = dirname(__FILE__);
$archivesdir = $rootdir . "/archives";
$currentdir = $archivesdir . $params;

//On définit les constantes pour les différents glyphes 
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

//On initialise un objet de la classe DirectoryNavigator
$navigator = new DirectoryNavigator($archivesdir, $params);
$results = $navigator->getFilesAndFolders();
rsort($results);

//On initialise un objet de la classe FileHandler
$fileHandler = new FileHandler();

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
                        $title = $title . ' Ce dosser contient un fichier qui a un espace , un accent ou un caractère spécial dans son nom . Merci de corriger cela :) ';
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
        // Affiche le contenu du fichier index.md s'il existe
        $mdindex = $fileHandler->hasMDIndex($currentdir);
        if ($mdindex) : ?>
            <div class="markdown">
                <?= $Parsedown->text(file_get_contents($mdindex)) ?>
            </div>
        <?php endif ?>
    </main>
</body>

</html>