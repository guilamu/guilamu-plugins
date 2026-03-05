<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Guilamu_Plugins {

	/**
	 * GitHub repos that are WordPress plugins.
	 * Add a 'wordpress-plugin' topic to any new GitHub repo to auto-include it.
	 * The 'gravity-forms' topic marks it as a Gravity Forms plugin.
	 *
	 * This map serves as a fallback for category detection when topics aren't set.
	 */
	const GF_SLUGS = array(
		'gf-advanced-expiring-entries',
		'french-schools-map',
		'gf-french-schools',
		'gf-chained-select-enhancer',
		'gravity-extract',
		'gf-external-choices',
		'GF-Advanced-Conditional-Choices',
		'gf-merge-tag-autocomplete',
		'gf-conditional-compass',
		'gravity-forms-shortcode-builder',
	);

	private static $instance = null;

	/** @var bool Whether the GitHub API returned data. */
	private $api_available = true;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_guilamu_install_plugin', array( $this, 'ajax_install_plugin' ) );
		add_action( 'wp_ajax_guilamu_activate_plugin', array( $this, 'ajax_activate_plugin' ) );
		add_action( 'wp_ajax_guilamu_deactivate_plugin', array( $this, 'ajax_deactivate_plugin' ) );
		add_action( 'wp_ajax_guilamu_delete_plugin', array( $this, 'ajax_delete_plugin' ) );
		add_action( 'wp_ajax_guilamu_refresh_plugins', array( $this, 'ajax_refresh_plugins' ) );
	}

	public function add_admin_menu() {
		add_menu_page(
			__( "Guilamu's Plugins", 'guilamu-plugins' ),
			__( "Guilamu's Plugins", 'guilamu-plugins' ),
			'manage_options',
			'guilamu-plugins',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-plugins',
			66
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_guilamu-plugins' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'guilamu-plugins-admin',
			GUILAMU_PLUGINS_URL . 'assets/css/admin.css',
			array(),
			GUILAMU_PLUGINS_VERSION
		);

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_script(
			'guilamu-plugins-admin',
			GUILAMU_PLUGINS_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			GUILAMU_PLUGINS_VERSION,
			true
		);

			wp_localize_script(
			'guilamu-plugins-admin',
			'guilamuPlugins',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'guilamu_plugins_nonce' ),
				'bugReporterActive'  => $this->is_bug_reporter_active(),
			)
		);
	}

	/* ─────────────────────────────────────────────
	 *  Admin Page Rendering
	 * ───────────────────────────────────────────── */

	public function render_admin_page() {
		$plugins   = $this->get_plugin_data();
		$gf_active = $this->is_gravity_forms_active();

		$counts = array(
			'all'               => count( $plugins ),
			'activated'         => 0,
			'installed'         => 0,
			'not-installed'     => 0,
			'gravity-forms'     => 0,
			'non-gravity-forms' => 0,
		);

		foreach ( $plugins as $p ) {
			if ( $p['is_active'] ) {
				$counts['activated']++;
			}
			if ( $p['is_installed'] ) {
				$counts['installed']++;
			} else {
				$counts['not-installed']++;
			}
			$counts[ $p['category'] ]++;
		}

		?>
		<div class="wrap guilamu-plugins-wrap">

			<div class="guilamu-header">
				<h1><?php esc_html_e( "Guilamu's Plugins", 'guilamu-plugins' ); ?></h1>
				<a href="#" id="guilamu-refresh" class="page-title-action" title="<?php esc_attr_e( 'Refresh plugin list from GitHub', 'guilamu-plugins' ); ?>">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Refresh', 'guilamu-plugins' ); ?>
				</a>
			</div>

			<?php if ( ! $this->api_available ) : ?>
				<div class="notice notice-info inline">
					<p>
						<span class="dashicons dashicons-info"></span>
						<?php esc_html_e( 'Could not fetch plugin data from GitHub. Descriptions may be unavailable. Click "Refresh" to retry.', 'guilamu-plugins' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( ! $gf_active ) : ?>
				<div class="notice notice-warning inline guilamu-gf-notice">
					<p>
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'Gravity Forms is not installed or activated. Gravity Forms plugins require it to function.', 'guilamu-plugins' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="guilamu-toolbar">
				<div class="guilamu-search-wrap">
					<span class="dashicons dashicons-search guilamu-search-icon"></span>
					<input type="search"
						   id="guilamu-search"
						   placeholder="<?php esc_attr_e( 'Search plugins...', 'guilamu-plugins' ); ?>"
						   class="guilamu-search-input"
						   autocomplete="off">
				</div>

				<select id="guilamu-status-filter" class="guilamu-filter-select">
					<option value="all"><?php printf( '%s (%d)', esc_html__( 'All Plugins', 'guilamu-plugins' ), intval( $counts['all'] ) ); ?></option>
					<option value="activated"><?php printf( '%s (%d)', esc_html__( 'Activated', 'guilamu-plugins' ), intval( $counts['activated'] ) ); ?></option>
					<option value="installed"><?php printf( '%s (%d)', esc_html__( 'Installed', 'guilamu-plugins' ), intval( $counts['installed'] ) ); ?></option>
					<option value="not-installed"><?php printf( '%s (%d)', esc_html__( 'Not Installed', 'guilamu-plugins' ), intval( $counts['not-installed'] ) ); ?></option>
				</select>

				<select id="guilamu-category-filter" class="guilamu-filter-select">
					<option value="all"><?php esc_html_e( 'All Categories', 'guilamu-plugins' ); ?></option>
					<option value="gravity-forms"><?php printf( '%s (%d)', esc_html__( 'Gravity Forms', 'guilamu-plugins' ), intval( $counts['gravity-forms'] ) ); ?></option>
					<option value="non-gravity-forms"><?php printf( '%s (%d)', esc_html__( 'Non-Gravity Forms', 'guilamu-plugins' ), intval( $counts['non-gravity-forms'] ) ); ?></option>
				</select>

	
			</div>

			<div class="guilamu-plugin-grid" id="guilamu-plugin-grid">
				<?php foreach ( $plugins as $plugin ) : ?>
					<?php $this->render_plugin_card( $plugin, $gf_active ); ?>
				<?php endforeach; ?>
			</div>

			<div class="guilamu-no-results" id="guilamu-no-results" style="display: none;">
				<div class="guilamu-no-results-inner">
					<span class="dashicons dashicons-search"></span>
					<p><?php esc_html_e( 'No plugins match your criteria.', 'guilamu-plugins' ); ?></p>
				</div>
			</div>

		</div>
		<?php
	}

	private function render_plugin_card( $plugin, $gf_active ) {
		$status   = $plugin['is_active'] ? 'active' : ( $plugin['is_installed'] ? 'installed' : 'not-installed' );
		$needs_gf = ( 'gravity-forms' === $plugin['category'] && ! $gf_active );

		?>
		<div class="guilamu-plugin-card"
			 data-slug="<?php echo esc_attr( $plugin['slug'] ); ?>"
			 data-name="<?php echo esc_attr( $plugin['name'] ); ?>"
			 data-description="<?php echo esc_attr( $plugin['description'] ); ?>"
			 data-category="<?php echo esc_attr( $plugin['category'] ); ?>"
			 data-status="<?php echo esc_attr( $status ); ?>"
			 data-plugin-file="<?php echo esc_attr( $plugin['plugin_file'] ); ?>">

			<div class="guilamu-card-header">
				<h3 class="guilamu-card-title"><?php echo esc_html( $plugin['name'] ); ?></h3>
				<div class="guilamu-card-header-buttons">
					<a href="<?php echo esc_url( $plugin['github_url'] ); ?>"
					   target="_blank"
					   rel="noopener noreferrer"
					   class="guilamu-github-link"
					   title="<?php esc_attr_e( 'View on GitHub', 'guilamu-plugins' ); ?>">
						<svg height="20" width="20" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
							<path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
						</svg>
					</a>
					<?php if ( $plugin['is_installed'] ) : ?>
						<button type="button"
								class="guilamu-header-icon-btn guilamu-bug-report-btn"
								data-plugin-slug="<?php echo esc_attr( $plugin['slug'] ); ?>"
								data-plugin-name="<?php echo esc_attr( $plugin['name'] ); ?>"
								title="<?php esc_attr_e( 'Report a Bug', 'guilamu-plugins' ); ?>">
							<span class="dashicons dashicons-flag"></span>
						</button>
					<?php endif; ?>
					<?php if ( $plugin['is_installed'] && ! $plugin['is_active'] ) : ?>
						<button type="button"
								class="guilamu-header-icon-btn guilamu-delete-btn"
								title="<?php esc_attr_e( 'Delete plugin', 'guilamu-plugins' ); ?>">
							<span class="dashicons dashicons-trash"></span>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="guilamu-card-body">
				<div class="guilamu-card-badges">
					<?php if ( 'gravity-forms' === $plugin['category'] ) : ?>
						<span class="guilamu-badge guilamu-badge--gravity-forms"><?php esc_html_e( 'Gravity Forms', 'guilamu-plugins' ); ?></span>
					<?php else : ?>
						<span class="guilamu-badge guilamu-badge--general"><?php esc_html_e( 'General', 'guilamu-plugins' ); ?></span>
					<?php endif; ?>
					<?php if ( $needs_gf ) : ?>
						<span class="guilamu-badge guilamu-badge--warning">
							&#9888; <?php esc_html_e( 'Requires Gravity Forms', 'guilamu-plugins' ); ?>
						</span>
					<?php endif; ?>
				</div>
				<p class="guilamu-card-description"><?php echo esc_html( $plugin['description'] ); ?></p>
				<?php if ( $plugin['is_installed'] && $plugin['version'] ) : ?>
					<span class="guilamu-version">v<?php echo esc_html( $plugin['version'] ); ?></span>
				<?php endif; ?>
			</div>

			<div class="guilamu-card-footer">
				<?php if ( ! $plugin['is_installed'] ) : ?>
					<span class="guilamu-status-label guilamu-status--not-installed">
						<?php esc_html_e( 'Not Installed', 'guilamu-plugins' ); ?>
					</span>
					<button class="button button-primary guilamu-install-btn">
						<?php esc_html_e( 'Install', 'guilamu-plugins' ); ?>
					</button>
				<?php else : ?>
					<div class="guilamu-toggle-wrap">
						<span class="guilamu-status-label <?php echo $plugin['is_active'] ? 'guilamu-status--active' : 'guilamu-status--inactive'; ?>">
							<?php echo $plugin['is_active'] ? esc_html__( 'Active', 'guilamu-plugins' ) : esc_html__( 'Inactive', 'guilamu-plugins' ); ?>
						</span>
						<button class="guilamu-toggle <?php echo $plugin['is_active'] ? 'active' : ''; ?>"
								role="switch"
								aria-checked="<?php echo $plugin['is_active'] ? 'true' : 'false'; ?>"
								aria-label="<?php echo $plugin['is_active'] ? esc_attr__( 'Deactivate', 'guilamu-plugins' ) : esc_attr__( 'Activate', 'guilamu-plugins' ); ?>">
							<span class="guilamu-toggle-thumb"></span>
						</button>
					</div>
				<?php endif; ?>
			</div>

		</div>
		<?php
	}

	/* ─────────────────────────────────────────────
	 *  AJAX Handlers
	 * ───────────────────────────────────────────── */

	public function ajax_install_plugin() {
		check_ajax_referer( 'guilamu_plugins_nonce', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'guilamu-plugins' ) ) );
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( ! $this->is_valid_guilamu_repo( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown plugin.', 'guilamu-plugins' ) ) );
		}

		$download_url = 'https://github.com/guilamu/' . rawurlencode( $slug ) . '/releases/latest/download/' . rawurlencode( $slug ) . '.zip';

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $download_url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( ! $result ) {
			$errors  = $skin->get_errors();
			$message = is_wp_error( $errors ) ? $errors->get_error_message() : __( 'Installation failed.', 'guilamu-plugins' );
			wp_send_json_error( array( 'message' => $message ) );
		}

		// Find the installed plugin file.
		$destination  = isset( $upgrader->result['destination_name'] ) ? $upgrader->result['destination_name'] : $slug;
		$plugin_files = get_plugins( '/' . $destination );

		if ( empty( $plugin_files ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin installed but main file not found.', 'guilamu-plugins' ) ) );
		}

		$main_file   = $destination . '/' . key( $plugin_files );
		$plugin_data = reset( $plugin_files );

		wp_send_json_success( array(
			'plugin_file' => $main_file,
			'name'        => $plugin_data['Name'],
			'description' => $plugin_data['Description'],
			'version'     => $plugin_data['Version'],
		) );
	}

	public function ajax_activate_plugin() {
		check_ajax_referer( 'guilamu_plugins_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'guilamu-plugins' ) ) );
		}

		$plugin_file = isset( $_POST['plugin_file'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) ) : '';
		$slug        = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( ! $this->is_valid_guilamu_repo( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown plugin.', 'guilamu-plugins' ) ) );
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		if ( ! isset( $all_plugins[ $plugin_file ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin file not found.', 'guilamu-plugins' ) ) );
		}

		$result = activate_plugin( $plugin_file );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success();
	}

	public function ajax_deactivate_plugin() {
		check_ajax_referer( 'guilamu_plugins_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'guilamu-plugins' ) ) );
		}

		$plugin_file = isset( $_POST['plugin_file'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) ) : '';
		$slug        = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( ! $this->is_valid_guilamu_repo( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown plugin.', 'guilamu-plugins' ) ) );
		}

		deactivate_plugins( $plugin_file );

		wp_send_json_success();
	}

	public function ajax_delete_plugin() {
		check_ajax_referer( 'guilamu_plugins_nonce', 'nonce' );

		if ( ! current_user_can( 'delete_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'guilamu-plugins' ) ) );
		}

		$plugin_file = isset( $_POST['plugin_file'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) ) : '';
		$slug        = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( ! $this->is_valid_guilamu_repo( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown plugin.', 'guilamu-plugins' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Safety: deactivate before deleting.
		if ( is_plugin_active( $plugin_file ) ) {
			deactivate_plugins( $plugin_file );
		}

		$result = delete_plugins( array( $plugin_file ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success();
	}

	public function ajax_refresh_plugins() {
		check_ajax_referer( 'guilamu_plugins_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'guilamu-plugins' ) ) );
		}

		$api = new Guilamu_GitHub_API();
		$api->clear_cache();

		wp_send_json_success();
	}

	/* ─────────────────────────────────────────────
	 *  Helpers
	 * ───────────────────────────────────────────── */

	/**
	 * Build the unified plugin data array from GitHub + local installs.
	 *
	 * @return array
	 */
	private function get_plugin_data() {
		$api          = new Guilamu_GitHub_API();
		$github_repos = $api->get_repos();

		$this->api_available = ! empty( $github_repos );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = get_plugins();
		$plugins           = array();

		// Skip repos that are clearly not WP plugins (this plugin itself, docs, etc.).
		$skip = array( 'guilamu-plugins', 'guilamu', '.github' );

		foreach ( $github_repos as $slug => $repo ) {
			// Skip excluded repos.
			if ( in_array( $slug, $skip, true ) ) {
				continue;
			}

			// Auto-detect if this is a WP plugin:
			// 1. Has 'wordpress-plugin' topic, OR
			// 2. Is in our known GF_SLUGS fallback, OR
			// 3. Is already installed locally as a WP plugin.
			$topics       = isset( $repo['topics'] ) ? $repo['topics'] : array();
			$has_wp_topic = in_array( 'wordpress-plugin', $topics, true );
			$is_known     = in_array( $slug, self::GF_SLUGS, true );
			$installed    = $this->find_installed_plugin( $slug, $installed_plugins );

			if ( ! $has_wp_topic && ! $is_known && ! $installed ) {
				continue;
			}

			// Determine category.
			$category = 'non-gravity-forms';
			if ( in_array( $slug, self::GF_SLUGS, true ) || in_array( 'gravity-forms', $topics, true ) ) {
				$category = 'gravity-forms';
			}

			$plugin = array(
				'slug'         => $slug,
				'name'         => $this->format_name( $slug ),
				'description'  => ! empty( $repo['description'] ) ? $repo['description'] : '',
				'github_url'   => 'https://github.com/guilamu/' . $slug,
				'category'     => $category,
				'is_installed' => false,
				'is_active'    => false,
				'plugin_file'  => '',
				'version'      => '',
			);

			if ( $installed ) {
				$plugin['is_installed'] = true;
				$plugin['plugin_file']  = $installed['file'];
				$plugin['is_active']    = is_plugin_active( $installed['file'] );
				$plugin['version']      = $installed['data']['Version'];

				if ( ! empty( $installed['data']['Name'] ) ) {
					$plugin['name'] = $installed['data']['Name'];
				}
				if ( ! empty( $installed['data']['Description'] ) ) {
					$plugin['description'] = $installed['data']['Description'];
				}
			}

			$plugins[ $slug ] = $plugin;
		}

		// Sort alphabetically by display name.
		uasort( $plugins, function ( $a, $b ) {
			return strcasecmp( $a['name'], $b['name'] );
		} );

		return $plugins;
	}

	/**
	 * Find an installed WP plugin matching a GitHub slug (case-insensitive).
	 */
	private function find_installed_plugin( $slug, $installed_plugins ) {
		foreach ( $installed_plugins as $file => $data ) {
			$dir = dirname( $file );
			if ( strcasecmp( $dir, $slug ) === 0 ) {
				return array( 'file' => $file, 'data' => $data );
			}
		}
		return null;
	}

	/**
	 * Check if a slug matches a known Guilamu GitHub repo.
	 */
	private function is_valid_guilamu_repo( $slug ) {
		if ( empty( $slug ) || ! preg_match( '/^[a-zA-Z0-9._-]+$/', $slug ) ) {
			return false;
		}
		$api   = new Guilamu_GitHub_API();
		$repos = $api->get_repos();
		return array_key_exists( $slug, $repos );
	}

	/**
	 * Convert a GitHub slug to a human-readable name.
	 */
	private function format_name( $slug ) {
		$name = str_replace( array( '-', '_' ), ' ', $slug );
		$name = ucwords( $name );
		// Keep common abbreviations uppercase.
		$name = preg_replace( '/\bGf\b/', 'GF', $name );
		$name = preg_replace( '/\bPdf\b/', 'PDF', $name );
		return $name;
	}

	/**
	 * Check whether Gravity Forms is currently active.
	 */
	private function is_gravity_forms_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'gravityforms/gravityforms.php' );
	}

	/**
	 * Check whether Guilamu Bug Reporter is installed and active.
	 */
	private function is_bug_reporter_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed = get_plugins();
		foreach ( $installed as $file => $data ) {
			if ( stripos( $file, 'guilamu-bug-reporter' ) !== false && is_plugin_active( $file ) ) {
				return true;
			}
		}
		return false;
	}
}
