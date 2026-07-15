jQuery(function ($) {
    'use strict';

    if (new URLSearchParams(window.location.search).get('settings-updated') === 'true') {
        $('.wrap > .notice, .wrap > .updated, .wrap > #setting-error-settings_updated').hide();

        var $toast = $('<div class="schema-nerd-toast">Settings saved.</div>').appendTo('body');

        setTimeout(function () {
            $toast.addClass('is-fading');
        }, 2000);

        setTimeout(function () {
            $toast.remove();
        }, 2500);
    }

    var $search = $('#schema-nerd-articles-search');

    if (!$search.length) {
        return;
    }

    var $grid = $('.schema-nerd-articles-grid');
    var $cards = $grid.find('.schema-nerd-article-card');
    var $count = $('.schema-nerd-articles-count');
    var $noResults = $('.schema-nerd-articles-no-results');
    var total = $cards.length;

    function filterArticles() {
        var query = $.trim($search.val()).toLowerCase();
        var visible = 0;

        $cards.each(function () {
            var $card = $(this);
            var haystack = ($card.data('search') || '').toString();
            var matches = !query || haystack.indexOf(query) !== -1;

            $card.toggleClass('is-hidden', !matches);

            if (matches) {
                visible += 1;
            }
        });

        if ($count.length) {
            $count.text('Showing ' + visible + ' of ' + total + (total === 1 ? ' article' : ' articles'));
        }

        if ($noResults.length) {
            $noResults.prop('hidden', visible > 0 || !query);
        }
    }

    $search.on('input', filterArticles);
});
