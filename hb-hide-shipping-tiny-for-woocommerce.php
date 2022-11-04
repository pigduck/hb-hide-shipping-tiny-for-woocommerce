<?php
/*
 * Plugin Name: HB Hide Shipping Tiny For Woocommerce
 * Plugin URI: https://piglet.me/hb-hide-shipping-tiny-for-woocommerce
 * Description: A HB Hide Shipping Tiny For Woocommerce
 * Version: 0.1.0
 * Author: heiblack
 * Author URI: https://piglet.me
 * License:  GPL 3.0
 * Domain Path: /languages
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
*/


class hb_hide_shipping_tiny_admin
{
    public function __construct()
    {
        if (!defined('ABSPATH')) {
            http_response_code(404);
            die();
        }
        if (!function_exists('plugin_dir_url')) {
            return;
        }
        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            return;
        }
        $this->init();
    }

    public   function init()
    {
        //Add 'Setting' link  Plugin
       $this->HBAddpluginlink();
        //Add Volume in product_options_shipping
       $this->HB_product_options_variable();
       $this->HB_product_options_shipping();

       $this->HB_package_rates();
       //Add info message in review_order_after_shipping
       $this->HB_checkout_form_info();
       $this->HB_price_info();
       //Setting Page
       $this->HB_add_hide_shipping_setting();
    }
    private function HBAddpluginlink(){
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), function ( $links ) {
            $links[] = '<a href="' .
                admin_url( 'admin.php?page=wc-settings&tab=hb-hide-shipping' ) .
                '">' . esc_html(__('Settings')) . '</a>';
            return $links;
        });
    }
    private function  HB_product_options_variable(){
        add_action('woocommerce_product_after_variable_attributes', function ($loop, $variation_data, $variation) {
            echo '<div class="Woo_HideShipping_Volume" style="border-top: 1px solid #eee;border-bottom: 1px solid #eee;padding-bottom: 10px">';
            woocommerce_wp_text_input(
                array(
                    'id'                => "_hb_hide_shipping_tiny_Volume{$loop}",
                    'name'              => "_hb_hide_shipping_tiny_Volume[{$loop}]",
                    'value'             => get_post_meta( $variation->ID, '_hb_hide_shipping_tiny_Volume', true ),
                    'label'             => __( 'Volume', 'hb-hide-shipping-tiny-for-woocommerce' ),
                    'type'              => 'number',
                    'desc_tip'          => true,
                    'description'       => __( 'Product volume', 'hb-hide-shipping-tiny-for-woocommerce' ),
                    'custom_attributes' => array('min' => '0')
                )
            );
            echo '</div>';
        },10,3);

        add_action( 'woocommerce_save_product_variation', function ( $id, $loop ){
            $text_field = sanitize_text_field($_POST['_hb_hide_shipping_tiny_Volume'][ $loop ]);
            update_post_meta( $id, '_hb_hide_shipping_tiny_Volume', esc_attr( $text_field ));
        }, 10, 2 );
    }
    private function  HB_product_options_shipping(){
        add_action('woocommerce_product_options_shipping', function () {
            echo '<div class="Woo_HideShipping_Volume" style="border-top: 1px solid #eee;border-bottom: 1px solid #eee;padding-bottom: 10px">';
            woocommerce_wp_text_input(
                array(
                    'id'                => '_hb_hide_shipping_tiny_Volume_all',
                    'label'             => __( 'Volume', 'hb-hide-shipping-tiny-for-woocommerce' ),
                    'type'              => 'number',
                    'desc_tip'          => 'true',
                    'description'       => __( 'Product volume', 'hb-hide-shipping-tiny-for-woocommerce' ),
                    'custom_attributes' => array('min' => '0')
                )
            );
            echo '&nbsp;&nbsp;&nbsp;&nbsp;';
            echo esc_html_e( 'When the weight or volume of the product is updated (Revise), it is recommended to clear the cache or save again by this plugin setting page.', 'hb-hide-shipping-tiny-for-woocommerce' );
            echo '</div>';
        });
        add_action( 'woocommerce_process_product_meta', function ( $id){
            $woocommerce_checkbox = isset( $_POST['_hb_hide_shipping_tiny_Volume_all'] ) ? sanitize_text_field($_POST['_hb_hide_shipping_tiny_Volume_all']) : '';
            update_post_meta( $id, '_hb_hide_shipping_tiny_Volume_all', esc_attr($woocommerce_checkbox) );
        }, 10, 1 );
    }

    private function  HB_package_rates()
    {
        add_filter( 'woocommerce_package_rates', function ( $rates, $package ) {
            $all_free_rates     = array();
            $all_weight         = 0;
            $all_volume         = 0;
            $weight_limit       = get_option('_hb_hide_shipping_tiny_weightLimit');
            $volume_limit       = get_option('_hb_hide_shipping_tiny_volumeLimit');
            $subtotal_limit     = get_option('_hb_hide_shipping_tiny_subtotal');
            $cart_total_limit   = get_option('_hb_hide_shipping_tiny_cart_contents_total');
            $cart_total         = WC()->cart->cart_contents_total;
            $subtotal           = WC()->cart->subtotal;

            foreach ($package['contents'] as $value){
                $product_id     = $value['product_id'];
                $variation_id   = $value['variation_id'];
                if(empty($variation_id)){
                    $volume     = get_post_meta( $product_id, '_hb_hide_shipping_tiny_Volume_all');
                }else{
                    $volume     = get_post_meta( $variation_id, '_hb_hide_shipping_tiny_Volume');
                    if(!$volume[0]){
                        $volume     = get_post_meta( $product_id, '_hb_hide_shipping_tiny_Volume_all');
                    }
                }
                $quantity =$value['quantity'];
                $weight     = ((double)$value['data']->weight)*((int)$quantity);
                if(isset($volume[0])){
                    $all_volume += ((double)$volume[0]);
                }
                $all_weight += $weight;
            }
            $hasHide = 'false';
            if($all_weight >= $weight_limit || $all_volume >= $volume_limit || $cart_total < $subtotal_limit || $subtotal < $cart_total_limit){
                $shipping_hide = get_option('_hb_hide_shipping_array');
                unset($shipping_hide['_hb_hide_shipping_tiny_weightLimit'],$shipping_hide['_hb_hide_shipping_tiny_volumeLimit']);
                foreach ( $rates as $rate_id => $rate ) {
                    if (!array_key_exists('_hb_hide_shipping_tiny_'.$rate->method_id,$shipping_hide)) {
                        $all_free_rates[ $rate_id ] = $rate;
                        $hasHide = 'true';
                    }
                }
            }
            if ( $hasHide == 'false') {
                return $rates;
            } else {
                return $all_free_rates;
            }
        }, 10, 2 );
    }
    private function  HB_checkout_form_info(){
        add_action( 'woocommerce_review_order_after_shipping', function () {
            wp_enqueue_style( '_hb_hide_shipping_tiny_css', plugin_dir_url( __FILE__ ) . 'assets/style.css' );
            $heiblack_message = get_option('_hb_hide_shipping_tiny_message');
            if($heiblack_message){
                global $woocommerce;
                $items          = $woocommerce->cart->get_cart();
                $all_weight     = 0;
                $all_volume     = 0;
                $volume_limit   = get_option('_hb_hide_shipping_tiny_volumeLimit');
                $weight_limit   = get_option('_hb_hide_shipping_tiny_weightLimit');

                foreach ($items as $value){
                    $product_id = $value['product_id'];
                    $variation_id = $value['variation_id'];
                    if(empty($variation_id)){
                        $volume     = get_post_meta( $product_id, '_hb_hide_shipping_tiny_Volume_all');
                    }else{
                        $volume     = get_post_meta( $variation_id, '_hb_hide_shipping_tiny_Volume');
                        if(!$volume[0]){
                            $volume     = get_post_meta( $product_id, '_hb_hide_shipping_tiny_Volume_all');
                        }
                    }
                    $quantity   = $value['quantity'];

                    $weight     = ((double)$value['data']->weight)*((int)$quantity);


                    if(isset($volume[0])){
                        $all_volume += ((double)$volume[0]);
                    }
                    $all_weight += $weight;
                }
                $heiblack_message = sanitize_text_field($heiblack_message);
                $message = str_replace('[[weight]]', esc_textarea($all_weight), $heiblack_message);
                $message = str_replace('[[volume]]', esc_textarea($all_volume), $message);
                $message = str_replace('[[br]]', '<br>', $message);


                if( $all_weight >= $weight_limit || $all_volume >= $volume_limit){
                    $html  = '<tr class="hb-hide-shipping-content">';
                    $html .= '<td colspan="2">';
                    $html .= '<div class="hbalert hb-alert-info">';
                    $html .= wp_kses_post($message);
                    $html .= '</div></td></tr></td></tr>';
                    echo  wp_kses_post($html);

                }
            }
        },10);
    }
    private function HB_price_info(){
        add_action( 'woocommerce_review_order_before_payment', function () {
            wp_enqueue_style( '_hb_hide_shipping_tiny_css', plugin_dir_url( __FILE__ ) . 'assets/style.css' );
            $subtotal_limit      = get_option('_hb_hide_shipping_tiny_subtotal');
            $cart_total_limit    = get_option('_hb_hide_shipping_tiny_cart_contents_total');
            $cart_total = WC()->cart->cart_contents_total;
            $subtotal   = WC()->cart->subtotal;

            if( $cart_total < $subtotal_limit || $subtotal < $cart_total_limit){
                $price_message    = get_option('_hb_hide_shipping_tiny_price_message');
                $heiblack_message = sanitize_text_field($price_message);
                $message = str_replace('[[br]]', '<br>', $heiblack_message);
                $html = '<div class="hbalert hb-alert-info">';
                $html .= wp_kses_post($message);
                $html .= '</div>';
                echo  wp_kses_post($html);
            }
        });
    }
    private function HB_add_hide_shipping_setting(){
        add_filter( 'woocommerce_get_settings_pages', function ( $settings ) {
            $settings[] = require_once dirname(__FILE__) . '/page/hb-settings.php';
            return $settings;
        } );


    }
}




new hb_hide_shipping_tiny_admin();



