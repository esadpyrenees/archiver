<?php

/**
 * Récupère la liste des fichiers et dossiers d'un répertoire donné.
 *
 * @param string $path Chemin absolu du répertoire à explorer.
 * @return array Tableau contenant les fichiers et dossiers (associatif avec leurs informations).
 */
class FileHandler
{
  private $cool_extensions = ['jpg', 'png', 'pdf', 'gif', 'webp', 'html', 'zip', 'css', 'js'];
  private $forbidden_extensions = ['psd', 'tif', 'tiff', 'ai', 'indd'];


  /**
   * Summary of listDirectory
   * @param string $path Chemin du repertoire a explorer.
   * retourne un tableau de tous les fichiers et dossiers contenus dans le repertoire
   */
  public function listDirectory($path)
  {
    if (!is_dir($path)) return [];

    $results = [];
    if (!is_dir($path)) return $results;

    foreach (new DirectoryIterator($path) as $fileinfo) {
      if ($fileinfo->isDot()) continue;
      if (str_starts_with($fileinfo->getFilename(), ".")) continue;
      if ($fileinfo->getExtension() == 'md') continue;

      if ($fileinfo->isDir()) {
        $results[] = $this->processDirectory($fileinfo);
      } else {
        $results[] = $this->processFile($fileinfo);
      }
    }
    return $results;
  }

  /**
   * Summary of processDirectory
   * @param DirectoryIterator $fileinfo qui est un objet de la classe DirectoryIterator représentant le dossier
   * @return array Informations sur le dossier en paramètre
   */
  private function processDirectory($fileinfo)
  {
    $folderPath = $fileinfo->getPathname();
    $folderInfo = CacheManager::getCachedFolderInfo($folderPath);

    // Check for index in this directory
    $pathSuffix = fileHandler::getPathSuffix($folderPath);

    return [
      'path' => $fileinfo->getFilename() . $pathSuffix,
      'name' => $fileinfo->getFilename(),
      'is_empty' => $this->isEmpty($folderPath),
      'size' => $this->formatSize($folderInfo['size']),
      'last_modified' => $folderInfo['last_modified'],
      'has_forbidden' => $this->containsForbiddenFiles($folderPath),
      'has_spaces' => $this->containsSpace($folderPath),
    ];
  }


  /**
   * Summary of processFile
   * @param DirectoryIterator $fileinfo qui est un objet de la classe DirectoryIterator représentant le fichier.
   * @return array Informations sur le fichier en paramètre
   */
  private function processFile($fileinfo)
  {
    return [
      'path' => $fileinfo->getFilename(),
      'name' => $fileinfo->getFilename(),
      'is_empty' => false,
      'size' => $this->formatSize($fileinfo->getSize()),
      'last_modified' => date('Y-m-d H:i:s', $fileinfo->getMTime()),
      'has_forbidden' => false,   //in_array($fileinfo->getExtension(), $this->forbidden_extensions),
      'has_spaces' => false,
    ];
  }



  /**
   * Summary of hasIndex
   * @param string $dir Chemin du dossier
   * @return string|false Nom du fichier index s'il existe , false sinon
   */
  public static function  getPathSuffix($dir)
  {
    $indexFiles = ["index.html", "index.php", "index.htm"];
    foreach ($indexFiles as $indexFile) {
      $indexPath = $dir . '/' . $indexFile;
      if (file_exists($indexPath)) {
        return '/' . $indexFile;
      }
    }
    return '/';
  }

  /**
   * Summary of hasMDIndex
   * @param string $dir Chemin du dossier
   * @return string|false Chemin du fichier index.md s'il existe , false sinon 
   */
  public function hasMDIndex($dir)
  {
    foreach (glob($dir . '/index.md', GLOB_BRACE) as $file) {
      return $file;
    }
    return false;
  }

  /**
   * Summary of isEmpty
   * @param string $dir chemin du dossier
   * @return bool True si le dossier est vide , retourne false sinon
   */
  public function isEmpty($dir)
  {
    return !(new FilesystemIterator($dir))->valid();
  }

  /**
   * Summary of hasSubDirectories
   * @param string $dir chemin du dossier
   * @return bool true si le dossier a des sous dossiers , false sinon
   */
  public function hasSubDirectories($dir)
  {
    foreach (new DirectoryIterator($dir) as $fileinfo) {
      if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        return true;
      }
    }
    return false;
  }

  /**
   * Summary of containsForbiddenFiles
   * Verifie la présence de fichiers interdits dans un dossier
   * @param string $folderPath 
   * @return bool True si les fichiers interdits sont présents, retourne false sinon
   */
  private function containsForbiddenFiles($folderPath)
  {
    foreach (new DirectoryIterator($folderPath) as $fileinfo) {
      if ($fileinfo->isDot()) continue;
      if (in_array($fileinfo->getExtension(), $this->forbidden_extensions)) return true;
    }
    return false;
  }

  /**
   * Summary of containsSpace
   * Vérifie la présence de fichier contenant des espaces , accents ou caractères spéciaux dans un dossier 
   * @param string $folderPath 
   * @return bool True si les espaces sont présents ,  accents ou catactères spéciaux sont présents , false sinon
   */
  private function containsSpace($folderPath)
  {
    foreach (new DirectoryIterator($folderPath) as $fileinfo) {
      if ($fileinfo->isDot()) continue;
      if (preg_match('/(?![a-zA-Z0-9\_\-\.]).+$/', $fileinfo->getFilename())) {
        return true;
      }
    }
    return false;
  }


  /**
   * Summary of formatSize
   * Formate la taille d'un fichier pour plus de lisibilité
   * @param int $bytes Taille en octets
   * @return string Taille formatée
   */
  private function formatSize($bytes)
  {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $bytes /= 1024, $i++);
    return round($bytes, 2) . ' ' . $units[$i];
  }
}
