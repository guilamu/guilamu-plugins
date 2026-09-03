<?php
/**
 * Plugin Name: Guilamu's WordPress Plugins
 * Plugin URI: https://github.com/guilamu/guilamu-plugins
 * Description: Easily discover, install, and manage Guilamu's WordPress plugins directly from your admin dashboard.
 * Version: 1.0.6
 * Author: Guilamu
 * Author URI: https://github.com/guilamu
 * Text Domain: guilamu-plugins
 * Domain Path: /languages
 * License: AGPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/agpl-3.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/guilamu/guilamu-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GUILAMU_PLUGINS_VERSION', '1.0.6' );
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
 * Register this plugin with Guilamu Bug Reporter.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'Guilamu_Bug_Reporter' ) ) {
			Guilamu_Bug_Reporter::register(
				array(
					'slug'        => 'guilamu-plugins',
					'name'        => "Guilamu's WordPress Plugins",
					'version'     => GUILAMU_PLUGINS_VERSION,
					'github_repo' => 'guilamu/guilamu-plugins',
				)
			);
		}
	},
	20
);

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

	if ( class_exists( 'Guilamu_Bug_Reporter' ) ) {
		$links[] = sprintf(
			'<a href="#" class="guilamu-bug-report-btn" data-plugin-slug="guilamu-plugins" data-plugin-name="%s">%s</a>',
			esc_attr__( "Guilamu's WordPress Plugins", 'guilamu-plugins' ),
			esc_html__( '🐛 Report a Bug', 'guilamu-plugins' )
		);
	} else {
		$links[] = '<a href="https://github.com/guilamu/guilamu-bug-reporter/releases" target="_blank">'
			. esc_html__( '🐛 Report a Bug (install Bug Reporter)', 'guilamu-plugins' )
			. '</a>';
	}

	return $links;
}
add_filter( 'plugin_row_meta', 'guilamu_plugins_row_meta', 10, 2 );
