<?php
/**
 * Plugin Name: Guilamu's WordPress Plugins
 * Plugin URI: https://github.com/guilamu/guilamu-plugins
 * Description: Easily discover, install, and manage Guilamu's WordPress plugins directly from your admin dashboard.
 * Version: 1.0.2
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

define( 'GUILAMU_PLUGINS_VERSION', '1.0.2' );
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

/**
 * Add a "View details" thickbox link to the plugin row meta.
 *
 * @param array  $links Plugin meta links.
 * @param string $file  Plugin file path.
 * @return array Modified links.
 */
function guilamu_plugins_row_meta( $links, $file ) {
	if ( plugin_basename( GUILAMU_PLUGINS_FILE ) !== $file ) {
		return $links;
	}

	$links[] = sprintf(
		'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
		esc_url( self_admin_url(
			'plugin-install.php?tab=plugin-information&plugin=guilamu-plugins'
			. '&TB_iframe=true&width=772&height=926'
		) ),
		esc_attr__( "More information about Guilamu's WordPress Plugins", 'guilamu-plugins' ),
		esc_attr__( "Guilamu's WordPress Plugins", 'guilamu-plugins' ),
		esc_html__( 'View details', 'guilamu-plugins' )
	);

	return $links;
}
add_filter( 'plugin_row_meta', 'guilamu_plugins_row_meta', 10, 2 );
