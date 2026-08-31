# Guilamu's WordPress Plugins

[![Latest Release](https://img.shields.io/github/v/release/guilamu/guilamu-plugins?color=blue)](https://github.com/guilamu/guilamu-plugins/releases) [![License: AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-green.svg)](LICENSE) [![WordPress: 5.8+](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org) [![PHP: 7.4+](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)

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

Any public GitHub repository under the `guilamu` account carrying the `wordpress-plugin`, `wordpress` or `gravity-forms` topic is listed automatically, as is any Guilamu plugin already installed on the site. Use the `Refresh` button to pick up newly published repos.

### How do I categorize a plugin as a Gravity Forms plugin?

Add the `gravity-forms` GitHub topic to the repository. It will appear under the Gravity Forms category on the next refresh.

### How do GitHub updates work?

The plugin uses the `Update URI` header and the `update_plugins_github.com` WordPress filter. When WordPress checks for updates, it fetches the latest GitHub release, compares versions, and shows an update notification in **Dashboard → Updates** if a newer version exists.

### Where can I report bugs?

Install and activate [guilamu-bug-reporter](https://github.com/guilamu/guilamu-bug-reporter). A flag icon appears on each installed plugin's card, and a **Report a Bug** link appears in this plugin's own row on the Plugins page.

### Can I use this on a site without internet access?

Yes. The last successful GitHub response is stored as an offline fallback, and locally installed Guilamu plugins are always listed, so installed plugins stay visible and manageable. Only the discovery of newly published plugins requires connectivity.

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

### 1.0.6 - 2026-08-31
- **New:** `gravity-forms` is now recognised as a WordPress plugin topic, so add-ons tagged only `gravity-forms` on GitHub are no longer missing from the list
- **New:** Registered with Guilamu Bug Reporter, adding a "Report a Bug" link to the plugin's own row on the Plugins page
- **New:** CSS pattern banner in the "View details" popup
- **Improved:** The GitHub repository list is now kept as an offline fallback, so the dashboard stays populated when the API is unreachable or rate-limited
- **Improved:** Locally installed Guilamu plugins absent from the GitHub response are now listed too
- **Fixed:** "View details" popup could fail with "Plugin not found" when another plugin's `plugins_api` filter discarded the payload
- **Fixed:** Empty popup footer when no GitHub release data was available
- **Changed:** Plugin header now declares AGPL-3.0, matching the `LICENSE` file and this README

### 1.0.5 - 2026-06-26
- **Fixed:** "View details" modal link for plugins where the directory/slug name differs from the text domain (e.g. simple-membership-manager)

### 1.0.4 - 2026-05-20
- **Fixed:** Activation toggle after installing a plugin from the dashboard no longer opens a stuck Thickbox loader
- **Fixed:** Removed the incorrect Thickbox rebinding on dynamically rendered plugin cards
- **Improved:** File-based cache busting for admin CSS and JS to avoid stale browser assets after updates

### 1.0.3 - 2026-05-09
- **New:** `View details` action in the plugin card footer
- **Improved:** Matched WordPress core plugin details modal behavior and close button handling

### 1.0.2 - 2026-03-31
- **New:** "View details" thickbox link on the Plugins page
- **New:** Parsedown dependency for reliable Markdown-to-HTML conversion
- **Improved:** Rewritten GitHub updater: README.md-based plugin info popup with Description, Installation, FAQ, and Changelog tabs
- **Improved:** CSS injection and div-based table rendering for wp_kses compatibility
- **Fixed:** Added required `id`, `slug`, `plugin` fields to the update response for WP core compatibility

### 1.0.1 - 2026-03-25
- **Removed:** Hardcoded GF_SLUGS fallback list; plugin category badges now rely entirely on GitHub repository topics (`gravity-forms`, `wordpress-plugin`)

### 1.0.0 - 2026-03-05
- Initial release
- Plugin dashboard with card grid, search, and dropdown filters
- Install, activate, deactivate, and delete plugins via AJAX
- GitHub API integration with topic-based auto-detection
- GitHub auto-update support via `Update URI` header
- Per-card bug reporting integration with guilamu-bug-reporter
- French (fr_FR) translation included

## Security

If you discover a security vulnerability in this plugin, please report it responsibly through [GitHub Security Advisories](https://github.com/guilamu/guilamu-plugins/security/advisories/new). Do not open a public issue for security reports.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request on [GitHub](https://github.com/guilamu/guilamu-plugins).

For translations, the plugin uses WordPress i18n. You can contribute translations by editing the `.po` files in the `languages/` directory and generating the corresponding `.mo` files with the `wp i18n` CLI commands.

## License

This project is licensed under the GNU Affero General Public License v3.0 (AGPL-3.0) - see the [LICENSE](LICENSE) file for details.

---

Made with love for the WordPress community
