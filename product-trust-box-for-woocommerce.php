<?php
/**
 * Plugin Name: Product Trust Box for WooCommerce
 * Plugin URI:  https://sirpisoftwares.com
 * Description: Adds a configurable trust/USP box on WooCommerce single product pages (delivery date range, free delivery, discounts, secure payment icons).
 * Version:     1.0.2
 * Author:      Abdul Majeed
 * License:     GPLv2 or later
 * Text Domain: product-trust-box-for-woocommerce
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * WC requires at least: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WCPTB_VERSION', '1.0.0' );
define( 'WCPTB_PLUGIN_FILE', __FILE__ );
define( 'WCPTB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCPTB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wcptb_add_settings_link');
function wcptb_add_settings_link($links) {
    $settings_url = admin_url('admin.php?page=wcptb');
    $settings_link = '<a href="' . esc_url($settings_url) . '">' . __('Settings', 'wcptb') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

require_once WCPTB_PLUGIN_DIR . 'inc/class-wcptb-plugin.php';

add_action( 'plugins_loaded', function() {
    \WCPTB\Plugin::instance();
});
