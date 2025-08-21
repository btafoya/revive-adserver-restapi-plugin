# Changelog

All notable changes to the Revive Adserver REST API Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0/).

## [Unreleased]

## [1.0.6] - 2025-08-21

### Added
- Automated CHANGELOG.md updates in release script
- CHANGELOG.md now automatically updated with version and date during releases

### Fixed
- GitHub Actions permissions for release workflow (403 Forbidden error)
- JavaScript syntax error in TargetingValidator.php (.append() → [] array syntax)
- Class redeclaration error in ReviveConfig.php during test coverage
- Database connection failures in CI environment
- Missing return statements in RuleSetsController JSON responses

### Changed
- Release script now includes CHANGELOG.md in automated version updates
- Improved test environment detection and database mocking

## [1.0.0] - 2025-08-20

### Added
- Initial public release of the Revive Adserver REST API Plugin.
- **Campaigns / Banners / Zones**: CRUD endpoints with consistent JSON responses.
- **Banner uploads**: multipart file upload pipeline with validation and type/size limits.
- **Targeting system** with groups (all/any/none), lists (`IN` semantics), and time helpers (DayOfWeek, HourOfDay ranges).
- **Reusable Rule Sets**: CRUD for stored targeting rule sets.
- **Bulk apply**: `POST /api/v1/rule-sets/{id}/apply` with `replace|merge` mode.
- **Ad‑hoc multi‑banner apply**: `POST /api/v1/banners/apply` (raw `rules` or `ruleSetId`). 
- **Validator** (dry‑run): `POST /api/v1/targeting/validate` → compiled string, normalized tree, ACL preview, warnings; no DB writes.
- **Preview**: `GET /api/v1/rule-sets/{id}/preview`.
- **Export/Import** of rule sets.
- **Schema introspection**: `GET /api/v1/targeting/schema` for UI generation.
- **Named variables helper** for `Site:Variable` via `kv` shape and formatter endpoint.
- **Stats endpoints** for campaigns, banners, and zones.
- **Health check** endpoint.
- **Auth**: session and token support; pluggable middleware.
- **Security**: CORS, rate limiting, input sanitization, and security logging.
- **Compatibility**: Works with https://github.com/btafoya/revive-adserver-mcp and Revive Adserver 5.x.

### Changed
- Normalized targeting compiler to generate parenthesized expressions with deterministic ordering.
- Consistent error format across all controllers and middleware.

### Fixed
- Safer JSON encoding/decoding for ACL `data` payloads.
- Transactional writes to prevent partial ACL states on failure.

### Notes
- Requires PHP 7.4+ and Revive Adserver 5.x.
- See `README.md` for route list and usage examples.
