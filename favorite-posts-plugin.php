<?php
/*
Plugin Name: Favorite Posts Plugin
Description: Permite que usuários logados favoritem posts usando a WP REST API.
Version: 1.0
Author: Fábio Sousa
*/

if (!defined('ABSPATH')) { 
    exit;
}

// Carrega a classe principal do plugin
require_once plugin_dir_path(__FILE__) . 'class-favorite-posts.php';

// Inicializa o plugin
function initialize_favorite_posts_plugin() {
    $favoritePosts = new Favorite_Posts();
    $favoritePosts->register();
}

add_action('plugins_loaded', 'initialize_favorite_posts_plugin');

