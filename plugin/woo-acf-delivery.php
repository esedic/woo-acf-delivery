<?php
/**
 * Plugin Name: ACF delayed delivery
 * Plugin URI:  https://spletodrom.si
 * Description: Shows ACF fields "delayed_delivery" and "delayed_delivery_time" values in WooCommerce Cart and Checkout.
 * Version:     1.1.0
 * Author:      Elvis Sedić
 * Author URI:  https://spletodrom.si
 * License:     GPL2+
 * Text Domain: woo-acf-delivery
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Main plugin class for integrating ACF fields with WooCommerce cart and checkout
 * 
 * @since 1.2.0
 */
class Woo_Acf_Delivery {
    
    /**
     * Plugin instance
     *
     * @var Woo_Acf_Delivery
     */
    private static $instance = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Setup hooks
        $this->init_hooks();
        
        // Load textdomain
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Store ACF field data when adding to cart
        add_filter('woocommerce_add_cart_item_data', array($this, 'store_delayed_delivery_field'), 10, 2);
        
        // Display ACF field data in cart
        add_filter('woocommerce_get_item_data', array($this, 'display_delayed_delivery_field_in_cart'), 10, 2);
        
        // Load ACF field data from session
        add_action('woocommerce_cart_loaded_from_session', array($this, 'load_delayed_delivery_field'), 10, 1);
        
        // Restore ACF field data from session
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'restore_delayed_delivery_from_session'), 10, 2);
        
        // Add data to order item meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_delayed_delivery_to_order_item_meta'), 10, 4);
        
        // Display in order details
        add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'format_delayed_delivery_order_meta'), 10, 2);
    }
    
    /**
     * Get instance - Singleton pattern
     *
     * @return Woo_Acf_Delivery
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'woo-acf-delivery',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Store delayed delivery field in cart item data
     *
     * @param array $cart_item_data Cart item data
     * @param int $product_id Product ID
     * @return array Modified cart item data
     */
    public function store_delayed_delivery_field($cart_item_data, $product_id) {
        // Check if ACF is active
        if (!function_exists('get_field')) {
            return $cart_item_data;
        }
        
        // Get the actual product ID (handle variations)
        $actual_product_id = $product_id;
        if (isset($_POST['variation_id']) && !empty($_POST['variation_id'])) {
            $actual_product_id = absint($_POST['variation_id']);
        }
        
        // Get ACF fields
        $delayed_delivery = get_field('delayed_delivery', $actual_product_id);
        $delayed_delivery_time = get_field('delayed_delivery_time', $actual_product_id);
        
        // Handle array values
        if (is_array($delayed_delivery)) {
            $delayed_delivery = reset($delayed_delivery); // Get first value if it's an array
        }
        
        // If delayed delivery is enabled
        if ($delayed_delivery == "1" || $delayed_delivery === true) {
            $cart_item_data['delayed_delivery'] = 1;
            
            if (is_numeric($delayed_delivery_time)) {
                $cart_item_data['delayed_delivery_time'] = absint($delayed_delivery_time);
            }
        }
        
        return $cart_item_data;
    }
    
    /**
     * Display delayed delivery field in cart
     *
     * @param array $item_data Item data
     * @param array $cart_item Cart item
     * @return array Modified item data
     */
    public function display_delayed_delivery_field_in_cart($item_data, $cart_item) {
        if (!empty($cart_item['delayed_delivery'])) {
            $item_data[] = [
                'key'   => __('Delayed Delivery', 'woo-acf-delivery'),
                'value' => __('This product has a delivery delay!', 'woo-acf-delivery'),
                'display' => ''
            ];
            
            if (!empty($cart_item['delayed_delivery_time'])) {
                $weeks = absint($cart_item['delayed_delivery_time']);
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
                    'key'   => __('Delivery Time', 'woo-acf-delivery'),
                    'value' => esc_html($weeks_text),
                    'display' => ''
                ];
            }
        }
        
        return $item_data;
    }
    
    /**
     * Load delayed delivery field from session
     *
     * @param WC_Cart $cart Cart object
     */
    public function load_delayed_delivery_field($cart) {
        if (!empty($cart->get_cart())) {
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                if (isset($cart_item['delayed_delivery'])) {
                    $cart->cart_contents[$cart_item_key]['delayed_delivery'] = 1;
                    
                    if (isset($cart_item['delayed_delivery_time'])) {
                        $cart->cart_contents[$cart_item_key]['delayed_delivery_time'] = absint($cart_item['delayed_delivery_time']);
                    }
                }
            }
        }
    }
    
    /**
     * Restore delayed delivery from session
     *
     * @param array $cart_item Cart item
     * @param array $values Values from session
     * @return array Modified cart item
     */
    public function restore_delayed_delivery_from_session($cart_item, $values) {
        if (isset($values['delayed_delivery'])) {
            $cart_item['delayed_delivery'] = $values['delayed_delivery'];
        }
        
        if (isset($values['delayed_delivery_time'])) {
            $cart_item['delayed_delivery_time'] = absint($values['delayed_delivery_time']);
        }
        
        return $cart_item;
    }
    
    /**
     * Add delayed delivery info to order item meta
     *
     * @param WC_Order_Item_Product $item Order item
     * @param string $cart_item_key Cart item key
     * @param array $values Cart item values
     * @param WC_Order $order Order object
     */
    public function add_delayed_delivery_to_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['delayed_delivery'])) {
            $item->add_meta_data(__('Delayed Delivery', 'woo-acf-delivery'), __('This product has a delivery delay!', 'woo-acf-delivery'));
            
            if (isset($values['delayed_delivery_time'])) {
                $weeks = absint($values['delayed_delivery_time']);
                $weeks_text = sprintf(
                    _n(
                        'Expected delivery time: in %d week.',
                        'Expected delivery time: in %d weeks.',
                        $weeks,
                        'woo-acf-delivery'
                    ),
                    $weeks
                );
                
                $item->add_meta_data(__('Delivery Time', 'woo-acf-delivery'), esc_html($weeks_text));
            }
        }
    }
    
    /**
     * Format delayed delivery order meta
     *
     * @param array $formatted_meta Formatted meta data
     * @param WC_Order_Item $order_item Order item
     * @return array Modified formatted meta
     */
    public function format_delayed_delivery_order_meta($formatted_meta, $order_item) {
        foreach ($formatted_meta as $key => $meta) {
            if ($meta->key === __('Delayed Delivery', 'woo-acf-delivery') || 
                $meta->key === __('Delivery Time', 'woo-acf-delivery')) {
                
                // Add custom CSS class
                $formatted_meta[$key]->display_key = '<span class="delayed-delivery-meta">' . $meta->display_key . '</span>';
            }
        }
        
        return $formatted_meta;
    }
    
    /**
     * Check if ACF plugin is active
     *
     * @return boolean
     */
    public static function is_acf_active() {
        return function_exists('get_field');
    }
    
    /**
     * Check if WooCommerce is active
     *
     * @return boolean
     */
    public static function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
}

/**
 * Initialize plugin
 *
 * @return Woo_Acf_Delivery
 */
function woo_acf_delivery() {
    return Woo_Acf_Delivery::instance();
}

// Start the plugin when plugins are loaded
add_action('plugins_loaded', function() {
    // Check if dependencies are active
    if (!function_exists('get_field')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('ACF field value in WooCommerce Cart and Checkout requires Advanced Custom Fields plugin to be installed and activated.', 'woo-acf-delivery');
            echo '</p></div>';
        });
        return;
    }
    
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('ACF field value in WooCommerce Cart and Checkout requires WooCommerce plugin to be installed and activated.', 'woo-acf-delivery');
            echo '</p></div>';
        });
        return;
    }
    
    // Initialize plugin
    woo_acf_delivery();
});