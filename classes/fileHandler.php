<?php

class FileHandler
{
  private array $coolExtensions = ['jpg', 'png', 'pdf', 'gif', 'webp', 'html', 'zip', 'css', 'js'];
  private array $forbiddenExtensions = ['psd', 'tif', 'tiff', 'ai', 'indd'];

  /**
   * Vérifie si un dossier est vide
   */
  public function isDirEmpty(string $dir): bool
  {
    return !(new FilesystemIterator($dir))->valid();
  }

  /**
   * Vérifie si un fichier a une extension autorisée
   */
  public function isExtensionAllowed(string $fileName): bool
  {
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    return in_array($extension, $this->coolExtensions);
  }

  /**
   * Vérifie si un fichier est interdit
   */
  public function isExtensionForbidden(string $fileName): bool
  {
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    return in_array($extension, $this->forbiddenExtensions);
  }

  /**
   * Calcule la taille d’un dossier récursivement
   */
  public function calculateFolderSize(string $dir): int
  {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
      if ($file->isFile()) {
        $size += $file->getSize();
      }
    }
    return $size;
  }


  public function getLastModifiedDate(string $dir): string
  {
    date_default_timezone_set('Europe/Paris');
    $lastModified = 0;

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
      if ($file->isFile()) {
        $fileModified = $file->getMTime();
        if ($fileModified > $lastModified) {
          $lastModified = $fileModified;
        }
      }
    }

    return $lastModified ? date('Y-m-d H:i:s', $lastModified) : 'N/A';
  }


  function sizeFilter(int $bytes)
  {
    $label = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

    for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++);

    return (round($bytes, 2) . " " . $label[$i]);
  }
}
