<?php
class hb_hide_shipping_tiny_admin_setting extends WC_Settings_Page {
    public function __construct() {
        $this->id    = 'hb-hide-shipping';
        $this->label = esc_html(__( 'HB Hide Shipping', 'hb-hide-shipping-tiny-for-woocommerce' ));
        parent::__construct();
    }
    public function get_sections() {
        $sections = array(
            ''              => esc_html(__( 'General', 'hb-hide-shipping-tiny-for-woocommerce' )),
        );
        return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
    }
    public function output() {
        global $current_section;

        esc_html_e( '**When the weight or volume of the product is updated(Revise), it is recommended to clear the cache or save again this page.', 'hb-hide-shipping-tiny-for-woocommerce' );
        echo  "<br>";
        echo  "<a target='_blank' href='https://piglet.me/hb-hide-shipping-tiny-for-woocommerce/'>".__('Have Bug or suggest','hb-hide-shipping-tiny-for-woocommerce')."</a>";
        $settings = $this->get_settings( $current_section );
        WC_Admin_Settings::output_fields( $settings );
    }
    public function save() {
        global $current_section,$wpdb;
        $settings = $this->get_settings( $current_section );
        WC_Admin_Settings::save_fields( $settings );

        $postdata   =  $_POST;

        //remove redundant content
        unset($postdata['_wpnonce'],$postdata['_wp_http_referer'],$postdata['save']);

        //safe input
        $Safetypostdata = [];
        foreach ($postdata as $key=>$value){
            $Safetypostdata[sanitize_key($key)]= sanitize_text_field($value);
        }
        update_option('_hb_hide_shipping_array',$Safetypostdata);

        if ( $current_section ) {
            do_action( 'woocommerce_update_options_' . $this->id . '_' . $current_section );
        }

        //clear cache
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM wp_options WHERE option_name LIKE %s;",
                '%\_transient\_%'
            )
        );
    }
    public function get_settings( $current_section = '' ) {
        //get all shipping_methods
        $shipping_methods = WC()->shipping->get_shipping_methods();

        $settings[] = [
            'name' => esc_html(__('Setting', 'hb-hide-shipping-tiny-for-woocommerce')),
            'type' => 'title',
            'desc' => '',
        ];
        $settings[] = [
            'title' => 'Over weight Limit',
            'id' => '_hb_hide_shipping_tiny_weightLimit',
            'type' => 'number',
            'default' => 'no',
            'custom_attributes' => array('min' => '0')
        ];
        $settings[] = [
            'title' => 'Over volume Limit',
            'id' => '_hb_hide_shipping_tiny_volumeLimit',
            'type' => 'number',
            'default' => 'no',
            'custom_attributes' => array('min' => '0')
        ];
        $settings[] = [
            'title' => 'Less than original price',
            'id' => '_hb_hide_shipping_tiny_subtotal',
            'type' => 'number',
            'default' => 'no',
            'custom_attributes' => array('min' => '0')
        ];
        $settings[] = [
            'title' => 'Less than discount Price',
            'id' => '_hb_hide_shipping_tiny_cart_contents_total',
            'type' => 'number',
            'default' => 'no',
            'custom_attributes' => array('min' => '0')
        ];
        $settings[] = [
            'type'  => 'sectionend',
            'id'    => '_hb_hide_shipping_tiny',
        ];
        $settings[] = [
            'name' => esc_html(__('hide shipping methods', 'hb-hide-shipping-tiny-for-woocommerce')),
            'type' => 'title',
            'desc' => '',
        ];
        foreach ($shipping_methods as $value){
            $settings[] = [
                'title' => '',
                'desc' => $value->id,
                'id' => '_hb_hide_shipping_tiny_'.$value->id,
                'type' => 'checkbox',
                'default' => 'no',
            ];
        }

        $settings[] = [
            'type'  => 'sectionend',
            'id'    => '_hb_hide_shipping_tiny',
        ];
        $settings[] = [
            'name' => esc_html(__('Show message When “shipping_method” has hidden', 'hb-hide-shipping-tiny-for-woocommerce')),
            'type' => 'title',
            'desc' => '',
        ];
        $settings[] = [
            'title'    => 'Weight / Volume',
            'desc'     => __( 'you can use [[br]]  [[volume]]  [[weight]]', 'hb-hide-shipping-tiny-for-woocommerce' ),
            'id'       => '_hb_hide_shipping_tiny_message',
            'type'     => 'textarea',
        ];
        $settings[] = [
            'title'    => 'Price',
            'desc'     => __( 'you can use [[br]]', 'hb-hide-shipping-tiny-for-woocommerce' ),
            'id'       => '_hb_hide_shipping_tiny_price_message',
            'type'     => 'textarea',
        ];
        $settings[] = [
            'type'  => 'sectionend',
            'id'    => '_hb_hide_shipping_tiny',
        ];
        return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
    }
}

new hb_hide_shipping_tiny_admin_setting();