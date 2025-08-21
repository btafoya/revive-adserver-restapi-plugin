<?php
/**
 * Package script for creating Revive Adserver plugin releases
 * 
 * This script creates a properly formatted zip file for Revive plugin distribution
 */

function createPluginPackage($version = null) {
    $baseDir = dirname(__DIR__);
    $pluginName = 'reviveRestApi';
    
    // Read current version from plugin.xml if not provided
    if (!$version) {
        $pluginXml = file_get_contents($baseDir . '/plugin.xml');
        if (preg_match('/<version>(.*?)<\/version>/', $pluginXml, $matches)) {
            $version = $matches[1];
        } else {
            throw new Exception('Could not determine version from plugin.xml');
        }
    }
    
    $packageDir = $baseDir . '/build';
    $pluginDir = $packageDir . '/' . $pluginName;
    $zipFile = $packageDir . '/' . $pluginName . '-' . $version . '.zip';
    
    // Clean and create build directory
    if (is_dir($packageDir)) {
        exec("rm -rf " . escapeshellarg($packageDir));
    }
    mkdir($packageDir, 0755, true);
    mkdir($pluginDir, 0755, true);
    
    // Files and directories to include
    $includes = [
        'src/',
        'www/',
        'scripts/',
        'plugin.xml',
        'routes.addendum.php',
        'README.md',
        'CHANGELOG.md',
        'composer.json'
    ];
    
    // Copy files to package directory
    foreach ($includes as $item) {
        $source = $baseDir . '/' . $item;
        $dest = $pluginDir . '/' . $item;
        
        if (is_dir($source)) {
            exec("cp -r " . escapeshellarg($source) . " " . escapeshellarg($dest));
        } elseif (file_exists($source)) {
            copy($source, $dest);
        }
    }
    
    // Copy production vendor directory if it exists
    $vendorDir = $baseDir . '/vendor';
    if (is_dir($vendorDir)) {
        exec("cp -r " . escapeshellarg($vendorDir) . " " . escapeshellarg($pluginDir . '/vendor'));
    }
    
    // Create zip file
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception('Cannot create zip file: ' . $zipFile);
    }
    
    // Add files to zip
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $filePath = $file->getPathname();
        $relativePath = $pluginName . '/' . substr($filePath, strlen($pluginDir) + 1);
        
        if ($file->isDir()) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    $zip->close();
    
    echo "Package created: " . $zipFile . "\n";
    echo "Plugin directory structure:\n";
    exec("cd " . escapeshellarg($packageDir) . " && find " . escapeshellarg($pluginName) . " -type f | head -20");
    
    return $zipFile;
}

// Run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $version = $argv[1] ?? null;
        createPluginPackage($version);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}