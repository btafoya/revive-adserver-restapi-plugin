<?php
/**
 * Complete automated release script for Claude Code integration
 * Handles version bumping, git operations, and release publishing
 */

function autoRelease($version, $message = null) {
    $baseDir = dirname(__DIR__);
    
    echo "🚀 Starting automated release for version $version\n";
    
    // Validate version format
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
        throw new Exception('Version must be in format X.Y.Z (e.g., 1.2.3)');
    }
    
    // Check git status
    echo "📋 Checking git status...\n";
    exec('git status --porcelain', $statusOutput, $statusCode);
    if (!empty($statusOutput)) {
        echo "⚠️  Working directory has changes. Staging all changes...\n";
        exec('git add .', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new Exception('Failed to stage changes');
        }
        
        // Auto-commit changes if any
        $commitMessage = $message ?: "Prepare for release $version";
        exec('git commit -m ' . escapeshellarg($commitMessage), $output, $returnCode);
        if ($returnCode !== 0) {
            echo "ℹ️  No changes to commit or commit failed\n";
        } else {
            echo "✅ Committed changes: $commitMessage\n";
        }
    }
    
    // Update version in plugin.xml
    echo "📝 Updating plugin.xml version to $version...\n";
    $pluginXmlPath = $baseDir . '/plugin.xml';
    $pluginXml = file_get_contents($pluginXmlPath);
    $updatedXml = preg_replace(
        '/<version>.*?<\/version>/',
        '<version>' . $version . '</version>',
        $pluginXml
    );
    
    if ($updatedXml === $pluginXml) {
        throw new Exception('Failed to update version in plugin.xml');
    }
    
    file_put_contents($pluginXmlPath, $updatedXml);
    
    // Update composer.json version
    $composerJsonPath = $baseDir . '/composer.json';
    if (file_exists($composerJsonPath)) {
        $composer = json_decode(file_get_contents($composerJsonPath), true);
        $composer['version'] = $version;
        file_put_contents($composerJsonPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
    
    // Stage version files
    echo "📦 Staging version files...\n";
    exec('git add plugin.xml composer.json', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Failed to stage version files');
    }
    
    // Commit version bump
    echo "💾 Committing version bump...\n";
    $versionCommitMsg = "Bump version to $version

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>";
    
    exec('git commit -m ' . escapeshellarg($versionCommitMsg), $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Failed to commit version bump');
    }
    
    // Create git tag
    echo "🏷️  Creating git tag v$version...\n";
    $tag = 'v' . $version;
    
    // Check if tag already exists
    exec("git tag -l " . escapeshellarg($tag), $tagOutput, $returnCode);
    if (!empty($tagOutput)) {
        throw new Exception("Tag $tag already exists");
    }
    
    exec("git tag -a " . escapeshellarg($tag) . " -m 'Release version $version'", $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception("Failed to create git tag");
    }
    
    // Push main branch
    echo "⬆️  Pushing to main branch...\n";
    exec('git push origin main', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Failed to push main branch');
    }
    
    // Push tag to trigger release
    echo "🚀 Pushing tag to trigger automated release...\n";
    exec("git push origin $tag", $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Failed to push tag');
    }
    
    // Success message
    echo "\n✅ Release $version completed successfully!\n";
    echo "🎯 GitHub Actions workflow triggered by tag: $tag\n";
    echo "📦 Release will be available at: https://github.com/btafoya/revive-adserver-restapi-plugin/releases\n";
    echo "⏱️  Check workflow progress: https://github.com/btafoya/revive-adserver-restapi-plugin/actions\n\n";
    
    return $tag;
}

function showUsage() {
    echo "Usage: php release.php <version> [commit-message]\n";
    echo "Examples:\n";
    echo "  php release.php 1.2.3\n";
    echo "  php release.php 1.2.4 \"Add new API endpoints\"\n";
    echo "\nThis script will:\n";
    echo "  1. Stage and commit any pending changes\n";
    echo "  2. Update plugin.xml and composer.json versions\n";
    echo "  3. Commit version changes\n";
    echo "  4. Create git tag\n";
    echo "  5. Push to main branch\n";
    echo "  6. Push tag to trigger GitHub Actions release\n";
}

// Command line interface
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if (empty($argv[1])) {
        showUsage();
        exit(1);
    }
    
    try {
        $version = $argv[1];
        $message = $argv[2] ?? null;
        
        autoRelease($version, $message);
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}