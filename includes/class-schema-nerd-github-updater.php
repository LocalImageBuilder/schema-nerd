<?php
/**
 * GitHub Releases updater for private Schema Nerd distribution.
 *
 * Requires a GitHub token saved under Schema Nerd → Settings.
 *
 * @package Schema_Nerd
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Schema_Nerd_Github_Updater {

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin basename (schema-nerd/schema-nerd.php).
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * GitHub repository owner.
	 *
	 * @var string
	 */
	private $owner;

	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	private $repo;

	/**
	 * Installed plugin version.
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * @param string $plugin_file Main plugin file.
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = plugin_basename( $plugin_file );
		$this->owner       = (string) apply_filters( 'schema_nerd_github_owner', 'LocalImageBuilder' );
		$this->repo        = (string) apply_filters( 'schema_nerd_github_repo', 'schema-nerd' );

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data           = get_plugin_data( $plugin_file );
		$this->current_version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';

		// phpcs:ignore PluginCheck.CodeAnalysis.UpdateFunctions.pre_set_site_transient_update_plugins -- Private GitHub release updates.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( $this, 'authenticate_download' ), 10, 4 );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'maybe_show_token_notice' ) );
		}
	}

	/**
	 * GitHub token from plugin settings.
	 *
	 * @return string
	 */
	private function get_token() {
		if ( ! function_exists( 'schema_nerd_get_github_token' ) ) {
			return '';
		}

		return schema_nerd_get_github_token();
	}

	/**
	 * @param string $endpoint GitHub REST path beginning with /repos/...
	 * @return object|null
	 */
	private function api_request( $endpoint ) {
		$token = $this->get_token();

		if ( $token === '' ) {
			return null;
		}

		$response = wp_remote_get(
			'https://api.github.com' . $endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/vnd.github+json',
					'User-Agent'    => 'Schema-Nerd-WordPress-Plugin',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		return is_object( $body ) || is_array( $body ) ? $body : null;
	}

	/**
	 * @return object|null
	 */
	public function get_latest_release() {
		$cache_key = 'schema_nerd_github_release_' . md5( $this->owner . '/' . $this->repo );
		$cached    = get_transient( $cache_key );

		if ( is_object( $cached ) ) {
			return $cached;
		}

		$release = $this->api_request( '/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/releases/latest' );

		if ( ! is_object( $release ) || empty( $release->tag_name ) ) {
			$releases = $this->api_request( '/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/releases?per_page=1' );
			$release  = ( is_array( $releases ) && ! empty( $releases[0] ) && is_object( $releases[0] ) ) ? $releases[0] : null;
		}

		if ( is_object( $release ) ) {
			set_transient( $cache_key, $release, 12 * HOUR_IN_SECONDS );
		}

		return $release;
	}

	/**
	 * @param string $tag Release tag name.
	 * @return string
	 */
	private function normalize_version( $tag ) {
		return ltrim( (string) $tag, 'vV' );
	}

	/**
	 * Prefer a release asset zip; fall back to zipball URL.
	 *
	 * @param object $release GitHub release object.
	 * @return string
	 */
	private function get_download_package( $release ) {
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( ! is_object( $asset ) || empty( $asset->id ) ) {
					continue;
				}

				if ( ! empty( $asset->name ) && preg_match( '/\.zip$/i', $asset->name ) ) {
					return 'https://api.github.com/repos/' . rawurlencode( $this->owner ) . '/' . rawurlencode( $this->repo ) . '/releases/assets/' . (int) $asset->id;
				}
			}
		}

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
		if ( ! is_object( $transient ) || empty( $transient->checked ) || $this->get_token() === '' ) {
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
			'slug'        => dirname( $this->plugin_slug ),
			'plugin'      => $this->plugin_slug,
			'new_version' => $new_version,
			'url'         => ! empty( $release->html_url ) ? $release->html_url : '',
			'package'     => $package,
			'icons'       => array(),
			'banners'     => array(),
			'tested'      => '7.0',
			'requires_php' => '7.4',
		);

		return $transient;
	}

	/**
	 * @param false|object|array $result Plugin info result.
	 * @param string             $action API action.
	 * @param object             $args   Query args.
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' || ! is_object( $args ) || empty( $args->slug ) ) {
			return $result;
		}

		if ( dirname( $this->plugin_slug ) !== $args->slug || $this->get_token() === '' ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( ! is_object( $release ) || empty( $release->tag_name ) ) {
			return $result;
		}

		$new_version = $this->normalize_version( $release->tag_name );
		$package     = $this->get_download_package( $release );

		$info                 = new stdClass();
		$info->name           = 'Schema Nerd';
		$info->slug           = dirname( $this->plugin_slug );
		$info->version        = $new_version;
		$info->author         = '<a href="https://localimageco.com/">Local Image</a>';
		$info->homepage       = 'https://localimageco.com/';
		$info->download_link  = $package;
		$info->requires       = '6.0';
		$info->tested         = '7.0';
		$info->requires_php   = '7.4';
		$info->last_updated   = ! empty( $release->published_at ) ? gmdate( 'Y-m-d', strtotime( $release->published_at ) ) : '';
		$info->sections       = array(
			'description' => 'API interface for Schema Nerd organizations.',
			'changelog'   => ! empty( $release->body ) ? wp_kses_post( wpautop( $release->body ) ) : '',
		);

		return $info;
	}

	/**
	 * Download private GitHub release assets with authentication.
	 *
	 * @param bool|WP_Error $reply      Download response.
	 * @param string        $package    Package URL.
	 * @param WP_Upgrader   $upgrader   Upgrader instance.
	 * @param array         $hook_extra Extra hook data.
	 * @return bool|WP_Error|string
	 */
	public function authenticate_download( $reply, $package, $upgrader, $hook_extra ) {
		unset( $upgrader );

		if ( empty( $package ) || ! is_string( $package ) ) {
			return $reply;
		}

		$is_github = ( strpos( $package, 'github.com' ) !== false || strpos( $package, 'api.github.com' ) !== false );

		if ( ! $is_github ) {
			return $reply;
		}

		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] !== $this->plugin_slug ) {
			return $reply;
		}

		$token = $this->get_token();

		if ( $token === '' ) {
			return new WP_Error(
				'schema_nerd_no_token',
				esc_html__( 'Schema Nerd GitHub update token is not configured.', 'schema-nerd' )
			);
		}

		$tmp_file = wp_tempnam( $package );

		if ( ! $tmp_file ) {
			return new WP_Error(
				'schema_nerd_temp_file',
				esc_html__( 'Could not create a temporary file for the Schema Nerd update.', 'schema-nerd' )
			);
		}

		$response = wp_remote_get(
			$package,
			array(
				'headers'  => array(
					'Authorization' => 'Bearer ' . $token,
					'Accept'        => 'application/octet-stream',
					'User-Agent'    => 'Schema-Nerd-WordPress-Plugin',
				),
				'timeout'  => 300,
				'stream'   => true,
				'filename' => $tmp_file,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_delete_file( $tmp_file );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			wp_delete_file( $tmp_file );

			return new WP_Error(
				'schema_nerd_download_failed',
				sprintf(
					/* translators: %d: HTTP status code */
					esc_html__( 'Schema Nerd GitHub download failed with status %d.', 'schema-nerd' ),
					(int) $code
				)
			);
		}

		return $tmp_file;
	}

	/**
	 * Remind admins to configure the GitHub token on the Plugins screen.
	 */
	public function maybe_show_token_notice() {
		if ( ! current_user_can( 'update_plugins' ) || $this->get_token() !== '' ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || $screen->id !== 'plugins' ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		printf(
			wp_kses(
				/* translators: %s: Schema Nerd settings admin URL */
				__( 'Schema Nerd automatic updates are disabled. Add a GitHub update token under %s.', 'schema-nerd' ),
				array( 'a' => array( 'href' => array() ) )
			),
			'<a href="' . esc_url( admin_url( 'admin.php?page=schema-nerd' ) ) . '">' . esc_html__( 'Schema Nerd → Settings', 'schema-nerd' ) . '</a>'
		);
		echo '</p></div>';
	}
}
