<?php

namespace By\Gateways;

use Exception;
use WC_Payment_Gateway;
use ByPaymentDirectController;

if (!defined('ABSPATH')) {
    exit;
}
define('AIRWALLEX_PLUGIN_URL5', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));

class By_Direct_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'beyounger-direct'; // 支付网关插件ID
        $this->icon = ''; // todo 将显示在结帐页面上您的网关名称附近的图标的URL
        $this->has_fields = true; // todo 如果需要自定义信用卡形式
        $this->method_title = 'Beyounger Direct Payments Gateway';
        $this->method_description = 'Take Credit/Debit Card payments on your store.'; // 将显示在选项页面上
        // 网关可以支持订阅，退款，保存付款方式，
        // 这里仅支持支付功能
        $this->supports = array(
            'products'
        );
        // 具有所有选项字段的方法
        $this->init_form_fields();

        // 加载设置。
        $this->init_settings();
        $this->enabled = $this->get_option( 'enabled' );
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        $this->domain = $this->get_option( 'domain' );
        $this->api_key = $this->get_option( 'api_key' );
        $this->api_secret = $this->get_option( 'api_secret' );
//        $this->app_id = $this->get_option( 'app_id' );
        $this->api_webhook = $this->get_option( 'api_webhook' );



        // 这个action hook保存设置
//        add_action( 'wp_enqueue_scripts'. $this->id, [$this, 'payment_scripts'] );//payment_scripts
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        // apply_filters( 'wp_doing_ajax', true );

        $this->controller = new ByPaymentDirectController;

    }

    /**
     * 插件设置选项
     */
    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Beyounger Payment Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Credit/Debit Card',
//                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with your credit card via our super-cool payment gateway.',
            ),
            'domain' => array (
                'title'       => 'Beyounger Domain',
                'type'        => 'text',
                'default'     => 'https://api.beyounger.com',
            ),
            'api_key' => array (
                'title'       => 'API Key',
                'type'        => 'text',
            ),
            'api_secret' => array (
                'title'       => 'API Secret',
                'type'        => 'text',
            ),
//            'app_id' => array (
//                'title'       => 'APP ID',
//                'type'        => 'text',
//            ),
//            'api_webhook' => array (
//                'title'       => 'Webhook',
//                'label'       => 'Enable Payment Webhook',
//                'type'        => 'checkbox',
//                'description' => 'url : http(s)://{host}?wc-api=by_webhook',
//                'default'     => 'no',
//            ),
            'api_webhook' => array (
                'title'       => 'Your Domain',
                'type'        => 'text',
                'default'     => 'https://yourdomain.com',
            ),
            'site_id' => array (
                'title'       => 'Site Id',
                'type'        => 'text',
                'default'     => 'cee541xxxx4a',
            ),
        );

    }

    /**
     * 字段验证
     */
    public function validate_fields() {

    }

    /**
     * 处理付款
     * @throws Exception
     */
    public function process_payment( $order_id ) {
        WC()->session->set('beyounger_order', $order_id);

        return $this->controller->payment($this, '');
    }


    public function receipt_page($order_id)
    {
        $by_url = get_post_meta($order_id, 'by_url', true);
        ?>
        <iframe
            src='<?= $by_url; ?>' height='795' width=100% frameBorder='0' id="new_iframe">
        </iframe>
        <?php
    }

    /**
     * 自定义信用卡表格
     */
    public function payment_fields() {


        wp_enqueue_script('custom-load-device-js', 'https://cdn.jsdelivr.net/npm/@beyounger/validator@0.0.3/dist/device.min.js', [], null, false);
        wp_enqueue_style( 'custom-css-direct' ,  plugins_url( '/asset/direct-payment/direct.css' , __FILE__ ));
        wp_enqueue_script('custom-jsencrypt', plugins_url('/asset/jsencrypt.min.js', __FILE__));
        // if ( !wp_script_is( 'custom-device-token', 'enqueued' ) ) {
        //     wp_enqueue_script('custom-forter', plugins_url('/asset/forter.js', __FILE__));
        // }
        wp_enqueue_script('custom-direct-forter', plugins_url('/asset/direct-payment/direct_forter.js', __FILE__));

        wp_enqueue_script('custom-direct', plugins_url('/asset/direct-payment/direct.js', __FILE__), [], null, false);
//        wp_localize_script( 'custom-direct', 'plugin_name_ajax_object',
//            array(
//                'var_app_id'=> $this->app_id,
//            )
//        );

        wp_localize_script( 'custom-direct-forter', 'plugin_name_ajax_object',
            array(
                'var_site_id'=> $this->site_id,
            )
        );
        ?>

    <form id="payment-form" method="POST" action="">
        <label class="cardno-label" for="cardno">Card Number</label>
        <div class="input-container cardno long">
          <input class="long" type="tel" id="cardno-field" maxlength="22">

          <div class="icon-container payment-method">
            <img id="logo-payment-method" />
          </div>
        </div>

        <div class="date-and-code">
          <div>
            <label class="date-label" for="expiry-date">MM / YY</label>
            <div class="input-container expiry-date ">
              <input type="tel" id="date-field" maxlength="5" placeholder="" type="text" value="">
            </div>
          </div>

          <div>
            <label class="cvv-label" for="cvv">CVV</label>
            <div class="input-container cvv">
              <input type="password" id="cvv-field" maxlength="3" onkeyup="value=value.replace(/[^\d]/g,'') "
                onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d]/g,''))">
            </div>
          </div>
        </div>
        <div style="height:40px"></div>
        <button  type="button" class="button alt wp-element-button" name="woocommerce_checkout_place_order" id="my_place_order"> Place order</button>
      </form>
        <input type="hidden" name="bin" id="bin" value="">
        <input type="hidden" name="last4" id="last4" value="">
        <input type="hidden" name="expiry_month" id="expiry_month" value="">
        <input type="hidden" name="expiry_year" id="expiry_year" value="">
        <input type="hidden" name="direct_device_token" id="direct_device_token" value="">
        <input type="hidden" name="direct_forter_token" id="direct_forter_token" value="">
        <input type="hidden" name="encrypt" id="encrypt" value="">
        
        <script>
            localStorage.setItem("direct_device_token", '');
            Device?Device.Report(window.location.href, false):''
            removeListener();
            addListener();
        </script>
        <?php

    }

    /*
     * 自定义CSS和JS，在大多数情况下，仅在使用自定义信用卡表格时才需要
     */
    public function payment_scripts() {


    }

}