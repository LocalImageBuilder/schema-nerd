<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$schema_nerd_api_key          = get_option( 'schema_nerd_api_key' );
$schema_nerd_selected_org     = get_option( 'schema_nerd_selected_org' );
$schema_nerd_locations        = schema_nerd_get_locations();
$schema_nerd_shortcodes       = schema_nerd_get_available_shortcodes();
$schema_nerd_location_choices = schema_nerd_get_locations_for_admin();
$schema_nerd_field_labels     = schema_nerd_get_location_field_labels();
$schema_nerd_has_multi        = count( $schema_nerd_locations ) > 1;
?>
<div class="schema-nerd-shortcodes-tab">
    <h2>Helpful Shortcodes</h2>

    <?php if ( empty( $schema_nerd_api_key ) || empty( $schema_nerd_selected_org ) ) : ?>
        <p>Save an API key and select an organization on the Settings tab to see available shortcodes.</p>
    <?php elseif ( empty( $schema_nerd_locations ) ) : ?>
        <p>No location data was found in the current organization schema. Confirm your organization is configured on schemanerd.app.</p>
    <?php else : ?>
        <p>
            <?php
            $schema_nerd_location_count = count( $schema_nerd_locations );
            echo esc_html(
                sprintf(
                    /* translators: %d: number of locations in the organization schema */
                    _n(
                        'Based on your current schema: %d location.',
                        'Based on your current schema: %d locations.',
                        $schema_nerd_location_count,
                        'schema-nerd'
                    ),
                    $schema_nerd_location_count
                )
            );
            ?>
        </p>

        <?php if ( $schema_nerd_has_multi ) : ?>
            <h3>Per-location shortcode builder</h3>
            <p>Choose a location and field to generate a shortcode for that location only. You can also add the <strong>Schema Nerd Location Builder</strong> block in the editor, or the widget under Appearance → Widgets.</p>
            <?php
            $schema_nerd_context    = 'admin';
            $schema_nerd_builder_id = 'schema-nerd-admin-builder';
            include SN_CORE_INC . 'partials/shortcode-builder.php';
            ?>
        <?php endif; ?>

        <?php
        $schema_nerd_current_group = '';
        foreach ( $schema_nerd_shortcodes as $schema_nerd_shortcode ) :
            if ( ! $schema_nerd_has_multi || $schema_nerd_shortcode['group'] !== 'All locations' ) {
                continue;
            }

            if ( $schema_nerd_shortcode['group'] !== $schema_nerd_current_group ) :
                $schema_nerd_current_group = $schema_nerd_shortcode['group'];
                echo '<h3>' . esc_html( $schema_nerd_current_group ) . '</h3>';
            endif;

            $schema_nerd_tag     = $schema_nerd_shortcode['tag'];
            $schema_nerd_code    = '[' . $schema_nerd_tag . ']';
            $schema_nerd_preview = do_shortcode( $schema_nerd_code );
            ?>
            <div class="schema-nerd-shortcode-card">
                <div class="schema-nerd-shortcode-meta">
                    <strong><?php echo esc_html( $schema_nerd_shortcode['label'] ); ?></strong>
                    <p><?php echo esc_html( $schema_nerd_shortcode['description'] ); ?></p>
                    <div class="schema-nerd-shortcode-copy">
                        <code class="schema-nerd-shortcode-tag"><?php echo esc_html( $schema_nerd_code ); ?></code>
                        <button type="button" class="button button-secondary schema-nerd-copy-shortcode" data-shortcode="<?php echo esc_attr( $schema_nerd_code ); ?>">Copy</button>
                    </div>
                </div>
                <?php if ( $schema_nerd_preview !== '' ) : ?>
                    <div class="schema-nerd-shortcode-preview">
                        <span class="schema-nerd-shortcode-preview-label">Preview</span>
                        <?php echo wp_kses_post( $schema_nerd_preview ); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ( ! $schema_nerd_has_multi && ! empty( $schema_nerd_location_choices[0]['fields'] ) ) : ?>
            <h3>Location</h3>
            <?php
            foreach ( $schema_nerd_location_choices[0]['fields'] as $schema_nerd_field_key ) :
                $schema_nerd_code    = schema_nerd_format_location_shortcode( $schema_nerd_field_key, 0 );
                $schema_nerd_preview = do_shortcode( $schema_nerd_code );
                ?>
                <div class="schema-nerd-shortcode-card">
                    <div class="schema-nerd-shortcode-meta">
                        <strong><?php echo esc_html( $schema_nerd_field_labels[ $schema_nerd_field_key ] ); ?></strong>
                        <p><?php echo esc_html( sprintf( '%s for this location.', $schema_nerd_field_labels[ $schema_nerd_field_key ] ) ); ?></p>
                        <div class="schema-nerd-shortcode-copy">
                            <code class="schema-nerd-shortcode-tag"><?php echo esc_html( $schema_nerd_code ); ?></code>
                            <button type="button" class="button button-secondary schema-nerd-copy-shortcode" data-shortcode="<?php echo esc_attr( $schema_nerd_code ); ?>">Copy</button>
                        </div>
                    </div>
                    <?php if ( $schema_nerd_preview !== '' ) : ?>
                        <div class="schema-nerd-shortcode-preview">
                            <span class="schema-nerd-shortcode-preview-label">Preview</span>
                            <?php echo wp_kses_post( $schema_nerd_preview ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ( $schema_nerd_has_multi && empty( array_filter( $schema_nerd_shortcodes, function ( $item ) {
            return $item['group'] === 'All locations';
        } ) ) && empty( $schema_nerd_location_choices ) ) : ?>
            <p>No shortcodes are available for the fields in your current schema.</p>
        <?php elseif ( ! $schema_nerd_has_multi && empty( $schema_nerd_location_choices[0]['fields'] ) ) : ?>
            <p>No shortcodes are available for the fields in your current schema.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
