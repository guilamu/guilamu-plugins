# Guilamu's WordPress Plugins

Discover, install, and manage all of Guilamu's WordPress plugins directly from your WordPress admin dashboard.

## Plugin Dashboard

- Browse all plugins from Guilamu's GitHub account in a single view
- Filter by status (All, Activated, Installed, Not Installed) and category (Gravity Forms, General)
- Search by plugin name or description in real time
- View the GitHub repository for any plugin with a single click

## Install & Manage

- Install any listed plugin directly from WordPress admin — no FTP or manual download needed
- Activate or deactivate plugins with a toggle switch on each card
- Delete inactive plugins from the card header
- Refresh the plugin list on demand to pick up newly published GitHub repos

## Auto-Detection

- New Guilamu plugins with the `wordpress-plugin` GitHub topic appear automatically
- Plugins with the `gravity-forms` topic are categorized under Gravity Forms
- Installed plugins are detected locally even if the GitHub API is unavailable

## Key Features

- **Dashboard:** Full-width plugin grid with card-based layout
- **GitHub Updates:** Automatic updates from GitHub releases via the standard WordPress update screen
- **Bug Reporting:** Per-card bug flag integrates with [guilamu-bug-reporter](https://github.com/guilamu/guilamu-bug-reporter) when active
- **Multilingual:** Works with content in any language
- **Translation-Ready:** All strings are internationalized; French (fr_FR) included
- **Secure:** Nonce-verified AJAX, capability checks, and input sanitization on all actions
- **Lightweight:** Pure PHP admin — no React, no build step required

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- A public internet connection (GitHub API calls use 12-hour transient caching)

## Installation

1. Upload the `guilamu-plugins` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Guilamu's Plugins** in the main admin menu
4. Browse, install, and manage plugins from the dashboard

## FAQ

### How are plugins detected?

Any public GitHub repository under the `guilamu` account that has the `wordpress-plugin` topic is automatically listed. Use the `Refresh` button to pick up newly published repos.

### How do I categorize a plugin as a Gravity Forms plugin?

Add the `gravity-forms` GitHub topic to the repository. It will appear under the Gravity Forms category on the next refresh.

### How do GitHub updates work?

The plugin uses the `Update URI` header and the `update_plugins_github.com` WordPress filter. When WordPress checks for updates, it fetches the latest GitHub release, compares versions, and shows an update notification in **Dashboard → Updates** if a newer version exists.

### Where can I report bugs?

Install and activate [guilamu-bug-reporter](https://github.com/guilamu/guilamu-bug-reporter). A flag icon will appear on each installed plugin's card, opening the bug report modal directly.

### Can I use this on a site without internet access?

The dashboard falls back gracefully if the GitHub API is unreachable — already-installed plugins remain visible and manageable, but descriptions and new plugin discovery will be unavailable until connectivity is restored.

## Project Structure

```
.
├── guilamu-plugins.php           # Bootstrap file, plugin header and init
├── uninstall.php                 # Cleanup transients on plugin deletion
├── README.md
├── assets/
│   ├── css/
│   │   └── admin.css             # Dashboard styles
│   └── js/
│       └── admin.js              # Search, filters, and AJAX actions
├── includes/
│   ├── class-github-api.php      # GitHub API wrapper with 12-hour caching
│   ├── class-github-updater.php  # GitHub auto-updates via WP update screen
│   └── class-guilamu-plugins.php # Admin menu, rendering, AJAX handlers
└── languages/
    ├── guilamu-plugins-fr_FR.mo  # French translation (binary)
    ├── guilamu-plugins-fr_FR.po  # French translation (source)
    └── guilamu-plugins.pot       # Translation template
```

## Changelog

### 1.0.2
- Added "View details" thickbox link on the Plugins page
- Rewritten GitHub updater: README.md-based plugin info popup with Description, Installation, FAQ, and Changelog tabs
- Added Parsedown dependency for reliable Markdown-to-HTML conversion
- CSS injection and div-based table rendering for wp_kses compatibility
- Added required `id`, `slug`, `plugin` fields to update response for WP core compatibility

### 1.0.1
- Removed hardcoded GF_SLUGS fallback list; plugin category badges now rely entirely on GitHub repository topics (`gravity-forms`, `wordpress-plugin`)

### 1.0.0
- Initial release
- Plugin dashboard with card grid, search, and dropdown filters
- Install, activate, deactivate, and delete plugins via AJAX
- GitHub API integration with topic-based auto-detection
- GitHub auto-update support via `Update URI` header
- Per-card bug reporting integration with guilamu-bug-reporter
- French (fr_FR) translation included

## License

This project is licensed under the GNU Affero General Public License v3.0 (AGPL-3.0) - see the [LICENSE](LICENSE) file for details.

---

<p align="center">
  Made with love for the WordPress community
</p>
