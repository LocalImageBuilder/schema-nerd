<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$schema_nerd_location_choices = isset( $schema_nerd_location_choices ) ? $schema_nerd_location_choices : schema_nerd_get_locations_for_admin();
$schema_nerd_field_labels     = schema_nerd_get_location_field_labels();
$schema_nerd_context          = isset( $schema_nerd_context ) ? $schema_nerd_context : 'admin';
$schema_nerd_builder_id       = isset( $schema_nerd_builder_id ) ? $schema_nerd_builder_id : 'schema-nerd-builder-' . wp_unique_id();
$schema_nerd_show_shortcode       = isset( $schema_nerd_show_shortcode ) ? (bool) $schema_nerd_show_shortcode : true;
$schema_nerd_hide_location_title  = isset( $schema_nerd_hide_location_title ) ? (bool) $schema_nerd_hide_location_title : schema_nerd_is_location_title_hidden();
$schema_nerd_nonce                = isset( $schema_nerd_nonce ) ? $schema_nerd_nonce : wp_create_nonce( 'schema_nerd_shortcodes' );
$schema_nerd_ajax_action      = in_array( $schema_nerd_context, array( 'widget', 'block' ), true ) ? 'schema_nerd_location_widget_preview' : 'schema_nerd_location_shortcode';

if ( empty( $schema_nerd_location_choices ) ) {
    echo '<p class="schema-nerd-no-locations">' . esc_html__( 'No locations available in the current schema.', 'schema-nerd' ) . '</p>';
    return;
}
?>
<div
    class="schema-nerd-shortcode-builder schema-nerd-shortcode-builder--<?php echo esc_attr( $schema_nerd_context ); ?>"
    id="<?php echo esc_attr( $schema_nerd_builder_id ); ?>"
    data-context="<?php echo esc_attr( $schema_nerd_context ); ?>"
    data-nonce="<?php echo esc_attr( $schema_nerd_nonce ); ?>"
    data-ajax-action="<?php echo esc_attr( $schema_nerd_ajax_action ); ?>"
    data-hide-location-title="<?php echo esc_attr( $schema_nerd_hide_location_title ? '1' : '0' ); ?>"
>
    <div class="schema-nerd-shortcode-builder-controls">
        <label class="schema-nerd-shortcode-builder-label" for="<?php echo esc_attr( $schema_nerd_builder_id ); ?>-location">
            <?php esc_html_e( 'Location', 'schema-nerd' ); ?>
        </label>
        <select id="<?php echo esc_attr( $schema_nerd_builder_id ); ?>-location" class="schema-nerd-location-select">
            <?php foreach ( $schema_nerd_location_choices as $schema_nerd_choice ) : ?>
                <option
                    value="<?php echo esc_attr( $schema_nerd_choice['index'] ); ?>"
                    data-fields="<?php echo esc_attr( wp_json_encode( $schema_nerd_choice['fields'] ) ); ?>"
                    data-slug="<?php echo esc_attr( $schema_nerd_choice['slug'] ); ?>"
                >
                    <?php
                    /* translators: %d: location number */
                    echo esc_html( $schema_nerd_choice['name'] !== '' ? $schema_nerd_choice['name'] : sprintf( __( 'Location %d', 'schema-nerd' ), $schema_nerd_choice['index'] + 1 ) );
                    ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="schema-nerd-field-buttons" role="group" aria-label="<?php esc_attr_e( 'Shortcode field', 'schema-nerd' ); ?>">
            <?php foreach ( $schema_nerd_field_labels as $schema_nerd_field_key => $schema_nerd_field_label ) : ?>
                <button type="button" class="button schema-nerd-field-button schema-nerd-field-button--<?php echo esc_attr( $schema_nerd_field_key ); ?>" data-field="<?php echo esc_attr( $schema_nerd_field_key ); ?>" aria-pressed="false">
                    <?php echo esc_html( $schema_nerd_field_label ); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <p class="schema-nerd-hide-location-title-wrap">
            <label>
                <input
                    type="checkbox"
                    class="schema-nerd-hide-location-title"
                    <?php checked( $schema_nerd_hide_location_title ); ?>
                >
                <?php esc_html_e( 'Hide location name in output', 'schema-nerd' ); ?>
            </label>
        </p>
    </div>

    <div class="schema-nerd-shortcode-builder-result">
        <?php if ( $schema_nerd_show_shortcode ) : ?>
            <div class="schema-nerd-shortcode-copy schema-nerd-shortcode-copy--builder">
                <code class="schema-nerd-shortcode-tag schema-nerd-builder-shortcode"></code>
                <button type="button" class="button button-secondary schema-nerd-copy-shortcode" disabled>
                    <?php esc_html_e( 'Copy shortcode', 'schema-nerd' ); ?>
                </button>
            </div>
        <?php endif; ?>

        <div class="schema-nerd-shortcode-preview schema-nerd-builder-preview">
            <span class="schema-nerd-shortcode-preview-label"><?php esc_html_e( 'Preview', 'schema-nerd' ); ?></span>
            <div class="schema-nerd-builder-preview-content"></div>
        </div>
    </div>
</div>
