<?php

class Favorite_Posts {
    private $table_name; 

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'favorite_posts';
    }

    public function register() {
        // Ações para criação da tabela e registro de endpoints
        register_activation_hook(__FILE__, [$this, 'create_table']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_post (user_id, post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function register_rest_routes() {
        register_rest_route('favorite-posts/v1', '/favorite', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_favorite'],
            'permission_callback' => [$this, 'check_user_logged_in']
        ]);

        register_rest_route('favorite-posts/v1', '/favorites', [
            'methods' => 'GET',
            'callback' => [$this, 'get_favorites'],
            'permission_callback' => [$this, 'check_user_logged_in']
        ]);
    }

    public function check_user_logged_in() {
        return is_user_logged_in();
    }

    public function handle_favorite($request) {
        $post_id = $request->get_param('post_id');
        $user_id = get_current_user_id();

        if (!$post_id || !get_post($post_id)) {
            return new WP_Error('invalid_post', 'Post inválido.', ['status' => 400]);
        }

        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND post_id = %d",
            $user_id, $post_id
        ));

        if ($exists) {
            // Remover dos favoritos
            $wpdb->delete($this->table_name, ['user_id' => $user_id, 'post_id' => $post_id], ['%d', '%d']);
            return rest_ensure_response(['message' => 'Post desfavoritado com sucesso.']);
        } else {
            // Adicionar aos favoritos
            $wpdb->insert($this->table_name, ['user_id' => $user_id, 'post_id' => $post_id], ['%d', '%d']);
            return rest_ensure_response(['message' => 'Post favoritado com sucesso.']);
        }
    }

    public function get_favorites() {
        $user_id = get_current_user_id();

        global $wpdb;
        $post_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));

        if (!$post_ids) {
            return rest_ensure_response([]);
        }

        $posts = get_posts([
            'post__in' => $post_ids,
            'post_type' => 'post',
            'posts_per_page' => -1
        ]);

        return rest_ensure_response($posts);
    }
}
