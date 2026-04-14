<?php
require_once __DIR__ . '/CacheManager.php';

$targetDir = $argv[1] ?? dirname(__FILE__) . "../archives";
$targetDir = realpath($targetDir);

// $logFile = sys_get_temp_dir() . '/cache_update_debug.log';
// $logFile = 'cache_update_debug.log';

if (!$targetDir || !is_dir($targetDir)) {
    // file_put_contents($logFile, "[ERROR] Invalid dir: $targetDir\n", FILE_APPEND);
    exit(1);
}

$lockFile = sys_get_temp_dir() . '/cache_update_global.lock';

// Log start
// file_put_contents($logFile, "[START] " . date('c') . " - $targetDir\n", FILE_APPEND);

try {
    CacheManager::processDirectoryTree($targetDir);
    // file_put_contents($logFile, "[DONE]  " . date('c') . " - $targetDir\n", FILE_APPEND);
} finally {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
