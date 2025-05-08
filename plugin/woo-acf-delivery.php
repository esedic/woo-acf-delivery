<?php
/**
 * Plugin Name: ACF field value in WooCommerce Cart and Checkout
 * Plugin URI:  https://spletodrom.si
 * Description: WordPress plugin which adds text (if a ACF field value exist) to the WooCommerce cart and checkout pages using Cart and Checkout Filters.
 * Version:     1.2.0
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
        add_filter('woocommerce_add_cart_item_data', [$this, 'store_delayed_delivery_field'], 10, 2);
        add_filter('woocommerce_get_item_data', [$this, 'display_delayed_delivery_field_in_cart'], 10, 2);
        add_action('woocommerce_cart_loaded_from_session', [$this, 'load_delayed_delivery_field'], 10, 1);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'restore_delayed_delivery_from_session'], 10, 2);
    }

    public function store_delayed_delivery_field($cart_item_data, $product_id) {
        if (function_exists('get_field')) {
            $delayed_delivery = get_field('delayed_delivery', $product_id);
            $delayed_delivery_time = get_field('delayed_delivery_time', $product_id);

            if (is_array($delayed_delivery)) {
                $delayed_delivery = reset($delayed_delivery); // Get first value if it's an array
            }

            if ($delayed_delivery == "1") {
                $cart_item_data['delayed_delivery'] = 1;

                if (is_numeric($delayed_delivery_time)) {
                    $cart_item_data['delayed_delivery_time'] = intval($delayed_delivery_time);
                }
            }
        }

        return $cart_item_data;
    }

    public function display_delayed_delivery_field_in_cart($item_data, $cart_item) {
        if (!empty($cart_item['delayed_delivery'])) {
            $item_data[] = [
                'name'  => __('Delayed Delivery', 'woo-acf-delivery'),
                'value' => __('This product has a delivery delay!', 'woo-acf-delivery')
            ];

            if (!empty($cart_item['delayed_delivery_time'])) {
                $weeks = intval($cart_item['delayed_delivery_time']);
                $weeks_text = sprintf(
                    _n(
                        'Expected delivery time: in %d week.',
                        'Expected delivery time: in %d weeks.',
                        $weeks,
                        'woo-acf-delivery'
                    ),
                    $weeks
                );

                $item_data[] = [
                    'name'  => __('Delivery Time', 'woo-acf-delivery'),
                    'value' => esc_html($weeks_text)
                ];
            }
        }
        error_log(print_r(WC()->cart->get_cart(), true));

        return $item_data;
    }

    public function load_delayed_delivery_field($cart) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['delayed_delivery'])) {
                $cart->cart_contents[$cart_item_key]['delayed_delivery'] = 1;

                if (isset($cart_item['delayed_delivery_time'])) {
                    $cart->cart_contents[$cart_item_key]['delayed_delivery_time'] = intval($cart_item['delayed_delivery_time']);
                }
            }
        }
    }

    public function restore_delayed_delivery_from_session($cart_item, $values) {
        if (isset($values['delayed_delivery'])) {
            $cart_item['delayed_delivery'] = $values['delayed_delivery'];
        }

        if (isset($values['delayed_delivery_time'])) {
            $cart_item['delayed_delivery_time'] = intval($values['delayed_delivery_time']);
        }

        return $cart_item;
    }
}

// Initialize the plugin.
new Custom_Cart_Text_Plugin();