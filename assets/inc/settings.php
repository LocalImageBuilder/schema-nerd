<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Settings Page

//--------------------------------------------------------------------------------------------------

function schema_nerd_settings_page() {

    add_menu_page(

        'Schema Nerd Settings',

        'Schema Nerd',

        'manage_options',

        'schema-nerd',

        'schema_nerd_settings_display',

        'dashicons-admin-generic',

        100

    );

}

add_action( 'admin_menu', 'schema_nerd_settings_page' );



function schema_nerd_admin_assets( $hook ) {

    if ( $hook !== 'toplevel_page_schema-nerd' ) {

        return;

    }



    $asset_path = dirname( dirname( __FILE__ ) );



    wp_enqueue_style(

        'sn-core',

        SN_CORE_CSS . 'sn-core.css',

        array(),

        filemtime( $asset_path . '/css/sn-core.css' )

    );



    wp_enqueue_style(

        'sn-admin',

        SN_CORE_CSS . 'sn-admin.css',

        array( 'sn-widget' ),

        filemtime( $asset_path . '/css/sn-admin.css' )

    );



    wp_enqueue_style( 'sn-widget' );



    wp_enqueue_script( 'sn-location-builder' );



    wp_enqueue_script(

        'sn-admin',

        SN_CORE_JS . 'sn-admin.js',

        array( 'jquery', 'sn-location-builder' ),

        filemtime( $asset_path . '/js/sn-admin.js' ),

        true

    );

}

add_action( 'admin_enqueue_scripts', 'schema_nerd_admin_assets' );



function schema_nerd_get_settings_tabs() {
    $tabs_dir  = SN_CORE_INC . '/tabs/';
    $tab_files = glob( $tabs_dir . '*.php' );
    $tabs      = array( 'settings' => 'Settings' );

    foreach ( $tab_files as $file ) {
        $tab_name = basename( $file, '.php' );

        if ( 'advanced' === $tab_name ) {
            continue;
        }

        $tabs[ $tab_name ] = ucfirst( $tab_name );
    }

    $tabs['advanced'] = 'Advanced';

    return $tabs;
}

function schema_nerd_is_saving_settings_tab( $tab ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked during options.php save after nonce verification in core.
    if ( empty( $_POST['schema_nerd_active_tab'] ) ) {
        return false;
    }

    return sanitize_key( wp_unslash( $_POST['schema_nerd_active_tab'] ) ) === $tab;
}

function schema_nerd_settings_active_tab_field( $tab ) {
    echo '<input type="hidden" name="schema_nerd_active_tab" value="' . esc_attr( $tab ) . '">';
}

function schema_nerd_settings_redirect_with_tab( $location ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Tab slug is only used to rebuild the settings screen redirect URL.
    if ( empty( $_POST['schema_nerd_active_tab'] ) ) {
        return $location;
    }

    $tab = sanitize_key( wp_unslash( $_POST['schema_nerd_active_tab'] ) );

    if ( $tab === '' || ! array_key_exists( $tab, schema_nerd_get_settings_tabs() ) || strpos( $location, 'page=schema-nerd' ) === false ) {
        return $location;
    }

    return add_query_arg( 'tab', $tab, $location );
}

if ( is_admin() ) {
    add_filter( 'wp_redirect', 'schema_nerd_settings_redirect_with_tab' );
}


function schema_nerd_settings_display() {

    $tabs_dir = SN_CORE_INC . '/tabs/';
    $tabs     = schema_nerd_get_settings_tabs();

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin tab navigation.
    $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';

    if ( ! array_key_exists( $current_tab, $tabs ) ) {
        $current_tab = 'settings';
    }

    ?>

    <div class="wrap">

        <h1>Schema Nerd Settings</h1>
        <?php settings_errors(); ?>



        <h2 class="nav-tab-wrapper">

            <?php foreach ( $tabs as $tab_slug => $tab_name ) : ?>

                <a href="?page=schema-nerd&tab=<?php echo esc_attr( $tab_slug ); ?>" class="nav-tab <?php echo $current_tab === $tab_slug ? 'nav-tab-active' : ''; ?>">

                    <?php

                    if ( isset( $tab_name ) ) {

                        $name = preg_replace( '/\.[^.]*$/', '', $tab_name );

                        $name = preg_replace( '/[^a-zA-Z0-9]+/', ' ', $name );

                        $name = ucwords( strtolower( $name ) );

                        echo esc_html( $name );

                    }

                    ?>

                </a>

            <?php endforeach; ?>

        </h2>



            <?php if ( $current_tab === 'settings' ) : ?>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'schema_nerd_settings_group' );
                schema_nerd_settings_active_tab_field( 'settings' );
                do_settings_sections( 'schema-nerd' );

                $api_key = get_option( 'schema_nerd_api_key' );

                if ( ! empty( $api_key ) ) {
                    $organizations = schema_nerd_fetch_organizations( $api_key );

                    if ( ! is_wp_error( $organizations ) && ! empty( $organizations ) ) {
                        echo '<table class="form-table">';
                        echo '<tr valign="top">';
                        echo '<th scope="row">Organization (required)</th>';
                        echo '<td>';

                        $selected_org = get_option( 'schema_nerd_selected_org' );

                        echo '<select name="schema_nerd_selected_org" style="min-width:50%;">';
                        echo '<option value="">-- Select Organization --</option>';

                        foreach ( $organizations as $org ) {
                            ?>
                            <option value="<?php echo esc_attr( $org['id'] ); ?>" <?php selected( $selected_org, $org['id'] ); ?>>
                                <?php echo esc_html( $org['title'] ); ?>
                            </option>
                            <?php
                        }

                        echo '</select>';
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';
                    } elseif ( is_wp_error( $organizations ) ) {
                        echo '<p>' . esc_html( $organizations->get_error_message() ) . '</p>';
                    } else {
                        echo '<p>No organizations found for this API key.</p>';
                    }
                }

                submit_button();
            ?>
        </form>

            <?php elseif ( $current_tab === 'advanced' ) : ?>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'schema_nerd_advanced_group' );
                schema_nerd_settings_active_tab_field( 'advanced' );

                $tab_file = $tabs_dir . 'advanced.php';

                if ( file_exists( $tab_file ) ) {
                    include $tab_file;
                }

                do_settings_sections( 'schema-nerd-advanced' );

                submit_button();
            ?>
        </form>

            <?php else : ?>

                <?php
                $tab_file = $tabs_dir . $current_tab . '.php';

                if ( file_exists( $tab_file ) ) {
                    include $tab_file;
                } else {
                    echo '<div class="notice notice-error"><p>Tab content not found.</p></div>';
                }
                ?>

            <?php endif; ?>

    </div>

    <?php

}



function schema_nerd_sanitize_selected_org( $value ) {
    if ( ! schema_nerd_is_saving_settings_tab( 'settings' ) ) {
        return get_option( 'schema_nerd_selected_org', '' );
    }

    $value = sanitize_text_field( $value );



    if ( $value === '' ) {

        return '';

    }



    $api_key = get_option( 'schema_nerd_api_key', '' );

    if ( $api_key === '' ) {

        return '';

    }



    $organizations = schema_nerd_fetch_organizations( $api_key );

    if ( is_wp_error( $organizations ) ) {

        return get_option( 'schema_nerd_selected_org', '' );

    }



    foreach ( $organizations as $org ) {

        if ( (int) $org['id'] === (int) $value ) {

            return (string) $org['id'];

        }

    }



    return get_option( 'schema_nerd_selected_org', '' );

}



function schema_nerd_sanitize_api_key( $value ) {
    if ( ! schema_nerd_is_saving_settings_tab( 'settings' ) ) {
        return get_option( 'schema_nerd_api_key', '' );
    }

    $value = sanitize_text_field( $value );



    if ( strpos( $value, '*' ) !== false ) {

        return get_option( 'schema_nerd_api_key', '' );

    }



    return $value;

}



function schema_nerd_sanitize_hide_location_title( $value ) {
    if ( ! schema_nerd_is_saving_settings_tab( 'advanced' ) ) {
        return (bool) get_option( 'schema_nerd_hide_location_title', false );
    }

    return rest_sanitize_boolean( $value );
}



function schema_nerd_register_settings() {

    register_setting(

        'schema_nerd_settings_group',

        'schema_nerd_api_key',

        array( 'sanitize_callback' => 'schema_nerd_sanitize_api_key' )

    );

    register_setting(

        'schema_nerd_settings_group',

        'schema_nerd_selected_org',

        array(

            'sanitize_callback' => 'schema_nerd_sanitize_selected_org',

        )

    );



    register_setting(
        'schema_nerd_advanced_group',
        'schema_nerd_hide_location_title',
        array(
            'type'              => 'boolean',
            'sanitize_callback' => 'schema_nerd_sanitize_hide_location_title',
            'default'           => false,
        )
    );

    add_settings_section(

        'schema_nerd_settings_section',

        'Schema Nerd Settings Section',

        'schema_nerd_settings_section_callback',

        'schema-nerd'

    );



    add_settings_field(

        'schema_nerd_api_key',

        'API Key (required)',

        'schema_nerd_api_key_callback',

        'schema-nerd',

        'schema_nerd_settings_section'

    );

    add_settings_section(
        'schema_nerd_advanced_section',
        'Shortcode output',
        'schema_nerd_advanced_section_callback',
        'schema-nerd-advanced'
    );

    add_settings_field(
        'schema_nerd_hide_location_title',
        'Location name',
        'schema_nerd_hide_location_title_callback',
        'schema-nerd-advanced',
        'schema_nerd_advanced_section'
    );

}

add_action( 'admin_init', 'schema_nerd_register_settings' );



function schema_nerd_settings_section_callback() {
    echo '<p>Connect this site to your Schema Nerd organization on schemanerd.app.</p>';
}

function schema_nerd_advanced_section_callback() {
    echo '<p>' . esc_html__( 'Default behavior for shortcodes and the admin shortcode builder.', 'schema-nerd' ) . '</p>';
}

function schema_nerd_api_key_callback() {
    $api_key = get_option( 'schema_nerd_api_key' );

    if ( empty( $api_key ) ) {
        echo '<input style="min-width:50%;" type="text" name="schema_nerd_api_key" value="" placeholder="Enter API Key" class="regular-text" />';
    } else {
        $masked_api_key = str_repeat( '*', max( 0, strlen( $api_key ) - 4 ) ) . substr( $api_key, -4 );
        echo '<input style="min-width:50%;" type="text" name="schema_nerd_api_key" value="' . esc_attr( $masked_api_key ) . '" placeholder="Enter API Key" class="regular-text" />';
    }

    echo '<p class="description">';
    printf(
        wp_kses(
            /* translators: %s: Schema Nerd sign-up URL */
            __( 'Need an API key? <a href="%s" target="_blank" rel="noopener noreferrer">Sign up for free at Schema Nerd</a> to create an account and get your key.', 'schema-nerd' ),
            array(
                'a' => array(
                    'href'   => array(),
                    'target' => array(),
                    'rel'    => array(),
                ),
            )
        ),
        esc_url( 'https://schemanerd.app/sign-up-for-free/' )
    );
    echo '</p>';
}

function schema_nerd_hide_location_title_callback() {
    $hide_title = (bool) get_option( 'schema_nerd_hide_location_title', false );
    ?>
    <input type="hidden" name="schema_nerd_hide_location_title" value="0">
    <label>
        <input type="checkbox" name="schema_nerd_hide_location_title" value="1" <?php checked( $hide_title ); ?>>
        <?php esc_html_e( 'Hide location name in shortcode output by default', 'schema-nerd' ); ?>
    </label>
    <p class="description"><?php esc_html_e( 'Applies to per-location shortcodes, the shortcode builder, and all-locations lists. Widgets and blocks have their own setting.', 'schema-nerd' ); ?></p>
    <?php
}

