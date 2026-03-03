# Changelog

All notable changes to RobotsTxt will be documented in this file.

## [1.0.0] — 2026-03-03

Initial release.

### Added
- Visual editor with dark theme and monospace font
- Two-column layout: preset sidebar on the left, editor on the right
- 10 built-in rule presets (allow all, block all, hide admin, block assets, block AI bots, sitemap, crawl-delay, block query strings, Google only, Bing only)
- Rules overview panel — parses `robots.txt` and renders a human-readable table with color-coded Allow/Disallow/Crawl-delay badges
- Automatic `robots.txt` path detection via `$config->paths->root`
- File write with `LOCK_EX` to prevent corruption on concurrent saves
- `is_file()` check in reader to guard against edge cases
- CSRF protection on all form submissions
- Redirect after successful save to prevent duplicate POST on reload
- Status bar showing file path, size, and last-modified date
- Warning banner when the file does not exist yet
- "View file ↗" link to open the live `robots.txt` in a new tab
- "Clear editor" button with confirmation dialog
- UIkit-based UI using ProcessWire's native admin styles
- Separate `robots-manager` permission
