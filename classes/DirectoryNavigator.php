<?php

require_once 'FileHandler.php';
require_once 'CacheManager.php';

class DirectoryNavigator
{
  private $fileHandler;
  private $currentDir;

  public function __construct($baseDir, $params)
  {
    $this->fileHandler = new FileHandler();
    $this->currentDir = rtrim($baseDir, '/') . '/' . ltrim($params, '/');
  }

  public function getFilesAndFolders()
  {
    return $this->fileHandler->listDirectory($this->currentDir);
  }
}
