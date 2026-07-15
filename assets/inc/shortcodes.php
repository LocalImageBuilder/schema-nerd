<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function schema_nerd_get_locations() {
    static $locations = null;

    if ( $locations !== null ) {
        return $locations;
    }

    $locations = array();
    $schema    = schema_nerd_get_organization_schema();

    if ( empty( $schema ) || ! is_string( $schema ) ) {
        return $locations;
    }

    $json_start = strpos( $schema, '{' );
    $json_end   = strrpos( $schema, '}' );

    if ( $json_start === false || $json_end === false ) {
        return $locations;
    }

    $json_string = substr( $schema, $json_start, $json_end - $json_start + 1 );
    $data        = json_decode( $json_string, true );

    if ( ! is_array( $data ) || empty( $data['@graph'] ) ) {
        return $locations;
    }

    foreach ( $data['@graph'] as $item ) {
        if ( ! isset( $item['@type'] ) ) {
            continue;
        }

        $type = $item['@type'];
        if ( $type === 'LocalBusiness' || ( is_array( $type ) && in_array( 'LocalBusiness', $type, true ) ) ) {
            $locations[] = $item;
        }
    }

    return $locations;
}

function schema_nerd_get_business_data() {
    $locations = schema_nerd_get_locations();

    return ! empty( $locations ) ? $locations[0] : array();
}

function schema_nerd_get_location_name( $location ) {
    if ( ! empty( $location['name'] ) ) {
        return $location['name'];
    }

    return '';
}

function schema_nerd_get_location_slug( $location, $index ) {
    $name = schema_nerd_get_location_name( $location );

    if ( $name !== '' ) {
        return sanitize_title( $name );
    }

    return 'location-' . (int) $index;
}

function schema_nerd_get_location_index( $location ) {
    $locations = schema_nerd_get_locations();

    foreach ( $locations as $index => $item ) {
        if ( $item === $location ) {
            return (int) $index;
        }
    }

    $target_name = schema_nerd_get_location_name( $location );
    foreach ( $locations as $index => $item ) {
        if ( schema_nerd_get_location_name( $item ) === $target_name ) {
            return (int) $index;
        }
    }

    return 0;
}

function schema_nerd_get_location_id_attr( $location, $index ) {
    return 'sn-location-' . (int) $index . '-' . schema_nerd_get_location_slug( $location, $index );
}

function schema_nerd_get_location_classes( $location, $index, $element = '', $legacy = array() ) {
    $slug    = schema_nerd_get_location_slug( $location, $index );
    $classes = array_merge(
        (array) $legacy,
        array(
            'schema-nerd-location',
            'schema-nerd-location-' . (int) $index,
            'schema-nerd-location--' . $slug,
        )
    );

    if ( $element ) {
        $classes[] = 'schema-nerd-' . $element;
        $classes[] = 'schema-nerd-location-' . $element;
        $classes[] = 'schema-nerd-location-' . (int) $index . '-' . $element;
        $classes[] = 'schema-nerd-location--' . $slug . '-' . $element;
    }

    return esc_attr( implode( ' ', array_unique( $classes ) ) );
}

function schema_nerd_get_location_phone( $location ) {
    if ( ! empty( $location['telephone'] ) ) {
        return $location['telephone'];
    }

    if ( ! empty( $location['address']['telephone'] ) ) {
        return $location['address']['telephone'];
    }

    return '';
}

function schema_nerd_get_location_email( $location ) {
    if ( ! empty( $location['email'] ) ) {
        return $location['email'];
    }

    return '';
}

function schema_nerd_format_phone_link( $phone, $location = null, $index = 0 ) {
    $clean_phone     = preg_replace( '/[^0-9+]/', '', $phone );
    $formatted_phone = preg_replace( '/(\d{3})(\d{3})(\d{4})/', '$1-$2-$3', $clean_phone );
    $class           = 'schema-nerd-phone-link';

    if ( $location ) {
        $class = schema_nerd_get_location_classes( $location, $index, 'phone-link' );
    }

    return '<a class="' . $class . '" href="tel:' . esc_attr( $clean_phone ) . '">' . esc_html( $formatted_phone ) . '</a>';
}

function schema_nerd_format_email_link( $email, $location = null, $index = 0 ) {
    $class = 'schema-nerd-email-link';

    if ( $location ) {
        $class = schema_nerd_get_location_classes( $location, $index, 'email-link' );
    }

    return '<a class="' . $class . '" href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
}

function schema_nerd_maybe_enqueue_assets() {
    if ( function_exists( 'schema_nerd_enqueue_front_assets' ) ) {
        schema_nerd_enqueue_front_assets();
    }
}

function schema_nerd_render_location_field( $location, $field, $index = null ) {
    if ( null === $index ) {
        $index = schema_nerd_get_location_index( $location );
    }

    switch ( $field ) {
        case 'phone':
            $phone = schema_nerd_get_location_phone( $location );
            if ( $phone === '' ) {
                return '';
            }
            return '<div class="' . schema_nerd_get_location_classes( $location, $index, 'phone', array( 'business-section', 'phone-section' ) ) . '"><p class="' . schema_nerd_get_location_classes( $location, $index, 'phone-text' ) . '">' . schema_nerd_format_phone_link( $phone, $location, $index ) . '</p></div>';

        case 'address':
            $address = schema_nerd_render_location_address( $location, $index );
            if ( $address === '' ) {
                return '';
            }
            return '<div class="' . schema_nerd_get_location_classes( $location, $index, 'address', array( 'business-section', 'address-section' ) ) . '">' . $address . '</div>';

        case 'hours':
            $hours = schema_nerd_get_location_hours_text( $location );
            if ( $hours === '' ) {
                return '';
            }
            return '<div class="' . schema_nerd_get_location_classes( $location, $index, 'hours', array( 'business-section', 'hours-section' ) ) . '"><pre class="' . schema_nerd_get_location_classes( $location, $index, 'hours-text' ) . '">' . esc_html( $hours ) . '</pre></div>';

        case 'email':
            $email = schema_nerd_get_location_email( $location );
            if ( $email === '' ) {
                return '';
            }
            return '<div class="' . schema_nerd_get_location_classes( $location, $index, 'email', array( 'business-section', 'email-section' ) ) . '"><p class="' . schema_nerd_get_location_classes( $location, $index, 'email-text' ) . '">' . schema_nerd_format_email_link( $email, $location, $index ) . '</p></div>';
    }

    return '';
}

function schema_nerd_get_location_by_ref( $ref ) {
    $locations = schema_nerd_get_locations();

    if ( empty( $locations ) ) {
        return null;
    }

    if ( $ref === '' || $ref === null ) {
        return $locations[0];
    }

    if ( is_numeric( $ref ) ) {
        $index = (int) $ref;
        return isset( $locations[ $index ] ) ? $locations[ $index ] : null;
    }

    $ref_lower = strtolower( trim( (string) $ref ) );
    foreach ( $locations as $location ) {
        if ( strtolower( schema_nerd_get_location_name( $location ) ) === $ref_lower ) {
            return $location;
        }
    }

    $ref_slug = sanitize_title( (string) $ref );
    foreach ( $locations as $index => $location ) {
        if ( schema_nerd_get_location_slug( $location, $index ) === $ref_slug ) {
            return $location;
        }
    }

    return null;
}

function schema_nerd_is_location_title_hidden( $hide_title = null ) {
    if ( null !== $hide_title ) {
        return filter_var( $hide_title, FILTER_VALIDATE_BOOLEAN );
    }

    return (bool) get_option( 'schema_nerd_hide_location_title', false );
}

function schema_nerd_format_location_shortcode( $field, $location_ref, $hide_title = null ) {
    $allowed = array( 'phone', 'address', 'hours', 'email' );
    if ( ! in_array( $field, $allowed, true ) ) {
        return '';
    }

    if ( is_numeric( $location_ref ) ) {
        $code = '[schema_nerd_location field="' . $field . '" location="' . (int) $location_ref . '"]';
    } else {
        $code = '[schema_nerd_location field="' . $field . '" location="' . sanitize_title( (string) $location_ref ) . '"]';
    }

    if ( schema_nerd_is_location_title_hidden( $hide_title ) ) {
        $code = str_replace( ']', ' hide_title="1"]', $code );
    }

    return $code;
}

function schema_nerd_get_location_field_labels() {
    return array(
        'phone'   => 'Phone',
        'address' => 'Address',
        'hours'   => 'Hours',
        'email'   => 'Email',
    );
}

function schema_nerd_get_locations_for_admin() {
    $locations = schema_nerd_get_locations();
    $choices   = array();

    foreach ( $locations as $index => $location ) {
        $fields = array();
        foreach ( array_keys( schema_nerd_get_location_field_labels() ) as $field ) {
            if ( schema_nerd_location_has_field( $location, $field ) ) {
                $fields[] = $field;
            }
        }

        $choices[] = array(
            'index'  => $index,
            'name'   => schema_nerd_get_location_name( $location ),
            'slug'   => schema_nerd_get_location_slug( $location, $index ),
            'fields' => $fields,
        );
    }

    return $choices;
}

function schema_nerd_normalize_opening_hours_strings( $opening_hours ) {
    if ( empty( $opening_hours ) ) {
        return array();
    }

    if ( is_string( $opening_hours ) ) {
        $opening_hours = trim( $opening_hours );
        return $opening_hours === '' ? array() : array( $opening_hours );
    }

    if ( ! is_array( $opening_hours ) ) {
        return array();
    }

    $strings = array();
    foreach ( $opening_hours as $item ) {
        if ( is_string( $item ) ) {
            $item = trim( $item );
            if ( $item !== '' ) {
                $strings[] = $item;
            }
        }
    }

    return $strings;
}

function schema_nerd_is_open_24_7_hours_string( $hours_string ) {
    $hours_string = preg_replace( '/\s+/', ' ', trim( (string) $hours_string ) );

    if ( $hours_string === '' ) {
        return false;
    }

    $patterns = array(
        '/^Mo-Su\s+00:00-00:00$/i',
        '/^Mo-Su\s+00:00-24:00$/i',
        '/^00:00-00:00$/i',
        '/^00:00-24:00$/i',
    );

    foreach ( $patterns as $pattern ) {
        if ( preg_match( $pattern, $hours_string ) ) {
            return true;
        }
    }

    return false;
}

function schema_nerd_is_open_24_7_opening_hours( $opening_hours ) {
    foreach ( schema_nerd_normalize_opening_hours_strings( $opening_hours ) as $hours_string ) {
        if ( schema_nerd_is_open_24_7_hours_string( $hours_string ) ) {
            return true;
        }
    }

    return false;
}

function schema_nerd_get_location_hours_text( $location ) {
    if ( ! empty( $location['openingHours'] ) ) {
        if ( schema_nerd_is_open_24_7_opening_hours( $location['openingHours'] ) ) {
            return 'Open 24/7';
        }

        $strings = schema_nerd_normalize_opening_hours_strings( $location['openingHours'] );
        if ( ! empty( $strings ) ) {
            return implode( "\n", $strings );
        }
    }

    if ( ! empty( $location['openingHoursSpecification'] ) ) {
        return schema_nerd_format_opening_hours( $location['openingHoursSpecification'] );
    }

    return '';
}

function schema_nerd_normalize_opening_hours( $opening_hours ) {
    if ( empty( $opening_hours ) ) {
        return array();
    }

    if ( isset( $opening_hours['dayOfWeek'] ) ) {
        return array( $opening_hours );
    }

    return is_array( $opening_hours ) ? $opening_hours : array();
}

function schema_nerd_military_to_ampm( $time ) {
    return gmdate( 'g:ia', strtotime( $time ) );
}

function schema_nerd_format_opening_hours( $opening_hours ) {
    $formatted_hours = array();

    foreach ( schema_nerd_normalize_opening_hours( $opening_hours ) as $hours ) {
        if ( empty( $hours['dayOfWeek'] ) ) {
            continue;
        }

        $day    = is_array( $hours['dayOfWeek'] ) ? $hours['dayOfWeek'][0] : $hours['dayOfWeek'];
        $opens  = schema_nerd_military_to_ampm( $hours['opens'] );
        $closes = schema_nerd_military_to_ampm( $hours['closes'] );
        $formatted_hours[] = "$day: $opens - $closes";
    }

    return implode( "\n", $formatted_hours );
}

function schema_nerd_render_location_address( $location, $index = null ) {
    if ( empty( $location['address'] ) || ! is_array( $location['address'] ) ) {
        return '';
    }

    if ( null === $index ) {
        $index = schema_nerd_get_location_index( $location );
    }

    $address = $location['address'];
    $output  = '<address class="' . schema_nerd_get_location_classes( $location, $index, 'address-block' ) . '">';

    if ( ! empty( $address['streetAddress'] ) ) {
        $output .= '<span class="' . schema_nerd_get_location_classes( $location, $index, 'address-street' ) . '">' . esc_html( $address['streetAddress'] ) . '</span><br>';
    }
    if ( ! empty( $address['addressLocality'] ) ) {
        $output .= '<span class="' . schema_nerd_get_location_classes( $location, $index, 'address-locality' ) . '">' . esc_html( $address['addressLocality'] ) . '</span>';
    }
    if ( ! empty( $address['addressRegion'] ) ) {
        $output .= '<span class="' . schema_nerd_get_location_classes( $location, $index, 'address-region' ) . '">, ' . esc_html( trim( $address['addressRegion'] ) ) . '</span>';
    }
    if ( ! empty( $address['postalCode'] ) ) {
        $output .= ' <span class="' . schema_nerd_get_location_classes( $location, $index, 'address-postal' ) . '">' . esc_html( $address['postalCode'] ) . '</span>';
    }
    if ( ! empty( $address['postalCode'] ) || ! empty( $address['addressLocality'] ) ) {
        $output .= '<br>';
    }
    if ( ! empty( $address['addressCountry'] ) ) {
        $output .= '<span class="' . schema_nerd_get_location_classes( $location, $index, 'address-country' ) . '">' . esc_html( $address['addressCountry'] ) . '</span>';
    }

    $output .= '</address>';

    if ( ! empty( $address['streetAddress'] ) && ! empty( $address['addressLocality'] ) ) {
        $maps_query = urlencode( $address['streetAddress'] . ', ' . $address['addressLocality'] );
        $output    .= '<p class="' . schema_nerd_get_location_classes( $location, $index, 'maps-link-wrap' ) . '"><a class="' . schema_nerd_get_location_classes( $location, $index, 'maps-link' ) . '" href="https://maps.google.com?q=' . esc_attr( $maps_query ) . '" target="_blank" rel="noopener noreferrer">View on Google Maps</a></p>';
    }

    return $output;
}

function schema_nerd_render_locations_list( $field ) {
    $locations = schema_nerd_get_locations();

    if ( empty( $locations ) ) {
        return '';
    }

    schema_nerd_maybe_enqueue_assets();
    $output = '<div class="schema-nerd-locations schema-nerd-locations-' . esc_attr( $field ) . ' schema-nerd-locations-list">';

    foreach ( $locations as $index => $location ) {
        $name   = schema_nerd_get_location_name( $location );
        $id_attr = schema_nerd_get_location_id_attr( $location, $index );
        $output .= '<div id="' . esc_attr( $id_attr ) . '" class="' . schema_nerd_get_location_classes( $location, $index, 'item' ) . '">';

        if ( $name !== '' && ! schema_nerd_is_location_title_hidden() ) {
            $output .= '<h3 class="' . schema_nerd_get_location_classes( $location, $index, 'name' ) . '">' . esc_html( $name ) . '</h3>';
        }

        $field_content = schema_nerd_render_location_field( $location, $field, $index );
        if ( $field_content !== '' ) {
            $output .= $field_content;
        }

        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}

function schema_nerd_location_has_field( $location, $field ) {
    switch ( $field ) {
        case 'phone':
            return schema_nerd_get_location_phone( $location ) !== '';
        case 'address':
            return ! empty( $location['address'] ) && is_array( $location['address'] );
        case 'hours':
            if ( ! empty( $location['openingHours'] ) ) {
                return ! empty( schema_nerd_normalize_opening_hours_strings( $location['openingHours'] ) );
            }
            return ! empty( $location['openingHoursSpecification'] );
        case 'fax':
            return ! empty( $location['address']['faxNumber'] );
        case 'email':
            return schema_nerd_get_location_email( $location ) !== '';
    }

    return false;
}

function schema_nerd_get_available_shortcodes() {
    $locations  = schema_nerd_get_locations();
    $shortcodes = array();

    if ( empty( $locations ) ) {
        return $shortcodes;
    }

    $first = $locations[0];
    $multi = count( $locations ) > 1;

    $single_defs = array(
        'schema_nerd_phone'   => array(
            'label'       => 'Phone',
            'description' => 'Phone number for the first location.',
            'field'       => 'phone',
        ),
        'schema_nerd_address' => array(
            'label'       => 'Address',
            'description' => 'Address for the first location.',
            'field'       => 'address',
        ),
        'schema_nerd_hours'   => array(
            'label'       => 'Hours',
            'description' => 'Opening hours for the first location.',
            'field'       => 'hours',
        ),
        'schema_nerd_fax'     => array(
            'label'       => 'Fax',
            'description' => 'Fax number for the first location.',
            'field'       => 'fax',
        ),
    );

    foreach ( $single_defs as $tag => $def ) {
        if ( schema_nerd_location_has_field( $first, $def['field'] ) ) {
            $shortcodes[] = array(
                'tag'         => $tag,
                'label'       => $def['label'],
                'description' => $def['description'],
                'group'       => $multi ? 'First location' : 'Location',
            );
        }
    }

    $multi_defs = array(
        'schema_nerd_locations_phone'   => array(
            'label'       => 'All location phones',
            'description' => 'Phone number for each location in your schema.',
            'field'       => 'phone',
        ),
        'schema_nerd_locations_address' => array(
            'label'       => 'All location addresses',
            'description' => 'Address for each location in your schema.',
            'field'       => 'address',
        ),
        'schema_nerd_locations_hours'   => array(
            'label'       => 'All location hours',
            'description' => 'Opening hours for each location in your schema.',
            'field'       => 'hours',
        ),
        'schema_nerd_locations_email'   => array(
            'label'       => 'All location emails',
            'description' => 'Email address for each location in your schema.',
            'field'       => 'email',
        ),
    );

    foreach ( $multi_defs as $tag => $def ) {
        foreach ( $locations as $location ) {
            if ( schema_nerd_location_has_field( $location, $def['field'] ) ) {
                $shortcodes[] = array(
                    'tag'         => $tag,
                    'label'       => $def['label'],
                    'description' => $def['description'],
                    'group'       => $multi ? 'All locations' : 'Location',
                );
                break;
            }
        }
    }

    return $shortcodes;
}

// Single-location shortcodes (first location — backward compatible)
function schema_nerd_phone_shortcode() {
    $business = schema_nerd_get_business_data();
    $phone    = schema_nerd_get_location_phone( $business );

    if ( $phone === '' ) {
        return '';
    }

    schema_nerd_maybe_enqueue_assets();
    return schema_nerd_render_location_field( $business, 'phone', 0 );
}
add_shortcode( 'schema_nerd_phone', 'schema_nerd_phone_shortcode' );

function schema_nerd_hours_shortcode() {
    $business = schema_nerd_get_business_data();

    if ( ! schema_nerd_location_has_field( $business, 'hours' ) ) {
        return '';
    }

    schema_nerd_maybe_enqueue_assets();
    return schema_nerd_render_location_field( $business, 'hours', 0 );
}
add_shortcode( 'schema_nerd_hours', 'schema_nerd_hours_shortcode' );

function schema_nerd_fax_shortcode() {
    $business = schema_nerd_get_business_data();

    if ( empty( $business['address']['faxNumber'] ) ) {
        return '';
    }

    schema_nerd_maybe_enqueue_assets();
    $index = 0;
    return '<div class="' . schema_nerd_get_location_classes( $business, $index, 'fax', array( 'business-section', 'fax-section' ) ) . '"><p class="' . schema_nerd_get_location_classes( $business, $index, 'fax-text' ) . '">' . esc_html( $business['address']['faxNumber'] ) . '</p></div>';
}
add_shortcode( 'schema_nerd_fax', 'schema_nerd_fax_shortcode' );

function schema_nerd_address_shortcode() {
    $business = schema_nerd_get_business_data();
    $address  = schema_nerd_render_location_field( $business, 'address', 0 );

    if ( $address === '' ) {
        return '';
    }

    schema_nerd_maybe_enqueue_assets();
    return $address;
}
add_shortcode( 'schema_nerd_address', 'schema_nerd_address_shortcode' );

// All locations shortcodes
function schema_nerd_locations_address_shortcode() {
    return schema_nerd_render_locations_list( 'address' );
}
add_shortcode( 'schema_nerd_locations_address', 'schema_nerd_locations_address_shortcode' );

function schema_nerd_locations_phone_shortcode() {
    return schema_nerd_render_locations_list( 'phone' );
}
add_shortcode( 'schema_nerd_locations_phone', 'schema_nerd_locations_phone_shortcode' );

function schema_nerd_locations_hours_shortcode() {
    return schema_nerd_render_locations_list( 'hours' );
}
add_shortcode( 'schema_nerd_locations_hours', 'schema_nerd_locations_hours_shortcode' );

function schema_nerd_locations_email_shortcode() {
    return schema_nerd_render_locations_list( 'email' );
}
add_shortcode( 'schema_nerd_locations_email', 'schema_nerd_locations_email_shortcode' );

function schema_nerd_location_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'field'      => '',
            'location'   => '0',
            'hide_title' => '',
        ),
        $atts,
        'schema_nerd_location'
    );

    $field      = sanitize_key( $atts['field'] );
    $hide_title = $atts['hide_title'] !== '' ? $atts['hide_title'] : null;
    $location   = schema_nerd_get_location_by_ref( $atts['location'] );

    if ( ! $location || ! schema_nerd_location_has_field( $location, $field ) ) {
        return '';
    }

    $content = schema_nerd_render_location_field( $location, $field );
    if ( $content === '' ) {
        return '';
    }

    schema_nerd_maybe_enqueue_assets();
    $index   = schema_nerd_get_location_index( $location );
    $name    = schema_nerd_get_location_name( $location );
    $id_attr = schema_nerd_get_location_id_attr( $location, $index );
    $output  = '<div id="' . esc_attr( $id_attr ) . '" class="' . schema_nerd_get_location_classes( $location, $index, 'single' ) . '">';

    if ( $name !== '' && ! schema_nerd_is_location_title_hidden( $hide_title ) ) {
        $output .= '<h3 class="' . schema_nerd_get_location_classes( $location, $index, 'name' ) . '">' . esc_html( $name ) . '</h3>';
    }

    $output .= $content . '</div>';

    return $output;
}
add_shortcode( 'schema_nerd_location', 'schema_nerd_location_shortcode' );

function schema_nerd_ajax_location_shortcode() {
    check_ajax_referer( 'schema_nerd_shortcodes', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
    }

    $location_ref = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '0';
    $field        = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';
    $hide_title   = isset( $_POST['hide_title'] ) ? sanitize_text_field( wp_unslash( $_POST['hide_title'] ) ) : null;
    $location     = schema_nerd_get_location_by_ref( $location_ref );

    if ( ! $location || ! schema_nerd_location_has_field( $location, $field ) ) {
        wp_send_json_error( array( 'message' => 'This field is not available for the selected location.' ) );
    }

    $shortcode = schema_nerd_format_location_shortcode( $field, $location_ref, $hide_title );
    $preview   = do_shortcode( $shortcode );

    wp_send_json_success(
        array(
            'shortcode' => $shortcode,
            'preview'   => $preview,
        )
    );
}
add_action( 'wp_ajax_schema_nerd_location_shortcode', 'schema_nerd_ajax_location_shortcode' );
