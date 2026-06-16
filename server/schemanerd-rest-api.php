<?php
/**
 * Plugin Name: Schema Nerds REST API (Server)
 * Description: Authenticated organization endpoints for Schema Nerd client plugins. Deploy on schemanerd.app.
 * Version: 1.0.1
 *
 * Install on schemanerd.app, then point the client plugin at this site.
 * If /sn/v2/organization already exists, replace that callback with this class
 * or deactivate the old route before activating this plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema_Nerds_REST_API {

	const REST_NAMESPACE = 'sn/v2';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/account',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_account' ),
				'permission_callback' => array( __CLASS__, 'require_valid_api_key' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/organization',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_organizations' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public static function require_valid_api_key( WP_REST_Request $request ) {
		$user_id = self::get_user_id_from_request( $request );

		if ( ! $user_id ) {
			return new WP_Error(
				'sn_invalid_api_key',
				'Invalid or missing API key.',
				array( 'status' => 401 )
			);
		}

		return true;
	}

	public static function get_account( WP_REST_Request $request ) {
		$user_id = self::get_user_id_from_request( $request );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error(
				'sn_invalid_api_key',
				'Invalid or missing API key.',
				array( 'status' => 401 )
			);
		}

		return rest_ensure_response(
			array(
				'user_id'       => (int) $user_id,
				'display_name'  => $user->display_name,
				'organizations' => self::get_organization_payload_for_user( (int) $user_id, false ),
			)
		);
	}

	public static function get_organizations( WP_REST_Request $request ) {
		$user_id = self::get_user_id_from_request( $request );

		if ( ! $user_id ) {
			return rest_ensure_response( self::get_public_organization_payload() );
		}

		$organizations = self::get_organization_payload_for_user( (int) $user_id, true );
		$requested_id  = absint( $request->get_param( 'id' ) );

		if ( $requested_id ) {
			foreach ( $organizations as $organization ) {
				if ( (int) $organization['id'] === $requested_id ) {
					return rest_ensure_response( $organization );
				}
			}

			return new WP_Error(
				'sn_org_not_found',
				'Organization not found for this API key.',
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $organizations );
	}

	private static function get_public_organization_payload(): array {
		$posts = get_posts(
			array(
				'post_type'      => 'organization',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$organizations = array();

		foreach ( $posts as $post ) {
			$organizations[] = self::format_organization( $post, false );
		}

		return $organizations;
	}

	private static function get_organization_payload_for_user( int $user_id, bool $include_schema ): array {
		$posts = get_posts(
			array(
				'post_type'      => 'organization',
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 100,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$organizations = array();

		foreach ( $posts as $post ) {
			$organizations[] = self::format_organization( $post, $include_schema );
		}

		return $organizations;
	}

	private static function format_organization( WP_Post $post, bool $include_schema ): array {
		$organization = array(
			'id'     => (int) $post->ID,
			'title'  => get_the_title( $post ),
			'author' => (int) $post->post_author,
		);

		if ( $include_schema ) {
			$organization['schema'] = self::get_schema_markup_for_post( $post );
		}

		return $organization;
	}

	private static function get_schema_markup_for_post( WP_Post $post ): string {
		/**
		 * Return rendered schema JSON-LD for an organization post.
		 *
		 * Override on schemanerd.app if schema is stored in post meta or ACF.
		 */
		$schema = apply_filters( 'schema_nerd_organization_schema', '', $post );

		if ( is_string( $schema ) && $schema !== '' ) {
			return $schema;
		}

		$meta_keys = array( 'schema', 'schema_markup', 'json_ld' );

		foreach ( $meta_keys as $meta_key ) {
			$value = get_post_meta( $post->ID, $meta_key, true );
			if ( is_string( $value ) && $value !== '' ) {
				return $value;
			}
		}

		return '';
	}

	public static function get_user_id_from_request( WP_REST_Request $request ): int {
		$api_key = self::get_api_key_from_request( $request );

		if ( ! $api_key ) {
			return 0;
		}

		$user_id = apply_filters( 'schema_nerd_user_id_from_api_key', 0, $api_key, $request );

		if ( $user_id ) {
			return (int) $user_id;
		}

		$user_id = self::find_user_id_by_known_meta_keys( $api_key );
		if ( $user_id ) {
			return $user_id;
		}

		$user_id = self::find_user_id_by_application_password( $api_key );
		if ( $user_id ) {
			return $user_id;
		}

		$user_id = self::find_user_id_by_organization_post_meta( $api_key );
		if ( $user_id ) {
			return $user_id;
		}

		if ( apply_filters( 'schema_nerd_allow_usermeta_value_search', true, $api_key, $request ) ) {
			$user_id = self::find_user_id_by_any_usermeta_value( $api_key );
			if ( $user_id ) {
				return $user_id;
			}
		}

		return 0;
	}

	private static function find_user_id_by_known_meta_keys( string $api_key ): int {
		$meta_keys = apply_filters(
			'schema_nerd_api_key_meta_keys',
			array(
				'sn_api_key',
				'schema_nerd_api_key',
				'api_key',
				'one_time_key',
				'one_time_api_key',
				'sn_one_time_key',
				'wl_api_key',
				'user_api_key',
			)
		);

		foreach ( (array) $meta_keys as $meta_key ) {
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			$users = get_users(
				array(
					'meta_key'   => $meta_key,
					'meta_value' => $api_key,
					'number'     => 1,
					'fields'     => 'ID',
				)
			);
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

			if ( ! empty( $users ) ) {
				return (int) $users[0];
			}
		}

		return 0;
	}

	private static function find_user_id_by_application_password( string $api_key ): int {
		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			return 0;
		}

		$user_ids = get_users(
			array(
				'fields' => 'ID',
			)
		);

		foreach ( $user_ids as $user_id ) {
			$passwords = WP_Application_Passwords::get_user_application_passwords( (int) $user_id );

			foreach ( $passwords as $password_details ) {
				if (
					isset( $password_details['password'] )
					&& WP_Application_Passwords::check_password( $api_key, $password_details['password'] )
				) {
					return (int) $user_id;
				}
			}
		}

		return 0;
	}

	private static function find_user_id_by_organization_post_meta( string $api_key ): int {
		$meta_keys = apply_filters(
			'schema_nerd_organization_api_key_meta_keys',
			array( 'api_key', 'sn_api_key', 'schema_nerd_api_key', 'one_time_key' )
		);

		foreach ( (array) $meta_keys as $meta_key ) {
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			$posts = get_posts(
				array(
					'post_type'      => 'organization',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'meta_key'       => $meta_key,
					'meta_value'     => $api_key,
					'fields'         => 'ids',
				)
			);
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

			if ( ! empty( $posts ) ) {
				$post = get_post( (int) $posts[0] );
				if ( $post instanceof WP_Post ) {
					return (int) $post->post_author;
				}
			}
		}

		return 0;
	}

	private static function find_user_id_by_any_usermeta_value( string $api_key ): int {
		global $wpdb;

		$cache_key = 'schema_nerd_user_by_meta_' . md5( $api_key );
		$cached    = wp_cache_get( $cache_key, 'schema_nerd' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fallback lookup across legacy meta values; result is cached above.
		$user_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_value = %s LIMIT 1",
				$api_key
			)
		);

		$user_id = $user_id ? (int) $user_id : 0;
		wp_cache_set( $cache_key, $user_id, 'schema_nerd', HOUR_IN_SECONDS );

		return $user_id;
	}

	private static function get_api_key_from_request( WP_REST_Request $request ): string {
		$query_key = sanitize_text_field( (string) $request->get_param( 'api_key' ) );
		if ( $query_key !== '' ) {
			return $query_key;
		}

		$authorization = $request->get_header( 'authorization' );

		if ( ! is_string( $authorization ) || $authorization === '' ) {
			if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
				$authorization = sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) );
			} elseif ( ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
				$authorization = sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) );
			}
		}

		if ( ! is_string( $authorization ) || $authorization === '' ) {
			return '';
		}

		if ( preg_match( '/^(?:One Time|Bearer)\s+(.+)$/i', trim( $authorization ), $matches ) ) {
			return trim( $matches[1] );
		}

		return trim( $authorization );
	}
}

Schema_Nerds_REST_API::init();
