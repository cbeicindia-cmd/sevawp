<?php
/**
 * Plugin Name: Seva Setu Platform
 * Description: Core platform features for SEVA SETU KENDRA.
 * Version: 1.0.0
 * Author: Seva Setu Kendra
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SEVA_SETU_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SEVA_SETU_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once SEVA_SETU_PLUGIN_PATH . 'includes/class-seva-setu-platform.php';

function seva_setu_platform_init() {
    $platform = new Seva_Setu_Platform();
    $platform->init();
}
add_action('plugins_loaded', 'seva_setu_platform_init');

register_activation_hook(__FILE__, ['Seva_Setu_Platform', 'activate']);
register_deactivation_hook(__FILE__, ['Seva_Setu_Platform', 'deactivate']);
