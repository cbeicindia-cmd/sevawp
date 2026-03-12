<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'primary' => 'Primary Menu',
    ]);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('seva-setu-theme', get_stylesheet_uri(), [], '1.0.0');
});
