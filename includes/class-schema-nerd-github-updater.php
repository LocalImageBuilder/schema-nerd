<?php
/**
 * Public GitHub Releases updater (no token required).
 *
 * Requires the LocalImageBuilder/schema-nerd repository to be public,
 * with a release asset named schema-nerd.zip (folder structure: schema-nerd/).
 *
 * @package Schema_Nerd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Schema_Nerd_Github_Updater {

	/**
	 * @var string
	 */
	private $plugin_file;

	/**
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * @var string
	 */
	private $owner;

	/**
	 * @var string
	 */
	private $repo;

	/**
	 * @var string
	 */
	private $current_version;

	/**
	 * @param string $plugin_file Main plugin file path.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = plugin_basename( $plugin_file );
		$this->owner       = (string) apply_filters( 'schema_nerd_github_owner', 'LocalImageBuilder' );
		$this->repo        = (string) apply_filters( 'schema_nerd_github_repo', 'schema-nerd' );

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data           = get_plugin_data( $plugin_file, false, false );
		$this->current_version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
	}

	/**
	 * @param string $endpoint Path starting with /repos/...
	 * @return object|array|null
	 */
	private function api_request( $endpoint ) {
		$response = wp_remote_get(
			'https://api.github.com' . $endpoint,
			array(
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Schema-Nerd-WordPress-Plugin',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		return ( is_object( $body ) || is_array( $body ) ) ? $body : null;
	}

	/**
	 * @return object|null
	 */
	public function get_latest_release() {
		$cache_key = 'schema_nerd_gh_release_' . md5( $this->owner . '/' . $this->repo );
		$cached    = get_transient( $cache_key );

		if ( is_object( $cached ) ) {
			return $cached;
		}

		$release = $this->api_request(
			'/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/releases/latest'
		);

		if ( ! is_object( $release ) || empty( $release->tag_name ) ) {
			$releases = $this->api_request(
				'/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/releases?per_page=1'
			);
			$release  = ( is_array( $releases ) && ! empty( $releases[0] ) && is_object( $releases[0] ) )
				? $releases[0]
				: null;
		}

		if ( is_object( $release ) ) {
			set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );
		}

		return $release;
	}

	/**
	 * @param string $tag Release tag.
	 * @return string
	 */
	private function normalize_version( $tag ) {
		return ltrim( (string) $tag, 'vV' );
	}

	/**
	 * Prefer a public release zip asset named schema-nerd.zip.
	 *
	 * @param object $release GitHub release.
	 * @return string
	 */
	private function get_download_package( $release ) {
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( ! is_object( $asset ) || empty( $asset->browser_download_url ) ) {
					continue;
				}

				$name = isset( $asset->name ) ? (string) $asset->name : '';

				if ( preg_match( '/\.zip$/i', $name ) ) {
					return (string) $asset->browser_download_url;
				}
			}
		}

		// Fallback: source zipball (folder may need renaming via fix_source_dir).
		if ( ! empty( $release->zipball_url ) ) {
			return (string) $release->zipball_url;
		}

		return '';
	}

	/**
	 * @param object $transient Update transient.
	 * @return object
	 */
	public function check_for_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();

		if ( ! is_object( $release ) || empty( $release->tag_name ) ) {
			return $transient;
		}

		$new_version = $this->normalize_version( $release->tag_name );

		if ( $new_version === '' || version_compare( $this->current_version, $new_version, '>=' ) ) {
			return $transient;
		}

		$package = $this->get_download_package( $release );

		if ( $package === '' ) {
			return $transient;
		}

		$transient->response[ $this->plugin_slug ] = (object) array(
			'slug'         => dirname( $this->plugin_slug ),
			'plugin'       => $this->plugin_slug,
			'new_version'  => $new_version,
			'url'          => ! empty( $release->html_url ) ? $release->html_url : '',
			'package'      => $package,
			'icons'        => array(),
			'banners'      => array(),
			'tested'       => '7.0',
			'requires_php' => '7.4',
		);

		return $transient;
	}

	/**
	 * @param false|object|array $result Result.
	 * @param string             $action Action.
	 * @param object             $args   Args.
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || empty( $args->slug ) ) {
			return $result;
		}

		if ( dirname( $this->plugin_slug ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( ! is_object( $release ) || empty( $release->tag_name ) ) {
			return $result;
		}

		$new_version = $this->normalize_version( $release->tag_name );
		$package     = $this->get_download_package( $release );

		$info                = new stdClass();
		$info->name          = 'Schema Nerd';
		$info->slug          = dirname( $this->plugin_slug );
		$info->version       = $new_version;
		$info->author        = '<a href="https://localimageco.com/">Local Image</a>';
		$info->homepage      = 'https://localimageco.com/';
		$info->download_link = $package;
		$info->requires      = '6.0';
		$info->tested        = '7.0';
		$info->requires_php  = '7.4';
		$info->last_updated  = ! empty( $release->published_at )
			? gmdate( 'Y-m-d', strtotime( $release->published_at ) )
			: '';
		$info->sections      = array(
			'description' => 'API interface for Schema Nerd organizations.',
			'changelog'   => ! empty( $release->body ) ? wp_kses_post( wpautop( $release->body ) ) : '',
		);

		return $info;
	}

	/**
	 * Ensure the unpacked folder is named schema-nerd/ (GitHub zipballs use a hash suffix).
	 *
	 * @param string      $source        Source path.
	 * @param string      $remote_source Remote source.
	 * @param WP_Upgrader $upgrader      Upgrader.
	 * @param array       $hook_extra    Extra.
	 * @return string|WP_Error
	 */
	public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
		unset( $upgrader );

		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_slug ) {
			return $source;
		}

		$desired = trailingslashit( $remote_source ) . 'schema-nerd';
		$source  = untrailingslashit( $source );

		if ( $source === $desired || basename( $source ) === 'schema-nerd' ) {
			return trailingslashit( $source );
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem || ! $wp_filesystem->move( $source, $desired ) ) {
			return new WP_Error(
				'schema_nerd_rename_failed',
				esc_html__( 'Could not prepare the Schema Nerd update package.', 'schema-nerd' )
			);
		}

		return trailingslashit( $desired );
	}
}
