jQuery(function ($) {
    'use strict';

    var config = window.schemaNerdBuilder || {};
    var ajaxUrl = config.ajaxUrl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

    window.schemaNerdBuilderInit = function ($scope) {
        var $targets = $scope ? $scope.find('.schema-nerd-shortcode-builder') : $('.schema-nerd-shortcode-builder');

        if ($scope && $scope.hasClass('schema-nerd-shortcode-builder')) {
            $targets = $targets.add($scope);
        }

        $targets.each(function () {
            var $root = $(this);

            if ($root.data('snBuilderInit')) {
                return;
            }

            $root.data('snBuilderInit', true);
            initBuilder($root);
        });
    };

    window.schemaNerdBuilderInit();

    $(document).on('click', '.schema-nerd-copy-shortcode', function () {
        var $button = $(this);
        var $builder = $button.closest('.schema-nerd-shortcode-builder');
        var shortcode = $button.data('shortcode');

        if (!shortcode && $builder.length) {
            shortcode = $builder.find('.schema-nerd-builder-shortcode').text();
        }

        if (!shortcode) {
            shortcode = $button.closest('.schema-nerd-shortcode-copy').find('.schema-nerd-shortcode-tag').text();
        }

        if (!shortcode) {
            return;
        }

        copyText(shortcode, $button, config);
    });

    function initBuilder($root) {
        var $select = $root.find('.schema-nerd-location-select');
        var $buttons = $root.find('.schema-nerd-field-button');
        var $shortcode = $root.find('.schema-nerd-builder-shortcode');
        var $preview = $root.find('.schema-nerd-builder-preview-content');
        var $copy = $root.find('.schema-nerd-copy-shortcode');
        var nonce = $root.data('nonce');
        var ajaxAction = $root.data('ajax-action') || 'schema_nerd_location_shortcode';
        var activeField = '';
        var i18n = config.i18n || {};

        function isHideLocationTitle() {
            return $root.find('.schema-nerd-hide-location-title').is(':checked') ? '1' : '0';
        }

        function getAvailableFields() {
            return $select.find(':selected').data('fields') || [];
        }

        function syncFieldButtons() {
            var fields = getAvailableFields();

            $buttons.each(function () {
                var $button = $(this);
                var field = $button.data('field');
                var enabled = fields.indexOf(field) !== -1;
                var isActive = enabled && field === activeField;

                $button
                    .prop('disabled', !enabled)
                    .toggleClass('is-active', isActive)
                    .attr('aria-pressed', isActive ? 'true' : 'false');
            });

            if (activeField && fields.indexOf(activeField) === -1) {
                activeField = '';
                $shortcode.text('');
                $preview.empty();
                $copy.prop('disabled', true).removeData('shortcode');
            }
        }

        function loadShortcode(field) {
            if (!field || !ajaxUrl) {
                return;
            }

            activeField = field;
            syncFieldButtons();
            $preview.html('<p class="schema-nerd-loading">' + (i18n.loading || 'Loading preview...') + '</p>');
            $copy.prop('disabled', true);

            $.post(ajaxUrl, {
                action: ajaxAction,
                nonce: nonce,
                location: $select.val(),
                field: field,
                hide_title: isHideLocationTitle()
            })
                .done(function (response) {
                    if (!response.success) {
                        var message = response.data && response.data.message ? response.data.message : (i18n.error || 'Could not load preview.');
                        $preview.html('<p class="schema-nerd-error">' + message + '</p>');
                        $shortcode.text('');
                        return;
                    }

                    $shortcode.text(response.data.shortcode || '');
                    $preview.html(response.data.preview || '<p class="schema-nerd-no-preview">No preview available.</p>');
                    $copy.prop('disabled', !response.data.shortcode).data('shortcode', response.data.shortcode || '');
                })
                .fail(function () {
                    $preview.html('<p class="schema-nerd-error">' + (i18n.error || 'Could not load preview.') + '</p>');
                    $shortcode.text('');
                });
        }

        $select.on('change', function () {
            syncFieldButtons();
            if (activeField) {
                loadShortcode(activeField);
            }
        });

        $buttons.on('click', function () {
            if ($(this).prop('disabled')) {
                return;
            }
            loadShortcode($(this).data('field'));
        });

        $root.on('change', '.schema-nerd-hide-location-title', function () {
            $root.data('hide-location-title', isHideLocationTitle());
            if (activeField) {
                loadShortcode(activeField);
            }
        });

        syncFieldButtons();

        var initialFields = getAvailableFields();
        if (initialFields.length) {
            loadShortcode(initialFields[0]);
        }
    }

    function copyText(shortcode, $button, config) {
        var i18n = (config && config.i18n) || {};

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(shortcode).then(function () {
                markCopied($button, i18n);
            });
            return;
        }

        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(shortcode).select();

        try {
            document.execCommand('copy');
            markCopied($button, i18n);
        } catch (error) {
            window.prompt('Copy this shortcode:', shortcode);
        }

        $temp.remove();
    }

    function markCopied($button, i18n) {
        var originalText = $button.text();
        $button.addClass('is-copied').text(i18n.copied || 'Copied');

        window.setTimeout(function () {
            $button.removeClass('is-copied').text(originalText);
        }, 1500);
    }
});
