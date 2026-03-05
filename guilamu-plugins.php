<?php
/**
 * Plugin Name: Guilamu's WordPress Plugins
 * Plugin URI: https://github.com/guilamu/guilamu-plugins
 * Description: Easily discover, install, and manage Guilamu's WordPress plugins directly from your admin dashboard.
 * Version: 1.0.0
 * Author: Guilamu
 * Author URI: https://github.com/guilamu
 * Text Domain: guilamu-plugins
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/guilamu/guilamu-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GUILAMU_PLUGINS_VERSION', '1.0.0' );
define( 'GUILAMU_PLUGINS_FILE', __FILE__ );
define( 'GUILAMU_PLUGINS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GUILAMU_PLUGINS_URL', plugin_dir_url( __FILE__ ) );

require_once GUILAMU_PLUGINS_DIR . 'includes/class-github-api.php';
require_once GUILAMU_PLUGINS_DIR . 'includes/class-github-updater.php';
require_once GUILAMU_PLUGINS_DIR . 'includes/class-guilamu-plugins.php';

/**
 * Load plugin text domain for translations.
 */
function guilamu_plugins_load_textdomain() {
	load_plugin_textdomain(
		'guilamu-plugins',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'guilamu_plugins_load_textdomain' );

add_action( 'init', array( 'Guilamu_Plugins', 'get_instance' ) );
Guilamu_Plugins_GitHub_Updater::init();
