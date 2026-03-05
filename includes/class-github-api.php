<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Guilamu_GitHub_API {

	const API_URL        = 'https://api.github.com/users/guilamu/repos';
	const CACHE_KEY      = 'guilamu_github_repos';
	const CACHE_DURATION = 43200; // 12 hours.

	/**
	 * Get repos from cache or fetch from GitHub.
	 *
	 * @return array Associative array keyed by repo name.
	 */
	public function get_repos() {
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$repos = $this->fetch_repos();
		if ( ! empty( $repos ) ) {
			set_transient( self::CACHE_KEY, $repos, self::CACHE_DURATION );
		}

		return $repos;
	}

	/**
	 * Fetch public repos from the GitHub API.
	 *
	 * @return array
	 */
	private function fetch_repos() {
		$url = add_query_arg(
			array(
				'per_page' => 100,
				'type'     => 'public',
			),
			self::API_URL
		);

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'Guilamu-Plugins-WordPress',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		$repos = array();
		foreach ( $data as $repo ) {
			if ( ! empty( $repo['name'] ) && empty( $repo['fork'] ) ) {
				$repos[ $repo['name'] ] = array(
					'name'        => $repo['name'],
					'description' => isset( $repo['description'] ) ? $repo['description'] : '',
					'html_url'    => isset( $repo['html_url'] ) ? $repo['html_url'] : '',
					'updated_at'  => isset( $repo['updated_at'] ) ? $repo['updated_at'] : '',
					'topics'      => isset( $repo['topics'] ) ? $repo['topics'] : array(),
				);
			}
		}

		return $repos;
	}

	/**
	 * Clear the cached repos transient.
	 */
	public function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}
}
