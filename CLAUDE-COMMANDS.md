# Claude Code Commands

This file documents the automated commands available for this project when using Claude Code.

## Release Commands

### Single Command Release
```bash
# Complete automated release (recommended)
./release 1.2.3

# With custom commit message  
./release 1.2.4 "Add new API endpoints"
```

### Component Commands
```bash
# Just update version and create tag
php scripts/version-bump.php 1.2.3 --tag

# Create package only
php scripts/package.php

# Test package creation
composer package
```

### Composer Shortcuts
```bash
# Complete release
composer release 1.2.3

# Individual operations
composer package
composer version-bump 1.2.3
```

## What the Automated Release Does

1. **🔍 Checks git status** - Stages any uncommitted changes
2. **📝 Updates versions** - plugin.xml and composer.json
3. **💾 Commits changes** - With proper Claude Code attribution
4. **🏷️ Creates git tag** - In format `v1.2.3`
5. **⬆️ Pushes to GitHub** - Main branch and tag
6. **🚀 Triggers release** - GitHub Actions creates zip and release

## GitHub Actions Workflow

The automated release triggers a GitHub Actions workflow that:

- ✅ Runs all tests
- 📦 Creates properly formatted `reviveRestApi-X.Y.Z.zip`
- 🎯 Publishes GitHub release with download
- 📋 Includes changelog and installation instructions

## File Structure Created

```
reviveRestApi-1.2.3.zip
└── reviveRestApi/
    ├── src/
    ├── www/
    ├── scripts/
    ├── plugin.xml       # Updated version
    ├── routes.addendum.php
    ├── README.md
    ├── CHANGELOG.md
    └── composer.json    # Updated version
```

## Monitoring Release

After running `./release X.Y.Z`, monitor:

- **Actions**: https://github.com/btafoya/revive-adserver-restapi-plugin/actions
- **Releases**: https://github.com/btafoya/revive-adserver-restapi-plugin/releases

## Claude Code Integration

These commands are optimized for Claude Code workflows:

- ✅ Proper git attribution with Claude Code signatures
- ✅ Atomic operations with rollback on failure
- ✅ Clear status reporting and error handling
- ✅ Intelligent change detection and staging
- ✅ Professional commit message formatting

## Troubleshooting

If release fails:

1. **Check git status**: `git status`
2. **Check tags**: `git tag -l`
3. **Check remote**: `git remote -v`
4. **Manual cleanup**: `git tag -d vX.Y.Z` if tag created but push failed

## Version Strategy

- **Patch**: `1.0.1` - Bug fixes, minor updates
- **Minor**: `1.1.0` - New features, backward compatible
- **Major**: `2.0.0` - Breaking changes

Use the single command for all releases: `./release X.Y.Z`