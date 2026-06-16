<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function schema_nerd_register_location_builder_block() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    $plugin_dir = dirname( dirname( dirname( __FILE__ ) ) );
    $block_dir  = $plugin_dir . '/blocks/location-builder';

    wp_register_script(
        'schema-nerd-block-location-builder-editor',
        SN_CORE_JS . 'block-location-builder.js',
        array(
            'wp-blocks',
            'wp-element',
            'wp-block-editor',
            'wp-components',
            'wp-i18n',
            'wp-server-side-render',
        ),
        filemtime( $plugin_dir . '/assets/js/block-location-builder.js' ),
        true
    );

    wp_localize_script(
        'schema-nerd-block-location-builder-editor',
        'schemaNerdBlock',
        array(
            'locations' => schema_nerd_get_locations_for_admin(),
            'fields'    => schema_nerd_get_location_field_labels(),
        )
    );

    register_block_type(
        $block_dir,
        array(
            'render_callback' => 'schema_nerd_render_location_builder_block',
        )
    );
}
add_action( 'init', 'schema_nerd_register_location_builder_block' );

function schema_nerd_block_editor_assets() {
    wp_enqueue_style( 'sn-widget' );
    wp_enqueue_script( 'sn-location-builder' );
}
add_action( 'enqueue_block_editor_assets', 'schema_nerd_block_editor_assets' );
