<?php
/**
 * Guilamu's WordPress Plugins – Uninstall
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes all transients and options created by the plugin.
 *
 * @package Guilamu_Plugins
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete GitHub API / updater transients.
delete_transient( 'guilamu_github_repos' );
delete_transient( 'guilamu_plugins_github_release' );
