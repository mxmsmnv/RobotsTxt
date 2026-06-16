# RobotsTxt

ProcessWire module for managing the `robots.txt` file through the admin UI — no FTP or command line required.

**Author:** Maxim Semenov  
**Website:** [smnv.org](https://smnv.org)  
**Email:** [maxim@smnv.org](mailto:maxim@smnv.org)

If this project helps your work, consider supporting future development: [GitHub Sponsors](https://github.com/sponsors/mxmsmnv) or [smnv.org/sponsor](https://smnv.org/sponsor/).  

## Requirements

- PHP 8.2+
- ProcessWire 3.0.200+
- Write permission on the site root directory

## Installation

1. Copy the `RobotsTxt` folder to `/site/modules/`
2. In the admin go to **Modules → Refresh**
3. Find **RobotsTxt** and click **Install**
4. The module appears under **Setup → Robots.txt**

### File permissions

The web server user must be able to write to the site root. Check with:

```bash
ls -la /var/www/yoursite/robots.txt
# or, if the file does not exist yet:
ls -la /var/www/yoursite/
```

If needed:

```bash
# Allow writing a new file
chmod 775 /var/www/yoursite/

# Fix an existing file
chmod 664 /var/www/yoursite/robots.txt
chown www-data:www-data /var/www/yoursite/robots.txt
```

## Features

- **Visual editor** with dark theme and monospace font
- **Two-column layout** — preset sidebar on the left, editor on the right
- **10 built-in rule presets** — click any card to append its rules to the editor:
  - Allow all crawlers
  - Block all crawlers (useful during development)
  - Hide admin panel (`/admin/`, `/processwire/`)
  - Block uploaded file assets
  - Block AI training bots (GPTBot, CCBot, anthropic-ai, Google-Extended, FacebookBot, Omgilibot)
  - Add Sitemap reference
  - Set Crawl-delay
  - Block URL query strings
  - Google only
  - Bing only
- **Rules overview** — parses the file after saving and renders a human-readable table with color-coded badges
- **Automatic path detection** — resolves `robots.txt` via `$config->paths->root`, the directory that contains `/site` and `/wire`
- **File locking** — uses `LOCK_EX` on write to prevent corruption from concurrent saves
- **CSRF protection** on all form submissions

## Usage

Open **Setup → Robots.txt** in the admin.

- If `robots.txt` already exists, its contents are loaded into the editor.
- If it does not exist yet, a warning banner shows the target path — fill in the editor and click **Save** to create the file.
- Click any preset card in the sidebar to append its rules to the editor.
- Click **Save** to write the file.
- Click **View file ↗** to open the live `robots.txt` in a new tab.

## License

MIT License. Free to use in personal and commercial projects.

## Author

Maxim Semenov — [smnv.org](https://smnv.org) · maxim@smnv.org
