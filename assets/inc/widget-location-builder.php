<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Schema_Nerd_Location_Builder_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'schema_nerd_location_builder',
            __( 'Schema Nerd Location Builder', 'schema-nerd' ),
            array(
                'description' => __( 'Pick a location and field to display schema data or copy the shortcode.', 'schema-nerd' ),
                'classname'   => 'schema-nerd-widget schema-nerd-widget-location-builder',
            )
        );
    }

    public function widget( $args, $instance ) {
        $locations = schema_nerd_get_locations_for_admin();

        if ( empty( $locations ) ) {
            return;
        }

        $title          = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $show_builder   = ! isset( $instance['show_builder'] ) || (bool) $instance['show_builder'];
        $show_shortcode      = ! empty( $instance['show_shortcode'] ) && current_user_can( 'edit_posts' );
        $hide_location_title = ! empty( $instance['hide_location_title'] );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided widget wrapper HTML.
        echo $args['before_widget'];

        if ( $title ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided widget title HTML.
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted plugin HTML renderer.
        echo schema_nerd_render_location_builder(
            array(
                'title'               => '',
                'show_builder'        => $show_builder,
                'show_shortcode'      => $show_shortcode,
                'hide_location_title' => $hide_location_title,
                'location'            => isset( $instance['location'] ) ? $instance['location'] : '0',
                'field'               => isset( $instance['field'] ) ? sanitize_key( $instance['field'] ) : 'phone',
                'wrapper_class'       => 'schema-nerd-widget schema-nerd-widget-location-builder',
                'builder_id'          => 'schema-nerd-widget-builder-' . esc_attr( $this->id ),
                'context'             => 'widget',
            )
        );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme-provided widget wrapper HTML.
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $locations      = schema_nerd_get_locations_for_admin();
        $title          = isset( $instance['title'] ) ? $instance['title'] : __( 'Find a Location', 'schema-nerd' );
        $show_builder   = ! isset( $instance['show_builder'] ) || (bool) $instance['show_builder'];
        $show_shortcode      = ! empty( $instance['show_shortcode'] );
        $hide_location_title = ! empty( $instance['hide_location_title'] );
        $location_ref        = isset( $instance['location'] ) ? $instance['location'] : '0';
        $field          = isset( $instance['field'] ) ? sanitize_key( $instance['field'] ) : 'phone';
        $field_labels   = schema_nerd_get_location_field_labels();
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'schema-nerd' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <input class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_builder' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_builder' ) ); ?>" type="checkbox" <?php checked( $show_builder ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_builder' ) ); ?>"><?php esc_html_e( 'Show interactive location builder', 'schema-nerd' ); ?></label>
        </p>
        <p>
            <input class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_shortcode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_shortcode' ) ); ?>" type="checkbox" <?php checked( $show_shortcode ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_shortcode' ) ); ?>"><?php esc_html_e( 'Show shortcode copy box to editors', 'schema-nerd' ); ?></label>
        </p>
        <p>
            <input class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hide_location_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hide_location_title' ) ); ?>" type="checkbox" <?php checked( $hide_location_title ); ?>>
            <label for="<?php echo esc_attr( $this->get_field_id( 'hide_location_title' ) ); ?>"><?php esc_html_e( 'Hide location name in output', 'schema-nerd' ); ?></label>
        </p>
        <?php if ( ! $show_builder && ! empty( $locations ) ) : ?>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'location' ) ); ?>"><?php esc_html_e( 'Location:', 'schema-nerd' ); ?></label>
                <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'location' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'location' ) ); ?>">
                    <?php foreach ( $locations as $choice ) : ?>
                        <option value="<?php echo esc_attr( $choice['index'] ); ?>" <?php selected( (string) $location_ref, (string) $choice['index'] ); ?>>
                            <?php echo esc_html( $choice['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'field' ) ); ?>"><?php esc_html_e( 'Field:', 'schema-nerd' ); ?></label>
                <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'field' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'field' ) ); ?>">
                    <?php foreach ( $field_labels as $field_key => $field_label ) : ?>
                        <option value="<?php echo esc_attr( $field_key ); ?>" <?php selected( $field, $field_key ); ?>>
                            <?php echo esc_html( $field_label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
        <?php endif; ?>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance                   = array();
        $instance['title']          = sanitize_text_field( $new_instance['title'] ?? '' );
        $instance['show_builder']   = ! empty( $new_instance['show_builder'] );
        $instance['show_shortcode']      = ! empty( $new_instance['show_shortcode'] );
        $instance['hide_location_title'] = ! empty( $new_instance['hide_location_title'] );
        $instance['location']            = isset( $new_instance['location'] ) ? sanitize_text_field( $new_instance['location'] ) : '0';
        $instance['field']          = isset( $new_instance['field'] ) ? sanitize_key( $new_instance['field'] ) : 'phone';

        return $instance;
    }
}

function schema_nerd_register_location_builder_widget() {
    register_widget( 'Schema_Nerd_Location_Builder_Widget' );
}
add_action( 'widgets_init', 'schema_nerd_register_location_builder_widget' );

function schema_nerd_ajax_location_widget_preview() {
    check_ajax_referer( 'schema_nerd_shortcodes', 'nonce' );

    $location_ref = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '0';
    $field        = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
    $hide_title   = isset( $_POST['hide_title'] ) ? sanitize_text_field( wp_unslash( $_POST['hide_title'] ) ) : null;
    $location     = schema_nerd_get_location_by_ref( $location_ref );

    if ( ! $location || ! schema_nerd_location_has_field( $location, $field ) ) {
        wp_send_json_error( array( 'message' => __( 'This field is not available for the selected location.', 'schema-nerd' ) ) );
    }

    $shortcode = schema_nerd_format_location_shortcode( $field, $location_ref, $hide_title );
    $preview   = do_shortcode( $shortcode );

    wp_send_json_success(
        array(
            'shortcode' => current_user_can( 'edit_posts' ) ? $shortcode : '',
            'preview'   => $preview,
        )
    );
}
add_action( 'wp_ajax_schema_nerd_location_widget_preview', 'schema_nerd_ajax_location_widget_preview' );
add_action( 'wp_ajax_nopriv_schema_nerd_location_widget_preview', 'schema_nerd_ajax_location_widget_preview' );

function schema_nerd_register_builder_assets() {
    $asset_path = dirname( dirname( __FILE__ ) );

    wp_register_style(
        'sn-widget',
        SN_CORE_CSS . 'sn-widget.css',
        array( 'sn-core' ),
        filemtime( $asset_path . '/css/sn-widget.css' )
    );

    wp_register_script(
        'sn-location-builder',
        SN_CORE_JS . 'sn-location-builder.js',
        array( 'jquery' ),
        filemtime( $asset_path . '/js/sn-location-builder.js' ),
        true
    );

    wp_localize_script(
        'sn-location-builder',
        'schemaNerdBuilder',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'i18n'    => array(
                'loading' => __( 'Loading preview...', 'schema-nerd' ),
                'error'   => __( 'Could not load preview.', 'schema-nerd' ),
                'copied'  => __( 'Copied', 'schema-nerd' ),
                'copy'    => __( 'Copy shortcode', 'schema-nerd' ),
            ),
        )
    );
}
add_action( 'init', 'schema_nerd_register_builder_assets' );
