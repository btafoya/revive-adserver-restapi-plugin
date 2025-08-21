<?php
/**
 * Version bump script for updating plugin.xml and creating git tags
 */

function updateVersion($newVersion) {
    $baseDir = dirname(__DIR__);
    $pluginXmlPath = $baseDir . '/plugin.xml';
    $composerJsonPath = $baseDir . '/composer.json';
    
    // Validate version format
    if (!preg_match('/^\d+\.\d+\.\d+$/', $newVersion)) {
        throw new Exception('Version must be in format X.Y.Z (e.g., 1.2.3)');
    }
    
    // Update plugin.xml
    $pluginXml = file_get_contents($pluginXmlPath);
    $updatedXml = preg_replace(
        '/<version>.*?<\/version>/',
        '<version>' . $newVersion . '</version>',
        $pluginXml
    );
    
    if ($updatedXml === $pluginXml) {
        throw new Exception('Failed to update version in plugin.xml');
    }
    
    file_put_contents($pluginXmlPath, $updatedXml);
    echo "Updated plugin.xml to version $newVersion\n";
    
    // Update composer.json version (optional, for reference)
    if (file_exists($composerJsonPath)) {
        $composer = json_decode(file_get_contents($composerJsonPath), true);
        $composer['version'] = $newVersion;
        file_put_contents($composerJsonPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        echo "Updated composer.json to version $newVersion\n";
    }
    
    return true;
}

function createGitTag($version) {
    $tag = 'v' . $version;
    
    // Check if tag already exists
    exec("git tag -l " . escapeshellarg($tag), $output, $returnCode);
    if (!empty($output)) {
        throw new Exception("Tag $tag already exists");
    }
    
    // Create and push tag
    exec("git add plugin.xml composer.json", $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception("Failed to stage files");
    }
    
    exec("git commit -m 'Bump version to $version'", $output, $returnCode);
    if ($returnCode !== 0) {
        echo "Warning: Failed to commit version changes (files may already be committed)\n";
    }
    
    exec("git tag -a " . escapeshellarg($tag) . " -m 'Release version $version'", $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception("Failed to create git tag");
    }
    
    echo "Created git tag: $tag\n";
    echo "To push tag and trigger release, run: git push origin $tag\n";
    
    return $tag;
}

// Command line interface
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if (empty($argv[1])) {
        echo "Usage: php version-bump.php <version> [--tag]\n";
        echo "Example: php version-bump.php 1.2.3 --tag\n";
        exit(1);
    }
    
    try {
        $version = $argv[1];
        $createTag = in_array('--tag', $argv);
        
        updateVersion($version);
        
        if ($createTag) {
            createGitTag($version);
        }
        
        echo "Version bump completed successfully!\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}