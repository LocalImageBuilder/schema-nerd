( function ( wp ) {
    'use strict';

    if ( ! wp.blocks || ! wp.blockEditor || ! wp.components || ! wp.element || ! wp.serverSideRender ) {
        return;
    }

    var blocks = wp.blocks;
    var blockEditor = wp.blockEditor;
    var components = wp.components;
    var element = wp.element;
    var ServerSideRender = wp.serverSideRender;
    var i18n = wp.i18n;
    var el = element.createElement;
    var Fragment = element.Fragment;
    var useBlockProps = blockEditor.useBlockProps;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    var useEffect = element.useEffect;
    var blockData = window.schemaNerdBlock || { locations: [], fields: {} };

    function getLocationOptions() {
        return ( blockData.locations || [] ).map( function ( location ) {
            var label = location.name || ( 'Location ' + ( parseInt( location.index, 10 ) + 1 ) );
            return { label: label, value: String( location.index ) };
        } );
    }

    function getFieldOptions() {
        return Object.keys( blockData.fields || {} ).map( function ( key ) {
            return { label: blockData.fields[ key ], value: key };
        } );
    }

    function initBuilderPreview() {
        if ( window.schemaNerdBuilderInit ) {
            window.schemaNerdBuilderInit();
        }
    }

    function syncBuilderToAttributes( props ) {
        if ( ! props.attributes.showBuilder || ! window.jQuery ) {
            return;
        }

        var $ = window.jQuery;
        var $root = $( '.schema-nerd-block-editor-location-builder .schema-nerd-shortcode-builder' ).first();

        if ( ! $root.length ) {
            return;
        }

        $root.off( '.snBlockSync' );

        $root.on( 'change.snBlockSync', '.schema-nerd-location-select', function () {
            props.setAttributes( { location: String( $( this ).val() ) } );
        } );

        $root.on( 'click.snBlockSync', '.schema-nerd-field-button', function () {
            var field = $( this ).data( 'field' );

            if ( field ) {
                props.setAttributes( { field: field } );
            }
        } );

        $root.on( 'change.snBlockSync', '.schema-nerd-hide-location-title', function () {
            props.setAttributes( { hideLocationTitle: $( this ).is( ':checked' ) } );
        } );
    }

    blocks.registerBlockType( 'schema-nerd/location-builder', {
        edit: function ( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps( {
                className: 'schema-nerd-block-editor-location-builder',
            } );

            useEffect(
                function () {
                    var timer = window.setTimeout( function () {
                        initBuilderPreview();
                        syncBuilderToAttributes( props );
                    }, 100 );

                    return function () {
                        window.clearTimeout( timer );
                    };
                },
                [ attributes.title, attributes.showBuilder, attributes.showShortcode, attributes.hideLocationTitle, attributes.location, attributes.field ]
            );

            return el(
                Fragment,
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: i18n.__( 'Location Builder', 'sn' ), initialOpen: true },
                        el( TextControl, {
                            label: i18n.__( 'Title', 'sn' ),
                            value: attributes.title,
                            onChange: function ( value ) {
                                setAttributes( { title: value } );
                            },
                        } ),
                        el( ToggleControl, {
                            label: i18n.__( 'Use interactive picker in editor preview', 'sn' ),
                            help: i18n.__( 'The live site always shows the location and field selected in the sidebar.', 'sn' ),
                            checked: attributes.showBuilder,
                            onChange: function ( value ) {
                                setAttributes( { showBuilder: value } );
                            },
                        } ),
                        el( ToggleControl, {
                            label: i18n.__( 'Show shortcode copy box', 'sn' ),
                            checked: attributes.showShortcode,
                            onChange: function ( value ) {
                                setAttributes( { showShortcode: value } );
                            },
                        } ),
                        el( ToggleControl, {
                            label: i18n.__( 'Hide location name in output', 'sn' ),
                            checked: attributes.hideLocationTitle,
                            onChange: function ( value ) {
                                setAttributes( { hideLocationTitle: value } );
                            },
                        } ),
                        getLocationOptions().length
                            ? el( SelectControl, {
                                  label: i18n.__( 'Location', 'sn' ),
                                  value: String( attributes.location ),
                                  options: getLocationOptions(),
                                  onChange: function ( value ) {
                                      setAttributes( { location: value } );
                                  },
                              } )
                            : null,
                        el( SelectControl, {
                            label: i18n.__( 'Field', 'sn' ),
                            value: attributes.field,
                            options: getFieldOptions(),
                            onChange: function ( value ) {
                                setAttributes( { field: value } );
                            },
                        } )
                    )
                ),
                el(
                    'div',
                    blockProps,
                    blockData.locations.length
                        ? el( ServerSideRender, {
                              block: 'schema-nerd/location-builder',
                              attributes: Object.assign( {}, attributes, { editorPreview: true } ),
                              LoadingResponsePlaceholder: function () {
                                  return el( 'p', null, i18n.__( 'Loading preview…', 'sn' ) );
                              },
                              ErrorResponsePlaceholder: function () {
                                  return el( 'p', null, i18n.__( 'Could not load preview.', 'sn' ) );
                              },
                          } )
                        : el(
                              'p',
                              null,
                              i18n.__( 'Save an API key and select an organization to use this block.', 'sn' )
                          )
                )
            );
        },
        save: function () {
            return null;
        },
    } );
} )( window.wp );
