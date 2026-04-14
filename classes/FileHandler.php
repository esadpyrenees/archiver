<?php

/**
 * Récupère la liste des fichiers et dossiers d'un répertoire donné.
 *
 * @param string $path Chemin absolu du répertoire à explorer.
 * @return array Tableau contenant les fichiers et dossiers (associatif avec leurs informations).
 */
class FileHandler
{
  private $forbidden_extensions = ['psd', 'tif', 'tiff', 'ai', 'indd'];

  /**
   * Liste les fichiers et dossiers d'un répertoire.
   *
   * @param string $path Chemin du répertoire à explorer
   * @return array
   */
  public function listDirectory($path)
  {
    $results = [];
    if (!is_dir($path)) return $results;

    foreach (new DirectoryIterator($path) as $fileinfo) {
      if ($fileinfo->isDot()) continue;
      if (substr($fileinfo->getFilename(), 0, 1) === ".") continue;
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
   * Construit les informations d'affichage pour un dossier.
   *
   * @param DirectoryIterator $fileinfo Entrée représentant un dossier
   * @return array
   */
  private function processDirectory($fileinfo)
  {
    $folderPath = $fileinfo->getPathname();
    $folderInfo = CacheManager::getCachedFolderInfo($folderPath);
    $directoryFlags = $this->collectDirectoryFlags($folderPath);

    // Vérifie la présence d'un index dans ce dossier.
    $pathSuffix = fileHandler::getPathSuffix($folderPath);

    return [
      'path' => $fileinfo->getFilename() . $pathSuffix,
      'name' => $fileinfo->getFilename(),
      'is_empty' => $directoryFlags['is_empty'],
      'size' => $this->formatSize($folderInfo['size']),
      'status' => $folderInfo['status'],
      'last_modified' => $folderInfo['last_modified'],
      'has_forbidden' => $directoryFlags['has_forbidden'],
      'has_spaces' => $directoryFlags['has_spaces'],
    ];
  }


  /**
   * Construit les informations d'affichage pour un fichier.
   *
   * @param DirectoryIterator $fileinfo Entrée représentant un fichier
   * @return array
   */
  private function processFile($fileinfo)
  {
    return [
      'path' => $fileinfo->getFilename(),
      'name' => $fileinfo->getFilename(),
      'is_empty' => false,
      'size' => $this->formatSize($fileinfo->getSize()),
      'last_modified' => date('Y-m-d H:i:s', $fileinfo->getMTime()),
      'has_forbidden' => false,
      'has_spaces' => false,
    ];
  }



  /**
   * Retourne le suffixe de navigation d'un dossier selon l'index présent.
   *
   * @param string $dir Chemin du dossier
   * @return string Suffixe '/' ou '/index.*'
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
   * Retourne le nom de l'index d'un dossier s'il existe.
   *
   * @param string $dir Chemin du dossier
   * @return string Nom du fichier index, ou chaîne vide
   */
  public function hasIndex($dir)
  {
    return ltrim($this->getPathSuffix($dir), '/');
  }

  /**
   * Retourne le chemin de `index.md` s'il existe dans le dossier.
   *
   * @param string $dir Chemin du dossier
   * @return string|false
   */
  public function hasMDIndex($dir)
  {
    foreach (glob($dir . '/index.md', GLOB_BRACE) as $file) {
      return $file;
    }
    return false;
  }

  /**
   * Parcourt le dossier une seule fois et calcule les indicateurs utilisés par l'UI.
   * Conserve le comportement existant tout en réduisant les accès disque.
   *
   * @param string $folderPath
   * @return array{is_empty: bool, has_forbidden: bool, has_spaces: bool}
   */
  private function collectDirectoryFlags($folderPath)
  {
    $isEmpty = true;
    $hasForbidden = false;
    $hasSpaces = false;

    foreach (new DirectoryIterator($folderPath) as $fileinfo) {
      if ($fileinfo->isDot()) {
        continue;
      }

      $isEmpty = false;

      if (!$hasForbidden && in_array($fileinfo->getExtension(), $this->forbidden_extensions, true)) {
        $hasForbidden = true;
      }

      if (!$hasSpaces && preg_match('/(?![a-zA-Z0-9\_\-\.]).+$/', $fileinfo->getFilename())) {
        $hasSpaces = true;
      }

      if ($hasForbidden && $hasSpaces) {
        break;
      }
    }

    return [
      'is_empty' => $isEmpty,
      'has_forbidden' => $hasForbidden,
      'has_spaces' => $hasSpaces,
    ];
  }


  /**
   * Formate une taille en octets dans une unité lisible.
   *
   * @param int $bytes Taille en octets
   * @return string
   */
  private function formatSize($bytes)
  {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $bytes /= 1024, $i++);
    return round($bytes, 2) . ' ' . $units[$i];
  }
}
