<?php

class FileHandler
{
  private $cool_extensions = ['jpg', 'png', 'pdf', 'gif', 'webp', 'html', 'zip', 'css', 'js'];
  private $forbidden_extensions = ['psd', 'tif', 'tiff', 'ai', 'indd'];

  public function listDirectory($path)
  {
    $results = [];
    if (!is_dir($path)) return $results;

    foreach (new DirectoryIterator($path) as $fileinfo) {
      if ($fileinfo->isDot()) continue;
      if (str_starts_with($fileinfo->getFilename(), ".")) continue;
      if ($fileinfo->getExtension() == 'md') continue;

      if ($fileinfo->isDir()) {
        $folderPath = $fileinfo->getPathname();
        $folderInfo = CacheManager::getCachedFolderInfo($folderPath);
        $has_forbidden = $this->containsForbiddenFiles($folderPath);
        $html_file = $this->getHtmlFile($folderPath);
        $has_spaces = $this->containsSpace($fileinfo->getFilename());


        $results[] = [
          'path' => $fileinfo->getFilename() . '/',
          'name' => $fileinfo->getFilename(),
          'is_empty' => $this->isEmpty($folderPath),
          'size' => $this->formatSize($folderInfo['size']),
          'last_modified' => $folderInfo['last_modified'],
          'has_forbidden' => $has_forbidden,
          'has_html' => $html_file,
          'has_spaces' => $has_spaces
        ];
      } else {
        $is_forbidden = in_array($fileinfo->getExtension(), $this->forbidden_extensions);
        $has_spaces = $this->containsSpace($fileinfo->getFilename());
        $results[] = [
          'path' => $fileinfo->getFilename(),
          'name' => $fileinfo->getFilename(),
          'is_empty' => false,
          'size' => $this->formatSize($fileinfo->getSize()),
          'last_modified' => date('Y-m-d H:i:s', $fileinfo->getMTime()),
          'has_forbidden' => $is_forbidden,
          'has_html' => false,
          'has_spaces' => $has_spaces
        ];
        if ($fileinfo->getExtension() == 'html') {
          $result['has_html'] = true;
        }
      }
    }
    return $results;
  }

  public function hasIndex($dir)
  {
    $s = scandir($dir);
    $fs = array_diff($s, array(".", ".."));
    foreach ($fs as $f) {
      if (in_array($f, ["index.html", "index.php", "index.htm"])) {
        return $f;
      }
    }
    return false;
  }

  public function hasMDIndex($dir)
  {
    foreach (glob($dir . '/index.md', GLOB_BRACE) as $file) {
      return $file;
    }
    return false;
  }

  public function hasHTMLIndex($dir)
  {
    foreach (glob($dir . '/index.html', GLOB_BRACE) as $file) {
      // Redirection vers le fichier index.html trouvé
      $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath($file));
      $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($relativePath, '/');
      header('Location: ' . $url);
      exit; // Assurez-vous de terminer le script après la redirection
    }
    return false;
  }

  public function isEmpty($dir)
  {
    return !(new \FilesystemIterator($dir))->valid();
  }

  public function hasSubDirectories($dir)
  {
    foreach (new \DirectoryIterator($dir) as $fileinfo) {
      if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        return true;
      }
    }
    return false;
  }

  private function containsForbiddenFiles($folderPath)
  {
    foreach (new \DirectoryIterator($folderPath) as $fileinfo) {
      if ($fileinfo->isDot()) continue;
      if (in_array($fileinfo->getExtension(), $this->forbidden_extensions)) return true;
    }
    return false;
  }

  private function containsSpace($filename)
  {
    if (str_contains($filename, ' ')) {
      return true;
    } else {
      return false;
    }
  }


  private function getHtmlFile($folderPath)
  {
    foreach (new \DirectoryIterator($folderPath) as $fileinfo) {
      if ($fileinfo->isDot()) continue;
      if ($fileinfo->getExtension() == 'html') return $fileinfo->getFilename();
    }
    return false;
  }

  private function formatSize($bytes)
  {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $bytes /= 1024, $i++);
    return round($bytes, 2) . ' ' . $units[$i];
  }
}
