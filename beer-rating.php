<?php
/**
 * Plugin Name:       Beer Rating
 * Description:       Plugin do oceniania postów za pomocą "piwka".
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Gotcha190
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       beer-rating
 *
 */

define('BEER_RATING_PATH', plugin_dir_path(__FILE__));
define('BEER_RATING_URL', plugin_dir_url(__FILE__));

function beer_rating_activate()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_beers';

    // Utwórz tabelę w bazie danych, jeśli nie istnieje
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            like_date datetime NOT NULL,
            PRIMARY KEY (id)
        );";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'beer_rating_activate');

function beer_rating_deactivate()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_beers';

    // Usuń tabelę, jeśli istnieje, podczas deaktywacji
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $wpdb->query("DROP TABLE $table_name");
    }
}
register_deactivation_hook(__FILE__, 'beer_rating_deactivate');

function beer_rating_init()
{
    wp_enqueue_style('custom-style', plugins_url('style.scss', __FILE__));

    wp_enqueue_script('custom-script', BEER_RATING_URL . 'js/custom-script.js', array('jquery'), '1.0', true);

    wp_localize_script('custom-script', 'beer_rating_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('init', 'beer_rating_init');

function create_block_beer_rating_block_init()
{
    register_block_type(__DIR__ . '/build');
}
add_action('init', 'create_block_beer_rating_block_init');

function add_like_button_to_post($content)
{
    if (is_single()) {
        $post_id = get_the_ID();
        $liked = false; // Załóżmy, że na początku użytkownik nie polubił posta

        echo '<script>';
        echo 'var imagePath = "' . plugins_url('', __FILE__) . '/";'; // Dodaj ścieżkę do katalogu pluginu
        echo '</script>';

        $cookie_name = 'like_posts_' . $post_id;
        if (isset($_COOKIE[$cookie_name])) {
            $cookie_data = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
            if (isset($cookie_data['liked']) && $cookie_data['liked'] === true) {
                $liked = true;
            }
        }

        $like_button_text = $liked ? 'Polubione' : 'Daj piwko';
        $like_button_image = $liked ? 'images/beer-full.svg' : 'images/beer-empty.svg';
        $data_liked_value = $liked ? 'true' : 'false';

        // Dodaj przycisk "Like" i obrazek
        $like_button = '<div class="like-container">';
        $like_button .= '<img class="like-img" src="' . plugins_url($like_button_image, __FILE__) . '" alt="Obrazek" data-liked="' . $data_liked_value . '"/>';
        $like_button .= '<span class="like-count" id="like-count-' . esc_attr(get_the_ID()) . '">' . get_likes_count_for_post(get_the_ID()) . '</span>';
        $like_button .= '<button class="like-button" data-post-id="' . $post_id . '">' . $like_button_text . '</button>';
        $like_button .= '</div>';

        $content .= $like_button;
    }
    return $content;
}
add_filter('the_content', 'add_like_button_to_post');
function get_likes_count_for_post($post_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_beers';

    // Pobierz liczbę polubień dla danego posta
    $likes_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE post_id = $post_id");

    // Jeśli nie ma rekordu dla tego posta, zwróć 0
    return $likes_count ? $likes_count : 0;
}
function update_likes_count()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'post_beers';

    $post_id = intval($_POST['post_id']);
    $cookie_name = 'like_posts_' . $post_id;

    $cookie_value = isset($_COOKIE[$cookie_name]) ? json_decode(stripslashes($_COOKIE[$cookie_name]), true) : array('liked' => false, 'like_date' => '');

    // Aktualna liczba "like" przesłana z JavaScript
    $current_likes = intval($_POST['current_likes']);

    if (!$cookie_value['liked']) {
        // Jeśli użytkownik chce dać "like" i jeszcze nie oddał

        $like_date = current_time('mysql');
        $cookie_value['liked'] = true;
        $cookie_value['like_date'] = $like_date;

        $wpdb->insert(
            $table_name,
            array('post_id' => $post_id, 'like_date' => $like_date),
            array('%d', '%s')
        );

        setcookie($cookie_name, json_encode($cookie_value), time() + 3600 * 24 * 7, '/');

        $current_likes++; // Zwiększ liczbę "like"
    } elseif ($cookie_value['liked']) {
        // Jeśli użytkownik chce usunąć "like" i już oddał

        $like_date = $cookie_value['like_date'];

        $wpdb->delete($table_name, array('post_id' => $post_id, 'like_date' => $like_date));

        setcookie($cookie_name, '', time() - 3600, '/');
        $current_likes--; // Zmniejsz liczbę "like"
    }

    // Przygotuj odpowiedź JSON
    $response_data = array(
        // 'likes_count' => $likes_count
        'likes_count' => $current_likes
    );

    wp_send_json_success($response_data);

    wp_die();
}
add_action('wp_ajax_update_likes_count', 'update_likes_count');
add_action('wp_ajax_nopriv_update_likes_count', 'update_likes_count');

function gutenberg_examples_dynamic_render_callback($block_attributes, $content)
{
    $recent_posts = wp_get_recent_posts(
        array(
            'numberposts' => 1,
            'post_status' => 'publish',
        )
    );
    if (count($recent_posts) === 0) {
        return 'No posts';
    }
    $post = $recent_posts[0];
    $post_id = $post['ID'];
    return sprintf(
        '<a class="wp-block-my-plugin-latest-post" href="%1$s">%2$s</a>',
        esc_url(get_permalink($post_id)),
        esc_html(get_the_title($post_id))
    );
}