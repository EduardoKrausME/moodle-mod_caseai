# Changelog

This changelog documents only the two most recent published GitHub releases. Each entry records changes verified by comparing the release tag with the immediately preceding published release. Later changes on `master` are excluded.

## 1.0.5 - 2026-10-15

### Changed

- Updated the GitHub Actions CI PostgreSQL service from version 16 to 17.
- Updated the MariaDB CI service from version 10 to 11.4, adjusting its startup environment variables and health-check command.
- Added the plugin icon at `pix/icon.png` and reorganized the SVG logo asset as `pix/monologo.svg`.
- Updated the plugin release identifier from `1.0.4` to `1.0.5` and its build number from `2026101200` to `2026101500`.

No changes to Case AI activity functionality were identified in this tag comparison.

[Compare 1.0.4...1.0.5](https://github.com/EduardoKrausME/moodle-mod_caseai/compare/1.0.4...1.0.5)

## 1.0.4 - 2026-10-12

### Changed

- Updated the PHP 8.3 CI matrix from `MOODLE_502_STABLE` (Moodle 5.2) to `MOODLE_503_STABLE` (Moodle 5.3); the Moodle 4.5 test entry was retained.
- Added a GitHub Actions checkout of `EduardoKrausME/moodle-local_ai_bridge` from `master` into `extra-plugins/ai_bridge`, and configured `moodle-plugin-ci install` with `--extra-plugins ./extra-plugins` to make the plugin dependency available during tests.
- Hardened the release workflow against shell injection by passing the head commit message through the `RELEASE_MESSAGE` environment variable instead of interpolating the message directly into shell commands.
- Updated the plugin release identifier from `1.0.3` to `1.0.4` and its build number from `2026100500` to `2026101200`.

No changes to Case AI activity functionality were identified in this tag comparison.

[Compare 1.0.3...1.0.4](https://github.com/EduardoKrausME/moodle-mod_caseai/compare/1.0.3...1.0.4)
