<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function schema_nerd_is_block_editor_preview_request( $attributes = array() ) {
    if ( ! empty( $attributes['editorPreview'] ) ) {
        return true;
    }

    if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
        return false;
    }

    if ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
        $route = (string) $GLOBALS['wp']->query_vars['rest_route'];

        if ( strpos( $route, '/block-renderer/' ) !== false ) {
            return true;
        }
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

    return strpos( $request_uri, '/block-renderer/' ) !== false
        || strpos( $request_uri, 'block-renderer' ) !== false;
}

/**
 * Shared location builder output for widget and block.
 *
 * @param array $args {
 *     @type string $title          Optional heading.
 *     @type bool   $show_builder   Interactive builder vs fixed shortcode.
 *     @type bool   $show_shortcode Show shortcode copy UI to editors.
 *     @type string $location       Location ref when builder is off.
 *     @type string $field          Field key when builder is off.
 *     @type string $wrapper_class  Outer wrapper class(es).
 *     @type string $builder_id     Unique builder element ID.
 *     @type string $context        admin|widget|block
 * }
 * @return string
 */
function schema_nerd_render_location_builder( $args = array() ) {
    $args = wp_parse_args(
        $args,
        array(
            'title'          => '',
            'show_builder'   => true,
            'show_shortcode' => false,
            'hide_location_title' => false,
            'location'       => '0',
            'field'          => 'phone',
            'wrapper_class'  => 'schema-nerd-location-builder',
            'builder_id'     => 'schema-nerd-builder-' . wp_unique_id(),
            'context'        => 'widget',
        )
    );

    $locations = schema_nerd_get_locations_for_admin();

    if ( empty( $locations ) ) {
        return '<p class="schema-nerd-no-locations">' . esc_html__( 'No locations available in the current schema.', 'schema-nerd' ) . '</p>';
    }

    if ( $args['show_builder'] ) {
        wp_enqueue_style( 'sn-core' );
        wp_enqueue_style( 'sn-widget' );
        wp_enqueue_script( 'sn-location-builder' );
    }

    ob_start();

    echo '<div class="' . esc_attr( $args['wrapper_class'] ) . '">';

    if ( $args['title'] !== '' ) {
        echo '<h3 class="schema-nerd-location-builder-title">' . esc_html( $args['title'] ) . '</h3>';
    }

    if ( $args['show_builder'] ) {
        $schema_nerd_builder_id          = $args['builder_id'];
        $schema_nerd_context             = $args['context'];
        $schema_nerd_location_choices    = $locations;
        $schema_nerd_show_shortcode      = $args['show_shortcode'] && current_user_can( 'edit_posts' );
        $schema_nerd_hide_location_title = isset( $args['hide_location_title'] ) ? (bool) $args['hide_location_title'] : schema_nerd_is_location_title_hidden();
        include SN_CORE_INC . 'partials/shortcode-builder.php';
    } else {
        $hide_title = isset( $args['hide_location_title'] ) ? $args['hide_location_title'] : null;
        echo do_shortcode( schema_nerd_format_location_shortcode( $args['field'], $args['location'], $hide_title ) );
    }

    echo '</div>';

    return ob_get_clean();
}

function schema_nerd_location_builder_block_attributes( $attributes ) {
    $is_editor_preview = schema_nerd_is_block_editor_preview_request( $attributes );
    $show_builder      = $is_editor_preview && ( ! isset( $attributes['showBuilder'] ) || (bool) $attributes['showBuilder'] );

    return array(
        'title'          => isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '',
        'show_builder'   => $show_builder,
        'show_shortcode' => ! empty( $attributes['showShortcode'] ),
        'hide_location_title' => ! empty( $attributes['hideLocationTitle'] ),
        'location'       => isset( $attributes['location'] ) ? sanitize_text_field( $attributes['location'] ) : '0',
        'field'          => isset( $attributes['field'] ) ? sanitize_key( $attributes['field'] ) : 'phone',
        'wrapper_class'  => 'schema-nerd-block-location-builder wp-block-schema-nerd-location-builder',
        'builder_id'     => 'schema-nerd-block-builder-' . wp_unique_id(),
        'context'        => 'block',
    );
}

function schema_nerd_render_location_builder_block( $attributes ) {
    return schema_nerd_render_location_builder( schema_nerd_location_builder_block_attributes( $attributes ) );
}
