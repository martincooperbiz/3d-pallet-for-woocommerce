<?php
/*
Plugin Name: 3D pallet for WooCommerce
Plugin URI: https://github.com/levcoder/3d-pallet-for-woocommerce
Description: 3D pallet for WooCommerce.
Version: 0.1.6
Author: Dima Levischenko
Author URI: https://github.com/levcoder
You can contact us at dimaleschenko@gmail.com

*/
if ( ! defined( 'ABSPATH' ) )
    die( "Can't load this file directly" );

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

if ( ! in_array( '3d-pallet-for-woocommerce/3d-pallet-for-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

define('WOO_3D_PALLET_VERSION', '0.1.6');
define('PATH_3D_PALLET_DIR_VIEW', plugin_dir_path(__FILE__).'view/');
define('URL_3D_PALLET_DIR_CSS', plugin_dir_url(__FILE__).'assets/css/');
define('URL_3D_PALLET_DIR_JS', plugin_dir_url(__FILE__).'assets/js/');

function op_register_menu_meta_box() {
    add_meta_box(
        '3d-pallet-for-woocommerce',
        esc_html__( '3D pallet', 'text-domain' ),
        'render_meta_box',
        'shop_order', // shop_order is the post type of the admin order page
        'normal', // change to 'side' to move box to side column
        'low' // priority (where on page to put the box)
    );
}
add_action( 'add_meta_boxes', 'op_register_menu_meta_box' );

/**
 * @param $wpPost WP_Post
 * @return void
 */
function render_meta_box($wpPost) {
    require_once PATH_3D_PALLET_DIR_VIEW.'content.php';
}
