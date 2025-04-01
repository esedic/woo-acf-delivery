<?php
/**
 * Plugin Name: ACF field value in WooCommerce Cart and Checkout
 * Plugin URI:  https://spletodrom.si
 * Description: WordPress plugin which adds text (if a ACF field value exist) to the WooCommerce cart and checkout pages using Cart and Checkout Filters.
 * Version:     1.0.0
 * Author:      Elvis Sedić
 * Author URI:  https://spletodrom.si
 * License:     GPL2
 * Text Domain: woo-acf-delivery
 */

if (!defined('ABSPATH')) {
    exit;
}

class Custom_Cart_Text_Plugin {
    
    public function __construct() {
        //add_action('wp_enqueue_scripts', [$this, 'enqueue_cart_filter_script']);
        add_filter('woocommerce_add_cart_item_data', [$this, 'store_delayed_delivery_field'], 10, 2);
        add_filter('woocommerce_get_item_data', [$this, 'display_delayed_delivery_field_in_cart'], 10, 2);
        add_action('woocommerce_cart_loaded_from_session', [$this, 'load_delayed_delivery_field'], 10, 1);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'restore_delayed_delivery_from_session'], 10, 2); // NEW!
    }

    // public function enqueue_cart_filter_script() {
    //     wp_register_script(
    //         'custom-cart-text',
    //         plugin_dir_url(__FILE__) . 'assets/js/custom-cart-text.js',
    //         ['wp-hooks', 'wp-element', 'wp-data', 'wp-components', 'wp-blocks', 'wc-blocks-registry'],
    //         filemtime(plugin_dir_path(__FILE__) . 'assets/js/custom-cart-text.js'),
    //         true
    //     );

    //     if (is_cart() || is_checkout()) {
    //         wp_enqueue_script('custom-cart-text');
    //         wp_localize_script('custom-cart-text', 'cartData', [
    //             'items' => WC()->cart->get_cart()
    //         ]);
    //     }
    // }

    public function store_delayed_delivery_field($cart_item_data, $product_id) {
        if (function_exists('get_field')) {
            $delayed_delivery = get_field('delayed_delivery', $product_id);
            if (is_array($delayed_delivery)) {
                $delayed_delivery = reset($delayed_delivery); // Get first value if it's an array
            }
            if ($delayed_delivery == "1") {
                $cart_item_data['delayed_delivery'] = 1; // Store the field in cart data
            }
        }
        return $cart_item_data;
    }

    public function display_delayed_delivery_field_in_cart($item_data, $cart_item) {
        if (!empty($cart_item['delayed_delivery'])) {
            $item_data[] = [
                'name'  => __('Delayed Delivery', 'yootheme-child'),
                'value' => __('This item has a delayed delivery!', 'yootheme-child')
            ];
        }
        //error_log(print_r(WC()->cart->get_cart(), true));

        return $item_data;
    }

    public function load_delayed_delivery_field($cart) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['delayed_delivery'])) {
                $cart->cart_contents[$cart_item_key]['delayed_delivery'] = 1; // Correct assignment
            }
        }
    }

    public function restore_delayed_delivery_from_session($cart_item, $values) {
        if (isset($values['delayed_delivery'])) {
            $cart_item['delayed_delivery'] = $values['delayed_delivery'];
        }
        return $cart_item;
    }
}

// Initialize the plugin.
new Custom_Cart_Text_Plugin();
