<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function schema_nerd_debug_log( $message ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
		return;
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( $message );
}

function schema_nerd_get_api_base_url() {
    return untrailingslashit( preg_replace( '#/wp-json/.*$#', '', SN_API_URL ) );
}

function schema_nerd_get_api_request_args( $api_key, $auth_prefix = 'One Time ' ) {
    return array(
        'headers' => array(
            'Authorization' => $auth_prefix . $api_key,
        ),
        'timeout' => 15,
    );
}

function schema_nerd_remote_request( $url, $api_key, $auth_prefix = 'One Time ' ) {
    return wp_remote_get( $url, schema_nerd_get_api_request_args( $api_key, $auth_prefix ) );
}

function schema_nerd_normalize_organization( $org ) {
    if ( ! is_array( $org ) || empty( $org['id'] ) ) {
        return null;
    }

    $title = '';
    if ( isset( $org['title'] ) && is_string( $org['title'] ) ) {
        $title = $org['title'];
    } elseif ( isset( $org['title']['rendered'] ) ) {
        $title = wp_strip_all_tags( $org['title']['rendered'] );
    }

    if ( $title === '' ) {
        return null;
    }

    return array(
        'id'    => (int) $org['id'],
        'title' => $title,
    );
}

function schema_nerd_normalize_organization_list( $data ) {
    $organizations = array();

    if ( ! is_array( $data ) ) {
        return $organizations;
    }

    if ( isset( $data['organizations'] ) && is_array( $data['organizations'] ) ) {
        $data = $data['organizations'];
    }

    foreach ( $data as $org ) {
        $normalized = schema_nerd_normalize_organization( $org );
        if ( $normalized ) {
            $organizations[] = $normalized;
        }
    }

    return $organizations;
}

function schema_nerd_get_public_organization_count() {
    static $count = null;

    if ( null !== $count ) {
        return $count;
    }

    $response = wp_remote_get(
        add_query_arg(
            array(
                'per_page' => 1,
                '_fields'  => 'id',
            ),
            schema_nerd_get_api_base_url() . '/wp-json/wp/v2/organization'
        ),
        array( 'timeout' => 15 )
    );

    if ( is_wp_error( $response ) ) {
        $count = 0;
        return $count;
    }

    $total = wp_remote_retrieve_header( $response, 'x-wp-total' );
    $count = $total ? (int) $total : 0;

    return $count;
}

function schema_nerd_request_authenticated_json( $url, $api_key ) {
    $last_code = 0;

    foreach ( array( 'One Time ', 'Bearer ' ) as $auth_prefix ) {
        $response = schema_nerd_remote_request( $url, $api_key, $auth_prefix );

        if ( is_wp_error( $response ) ) {
            continue;
        }

        $last_code = wp_remote_retrieve_response_code( $response );

        if ( in_array( $last_code, array( 401, 403 ), true ) ) {
            return new WP_Error(
                'invalid_api_key',
                'Invalid API key. Please verify the key from your Schema Nerds account.'
            );
        }

        if ( $last_code !== 200 ) {
            continue;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', 'Could not read the Schema Nerds API response.' );
        }

        return $data;
    }

    if ( in_array( $last_code, array( 401, 403 ), true ) ) {
        return new WP_Error(
            'invalid_api_key',
            'Invalid API key. Please verify the key from your Schema Nerds account.'
        );
    }

    return new WP_Error(
        'api_error',
        'Could not connect to the Schema Nerds API. Please try again later.'
    );
}

function schema_nerd_fetch_organizations( $api_key ) {
    if ( empty( $api_key ) ) {
        return new WP_Error( 'missing_api_key', 'API key is missing or empty' );
    }

    $account = schema_nerd_request_authenticated_json( SN_ACCOUNT_URL, $api_key );

    if ( ! is_wp_error( $account ) ) {
        $organizations = schema_nerd_normalize_organization_list( $account );

        if ( ! empty( $organizations ) ) {
            return $organizations;
        }

        return new WP_Error( 'empty_data', 'No organizations found for this API key.' );
    }

    $organization_data = schema_nerd_request_authenticated_json( SN_API_URL, $api_key );

    if ( is_wp_error( $organization_data ) ) {
        if ( $account->get_error_code() === 'invalid_api_key' ) {
            return $account;
        }

        return $organization_data;
    }

    $organizations = schema_nerd_normalize_organization_list( $organization_data );

    if ( empty( $organizations ) ) {
        if ( $account->get_error_code() === 'invalid_api_key' ) {
            return new WP_Error(
                'invalid_api_key',
                'Invalid API key. The key was not matched to a Schema Nerds account on the server.'
            );
        }

        return new WP_Error( 'empty_data', 'No organizations found for this API key.' );
    }

    $public_count = schema_nerd_get_public_organization_count();

    if ( $public_count > 0 && count( $organizations ) >= $public_count ) {
        if ( $account->get_error_code() === 'invalid_api_key' ) {
            return new WP_Error(
                'invalid_api_key',
                'Invalid API key. The key was not matched to a Schema Nerds account on the server.'
            );
        }

        return new WP_Error(
            'server_filter_required',
            'The API key could not be matched to a Schema Nerds account. Check the key, or ask your site admin to wire up API key lookup on schemanerd.app.'
        );
    }

    return $organizations;
}

function schema_nerd_fetch_organization_by_id( $api_key, $organization_id ) {
    if ( empty( $api_key ) ) {
        return new WP_Error( 'missing_api_key', 'API key is missing or empty' );
    }

    if ( empty( $organization_id ) ) {
        return new WP_Error( 'missing_org_id', 'No organization selected' );
    }

    $single = schema_nerd_request_authenticated_json(
        add_query_arg( 'id', (int) $organization_id, SN_API_URL ),
        $api_key
    );

    if ( ! is_wp_error( $single ) && isset( $single['id'] ) ) {
        return (object) $single;
    }

    $data = schema_nerd_request_authenticated_json( SN_API_URL, $api_key );

    if ( is_wp_error( $data ) ) {
        return $data;
    }

    $items = schema_nerd_normalize_organization_list( $data );
    $ids   = wp_list_pluck( $items, 'id' );

    if ( ! in_array( (int) $organization_id, array_map( 'intval', $ids ), true ) ) {
        return new WP_Error( 'org_not_found', 'Selected organization not found for this API key.' );
    }

    if ( is_array( $data ) ) {
        foreach ( $data as $organization ) {
            if ( is_array( $organization ) && isset( $organization['id'] ) && (int) $organization['id'] === (int) $organization_id ) {
                return (object) $organization;
            }
        }
    }

    return new WP_Error( 'org_not_found', 'Selected organization not found for this API key.' );
}

function schema_nerd_fetch_selected_organization_from_api() {
    $selected_org_id = get_option( 'schema_nerd_selected_org' );
    $api_key         = get_option( 'schema_nerd_api_key' );

    if ( empty( $api_key ) ) {
        schema_nerd_debug_log( 'Schema_Nerd Error: API key is missing or empty' );
        return new WP_Error( 'missing_api_key', 'API key is missing or empty' );
    }

    if ( empty( $selected_org_id ) ) {
        schema_nerd_debug_log( 'Schema_Nerd Error: No organization selected (empty $selected_org_id)' );
        return new WP_Error( 'missing_org_id', 'No organization selected' );
    }

    $organization = schema_nerd_fetch_organization_by_id( $api_key, $selected_org_id );

    if ( is_wp_error( $organization ) ) {
        schema_nerd_debug_log( 'Schema_Nerd Error: ' . $organization->get_error_message() );
        return $organization;
    }

    if ( isset( $organization->schema ) && is_string( $organization->schema ) ) {
        $decoded_schema = json_decode( $organization->schema );
        if ( $decoded_schema ) {
            $organization->schema = json_encode( $decoded_schema, JSON_UNESCAPED_SLASHES );
        }
    }

    return $organization;
}

function schema_nerd_display_selected_organization_from_api() {
    $api_key = get_option( 'schema_nerd_api_key' );
    if ( empty( $api_key ) || empty( get_option( 'schema_nerd_selected_org' ) ) ) {
        return;
    }

    $organization = schema_nerd_fetch_selected_organization_from_api();

    if ( is_wp_error( $organization ) ) {
        echo '<!-- Schema_Nerd: Organization schema not displayed due to error (check logs) -->';
        return;
    }

    if ( ! $organization ) {
        echo '<!-- Schema_Nerd: No organization data available -->';
        return;
    }

    if ( ! isset( $organization->schema ) || ! is_string( $organization->schema ) || trim( $organization->schema ) === '' ) {
        echo '<!-- Schema_Nerd: Selected organization has no schema data -->';
        return;
    }

    $schema_output = trim( $organization->schema );

    if ( strpos( $schema_output, '<script' ) === false ) {
        $decoded = json_decode( $schema_output );
        if ( null === $decoded ) {
            echo '<!-- Schema_Nerd: Schema data could not be parsed -->';
            return;
        }
        $schema_output = '<script type="application/ld+json">' . wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
    }

    echo '<!-- Schema_Nerd ID:' . esc_attr( $organization->id ) . '-' . esc_attr( $organization->title ) . ' -->';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD schema from authenticated Schema Nerd API, wrapped in script tag.
    echo $schema_output;
    echo "\n<!-- End Schema_Nerd -->";
}
add_action( 'wp_head', 'schema_nerd_display_selected_organization_from_api' );

function schema_nerd_get_organization_schema() {
    static $schema = null;

    if ( is_null( $schema ) ) {
        $organization = schema_nerd_fetch_selected_organization_from_api();
        $schema       = ( ! is_wp_error( $organization ) && isset( $organization->schema ) )
            ? $organization->schema
            : false;
    }

    return $schema;
}
