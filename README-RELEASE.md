# Release Process

This document describes how to create and publish releases for the Revive REST API plugin.

## Automated Release Process

The project uses GitHub Actions for automated releases. When you push a version tag, it automatically:

1. Runs tests to ensure quality
2. Updates plugin.xml with the correct version
3. Creates a properly formatted zip file for Revive Adserver
4. Publishes a GitHub release with the zip file attached

## Creating a Release

### Method 1: Using Scripts (Recommended)

```bash
# Bump version and create git tag
composer run-script -- release 1.2.3

# Push the tag to trigger automated release
git push origin v1.2.3
```

### Method 2: Manual Process

```bash
# 1. Update version manually
php scripts/version-bump.php 1.2.3 --tag

# 2. Push the tag
git push origin v1.2.3
```

### Method 3: GitHub UI

1. Go to your repository on GitHub
2. Click "Releases" → "Create a new release"
3. Create a new tag (e.g., `v1.2.3`)
4. The workflow will run automatically

## Package Structure

The automated release creates a zip file with this structure:

```
reviveRestApi-1.2.3.zip
└── reviveRestApi/
    ├── src/
    ├── www/
    ├── scripts/
    ├── vendor/ (production dependencies)
    ├── plugin.xml
    ├── routes.addendum.php
    ├── README.md
    ├── CHANGELOG.md
    └── composer.json
```

## Manual Testing

You can test the package creation locally:

```bash
# Create package for current version
composer package

# Create package for specific version
php scripts/package.php 1.2.3
```

The package will be created in the `build/` directory.

## Installation Instructions for Users

Include these instructions in your release notes:

1. Download the `reviveRestApi-X.Y.Z.zip` file from the release
2. Extract it to your Revive Adserver plugins directory (usually `/plugins/`)
3. The plugin directory should be named exactly `reviveRestApi`
4. Enable the plugin in the Revive Adserver admin panel

## Troubleshooting

### Release Workflow Fails

1. Check the Actions tab in your GitHub repository
2. Ensure all tests are passing
3. Verify the tag format is `vX.Y.Z` (e.g., `v1.2.3`)

### Package Issues

1. Test locally with `composer package`
2. Check that all required files are included
3. Verify the plugin directory structure matches Revive requirements

### Version Conflicts

1. Ensure the new version is higher than the current version
2. Check that the git tag doesn't already exist
3. Verify plugin.xml has been updated correctly