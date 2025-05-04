<?php
// update_cache.php

require_once __DIR__ . '/CacheManager.php';

// Get the target directory from CLI args or default path
$archivesdir = dirname(__FILE__) . "../archives";
$targetDir = $argv[1] ?? $archivesdir;

// Sanitize input
$targetDir = realpath($targetDir);
if (!$targetDir || !is_dir($targetDir)) {
    echo "Invalid directory: $targetDir\n";
    exit(1);
}

echo "Updating cache for: $targetDir\n";

try {
    $results = CacheManager::processDirectoryTree($targetDir);
    echo "Processed " . count($results) . " folders.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}