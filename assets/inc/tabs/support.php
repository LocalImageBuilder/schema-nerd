<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function schema_nerd_get_support_articles() {
    $cached = get_transient( 'schema_nerd_support_articles' );

    if ( false !== $cached && is_array( $cached ) ) {
        return $cached;
    }

    $api_url = add_query_arg(
        array(
            'per_page' => 100,
            'orderby'  => 'title',
            'order'    => 'asc',
            '_fields'  => 'title,link,excerpt,date',
        ),
        schema_nerd_get_api_base_url() . '/wp-json/wp/v2/posts'
    );

    $response = wp_remote_get( $api_url, array( 'timeout' => 15 ) );

    if ( is_wp_error( $response ) ) {
        schema_nerd_debug_log( 'Schema Nerds API Error: ' . $response->get_error_message() );
        return array();
    }

    $posts = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $posts ) || ! is_array( $posts ) ) {
        return array();
    }

    $articles = array();

    foreach ( $posts as $post ) {
        if ( empty( $post['title']['rendered'] ) || empty( $post['link'] ) ) {
            continue;
        }

        $title   = wp_strip_all_tags( $post['title']['rendered'] );
        $excerpt = '';

        if ( ! empty( $post['excerpt']['rendered'] ) ) {
            $excerpt = wp_trim_words( wp_strip_all_tags( $post['excerpt']['rendered'] ), 28, '…' );
        }

        $articles[] = array(
            'title'   => $title,
            'link'    => $post['link'],
            'excerpt' => $excerpt,
            'date'    => ! empty( $post['date'] ) ? $post['date'] : '',
        );
    }

    usort(
        $articles,
        function ( $a, $b ) {
            return strnatcasecmp( $a['title'], $b['title'] );
        }
    );

    set_transient( 'schema_nerd_support_articles', $articles, 15 * MINUTE_IN_SECONDS );

    return $articles;
}

$schema_nerd_articles = schema_nerd_get_support_articles();
?>
<div class="schema-nerd-support-tab">
    <h2>Support</h2>
    <p class="schema-nerd-support-intro">Browse articles from schemanerd.app for setup help, shortcode tips, and schema guidance.</p>

    <?php if ( empty( $schema_nerd_articles ) ) : ?>
        <p class="schema-nerd-support-empty">No articles are available at this time. Please try again later.</p>
    <?php else : ?>
        <div class="schema-nerd-articles-toolbar">
            <label class="screen-reader-text" for="schema-nerd-articles-search">Search articles</label>
            <input
                type="search"
                id="schema-nerd-articles-search"
                class="schema-nerd-articles-search"
                placeholder="Search articles by title or excerpt&hellip;"
                autocomplete="off"
            >
            <p class="schema-nerd-articles-count" aria-live="polite">
                <?php
                $schema_nerd_article_count = count( $schema_nerd_articles );
                echo esc_html(
                    sprintf(
                        /* translators: 1: number of visible articles, 2: total number of articles */
                        _n(
                            'Showing %1$d of %2$d article',
                            'Showing %1$d of %2$d articles',
                            $schema_nerd_article_count,
                            'schema-nerd'
                        ),
                        $schema_nerd_article_count,
                        $schema_nerd_article_count
                    )
                );
                ?>
            </p>
        </div>

        <div class="schema-nerd-articles-grid">
            <?php foreach ( $schema_nerd_articles as $schema_nerd_article ) : ?>
                <?php
                $schema_nerd_search_text = strtolower( $schema_nerd_article['title'] . ' ' . wp_strip_all_tags( $schema_nerd_article['excerpt'] ) );
                $schema_nerd_date_label  = '';

                if ( $schema_nerd_article['date'] ) {
                    $schema_nerd_date_label = date_i18n( get_option( 'date_format' ), strtotime( $schema_nerd_article['date'] ) );
                }
                ?>
                <article
                    class="schema-nerd-article-card"
                    data-search="<?php echo esc_attr( $schema_nerd_search_text ); ?>"
                >
                    <div class="schema-nerd-article-card-body">
                        <?php if ( $schema_nerd_date_label ) : ?>
                            <time class="schema-nerd-article-date" datetime="<?php echo esc_attr( $schema_nerd_article['date'] ); ?>">
                                <?php echo esc_html( $schema_nerd_date_label ); ?>
                            </time>
                        <?php endif; ?>

                        <h3 class="schema-nerd-article-title">
                            <a href="<?php echo esc_url( $schema_nerd_article['link'] ); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html( $schema_nerd_article['title'] ); ?>
                            </a>
                        </h3>

                        <?php if ( $schema_nerd_article['excerpt'] ) : ?>
                            <p class="schema-nerd-article-excerpt"><?php echo esc_html( $schema_nerd_article['excerpt'] ); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="schema-nerd-article-card-footer">
                        <a
                            class="button button-secondary schema-nerd-article-read-more"
                            href="<?php echo esc_url( $schema_nerd_article['link'] ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Read article
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="schema-nerd-articles-no-results" hidden>No articles match your search.</p>
    <?php endif; ?>
</div>
