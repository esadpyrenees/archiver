<?php

require_once __DIR__ . '/FileHandler.php';
require_once __DIR__ .  '/CacheManager.php';

/**
 * Class DirectoryNavigator
 * Gère la navigation dans un repertoire donné
 */

class DirectoryNavigator
{
  private $fileHandler;
  private $currentDir;


  /**
   * Constructeur de la classe DirectoryNavigator
   * @param string $baseDir le repertoire de base à explorer.
   * @param string $params Le chemin relatif
   */
  public function __construct($baseDir, $params)
  {
    $this->fileHandler = new FileHandler();
    $this->currentDir = rtrim($baseDir, '/') . '/' . ltrim($params, '/');
  }

  /**
   * Summary of getFilesAndFolders
   * Récupère la liste des fichiers et dossiers du repertoire 
   * @return array la liste des fichiers et dossiers du repertoire 
   */
  public function getFilesAndFolders()
  {
    return $this->fileHandler->listDirectory($this->currentDir);
  }
}
