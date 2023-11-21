<?php

namespace By\Gateways;

use Exception;
use WC_Payment_Gateway;
use ByPaymentController;

if (!defined('ABSPATH')) {
    exit;
}
define('AIRWALLEX_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));

class By_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'beyounger'; // 支付网关插件ID
        $this->icon = ''; // todo 将显示在结帐页面上您的网关名称附近的图标的URL
        $this->has_fields = true; // todo 如果需要自定义信用卡形式
        $this->method_title = 'Beyounger Payments Gateway';
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
        $this->api_webhook = $this->get_option( 'api_webhook' );
        $this->site_id = $this->get_option( 'site_id' );
        $this->iframe = $this->get_option( 'iframe' );


        // 这个action hook保存设置
//        add_action( 'wp_enqueue_scripts'. $this->id, [$this, 'payment_scripts'] );//payment_scripts
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        // apply_filters( 'wp_doing_ajax', true );

        $this->controller = new ByPaymentController;

    }

//    function my_plugin_assets() {
//        wp_register_style( 'custom-gallery', plugins_url( '/asset/style.css' , __FILE__ ) );
//        wp_register_script( 'custom-gallery', plugins_url( '/asset/gallery.js' , __FILE__ ) );
//        wp_register_script( 'custom-gallery2', plugins_url( '/asset/mini.js' , __FILE__ ) );
//
//        wp_enqueue_style( 'custom-gallery' );
//        wp_enqueue_script( 'custom-gallery' );
//        wp_enqueue_script( 'custom-gallery2' );
//    }

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
//            'iframe' => array (
//                'title'       => 'Iframe',
//                'label'       => 'Enable Beyounger Payment Iframe',
//                'type'        => 'checkbox',
//                'description' => 'If use iframe, page will be displayed as an iframe on the receipt_page of woo.',
//                'default'     => 'no',
//            ),
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

    //demo5.local-110
    //http://demo5.local/checkout/

    /**
     * 自定义信用卡表格
     */
    public function payment_fields() {
        wp_enqueue_style( 'custom-css' ,  plugins_url( '/asset/style.css' , __FILE__ ));
//        wp_enqueue_script('custom-gallery2', 'https://cdn.checkout.com/js/framesv2.min.js');
        wp_enqueue_script('custom-forter', plugins_url('/asset/forter.js', __FILE__));
        wp_enqueue_script('custom-load', plugins_url('/asset/load.js', __FILE__), [], null, true);
        wp_enqueue_script('custom-device-token', 'https://cdn.jsdelivr.net/npm/@beyounger/validator@0.0.3/dist/device.min.js', [], null, false);

        wp_localize_script( 'custom-forter', 'plugin_name_ajax_object',
            array(
                'var_site_id'=> $this->site_id,
            )
        );
        wp_localize_script( 'custom-load', 'plugin_name_ajax_object',
            array(
                'var_api_key'=> $this->api_key,
            )
        );

        ?>

        <div class="currencyContainer">
            <div style="background-color: #fff">

                <div class="wrap">
                    <svg class="visa" xmlns="http://www.w3.org/2000/svg" width="60" height="83" viewBox="0 0 256 83"><defs><linearGradient id="logosVisa0" x1="45.974%" x2="54.877%" y1="-2.006%" y2="100%"><stop offset="0%" stop-color="#222357"/><stop offset="100%" stop-color="#254AA5"/></linearGradient></defs><path fill="url(#logosVisa0)" d="M132.397 56.24c-.146-11.516 10.263-17.942 18.104-21.763c8.056-3.92 10.762-6.434 10.73-9.94c-.06-5.365-6.426-7.733-12.383-7.825c-10.393-.161-16.436 2.806-21.24 5.05l-3.744-17.519c4.82-2.221 13.745-4.158 23-4.243c21.725 0 35.938 10.724 36.015 27.351c.085 21.102-29.188 22.27-28.988 31.702c.069 2.86 2.798 5.912 8.778 6.688c2.96.392 11.131.692 20.395-3.574l3.636 16.95c-4.982 1.814-11.385 3.551-19.357 3.551c-20.448 0-34.83-10.87-34.946-26.428m89.241 24.968c-3.967 0-7.31-2.314-8.802-5.865L181.803 1.245h21.709l4.32 11.939h26.528l2.506-11.939H256l-16.697 79.963h-17.665m3.037-21.601l6.265-30.027h-17.158l10.893 30.027m-118.599 21.6L88.964 1.246h20.687l17.104 79.963h-20.679m-30.603 0L53.941 26.782l-8.71 46.277c-1.022 5.166-5.058 8.149-9.54 8.149H.493L0 78.886c7.226-1.568 15.436-4.097 20.41-6.803c3.044-1.653 3.912-3.098 4.912-7.026L41.819 1.245H63.68l33.516 79.963H75.473" transform="matrix(1 0 0 -1 0 82.668)"/></svg>
                    <svg  class="master" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="52px" height="32px" viewBox="0 0 52 32" version="1.1">
                        <!-- Generator: Sketch 55.2 (78181) - https://sketchapp.com -->
                        <title>MC-logo-52</title>
                        <desc>Created with Sketch.</desc>
                        <g id="Components---Sprint-3" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g id="assets-/-logo-/-mastercard-/-symbol">
                                <polygon id="Fill-1" fill="#FF5F00" points="18.7752605 28.537934 32.6926792 28.537934 32.6926792 3.41596003 18.7752605 3.41596003"/>
                                <path d="M19.6590387,15.976947 C19.6590387,10.8803009 22.03472,6.34107274 25.7341024,3.41596003 C23.0283795,1.27638054 19.6148564,0 15.9044284,0 C7.12054904,0 0.000132546844,7.15323422 0.000132546844,15.976947 C0.000132546844,24.8006598 7.12054904,31.953894 15.9044284,31.953894 C19.6148564,31.953894 23.0283795,30.6775135 25.7341024,28.537934 C22.03472,25.6123775 19.6590387,21.0735931 19.6590387,15.976947" id="Fill-2" fill="#EB001B"/>
                                <path d="M50.9714634,25.8771954 L50.9714634,25.257201 L50.8101981,25.257201 L50.6250744,25.6836968 L50.4395088,25.257201 L50.2782434,25.257201 L50.2782434,25.8771954 L50.3917919,25.8771954 L50.3917919,25.4094258 L50.5658701,25.8128438 L50.6838368,25.8128438 L50.857915,25.4085382 L50.857915,25.8771954 L50.9714634,25.8771954 Z M49.9504109,25.8771954 L49.9504109,25.3628264 L50.157184,25.3628264 L50.157184,25.2580887 L49.6314148,25.2580887 L49.6314148,25.3628264 L49.8377461,25.3628264 L49.8377461,25.8771954 L49.9504109,25.8771954 Z M51.4680723,15.9768139 C51.4680723,24.8005266 44.347214,31.9537609 35.5637764,31.9537609 C31.8533484,31.9537609 28.4393835,30.6773803 25.7341024,28.5378008 C29.4334848,25.6126881 31.8091661,21.07346 31.8091661,15.9768139 C31.8091661,10.8806116 29.4334848,6.34138341 25.7341024,3.41582689 C28.4393835,1.2762474 31.8533484,-0.000133141225 35.5637764,-0.000133141225 C44.347214,-0.000133141225 51.4680723,7.15310107 51.4680723,15.9768139 L51.4680723,15.9768139 Z" id="Fill-4" fill="#F79E1B"/>
                            </g>
                        </g>
                    </svg>
                </div>


                <form id="payment-form" method="POST" action="">
                    <label class="card-number-label" for="card-number">Card number</label>
                    <div class="input-container card-number">
                        <div class="card-number-frame"></div>

                        <div class="icon-container payment-method">
                            <img id="logo-payment-method" />
                        </div>
                    </div>

                    <div class="date-and-code">
                        <div>
                            <label for="expiry-date">Expiry date</label>
                            <div class="input-container expiry-date">
                                <div class="expiry-date-frame"></div>
                            </div>
                        </div>

                        <div>
                            <label for="cvv">Cvv</label>
                            <div class="input-container cvv">
                                <div class="cvv-frame"></div>
                            </div>
                        </div>
                    </div>

                    <label class="cko-name-label" for="cko-name"
                    >Name on card</label
                    >
                    <div class="input-container cko-name">
                        <div class="cko-name-wrap frame--activated">
                            <input  id="cko_cardholder_name" type="text" placeholder="Name on card" name="cko-name" class="cko-name-input">
                        </div>

                    </div>

                    <div id="card_err_msg"></div>
                    <div id="date_err_msg"></div>
                    <div id="cvv_err_msg"></div>
                    <div id="api_err_msg"></div>
                    <div style="height: 20px"></div>
                    <button
                            class="pay pay-button"
                            hidden="hidden"
                            id="pay-button"
                            type="submit"
                            onclick="submitCard(event)">
                        Submit
                    </button>
                </form>
                <input type="hidden" name="js_var" id="js_var" value="">
                <input type="hidden" name="bin" id="bin" value="">
                <input type="hidden" name="last4" id="last4" value="">
                <input type="hidden" name="expiry_month" id="expiry_month" value="">
                <input type="hidden" name="expiry_year" id="expiry_year" value="">
                <input type="hidden" name="device_token" id="device_token" value="">
                <input type="hidden" name="forter_token" id="forter_token" value="">

                <button  type="button" class="button alt wp-element-button" name="woocommerce_checkout_place_order" id="my_place_order"> Place order</button>
            </div>
        </div>

        <script>
            document.getElementById("my_place_order").addEventListener('click',(e)=>{
                document.getElementById("js_var").value = '';
                console.log('触发place_order')
                submitCard(e)
            })
        </script>

        <?php

    }
//
//    /*
//     * 自定义CSS和JS，在大多数情况下，仅在使用自定义信用卡表格时才需要
//     */
    public function payment_scripts() {

//        wp_register_style( 'custom-gallery', AIRWALLEX_PLUGIN_URL . '/asset/style.css'  );
//        wp_register_script( 'custom-gallery', plugins_url( '/asset/gallery.js' , __FILE__ ) );
//        wp_register_script( 'custom-gallery2', plugins_url( '/asset/mini.js' , __FILE__ ) );

//        wp_enqueue_style( 'custom-gallery' , AIRWALLEX_PLUGIN_URL . '/asset/style.css');
//        wp_enqueue_script( 'custom-gallery' );
//        wp_enqueue_script( 'custom-gallery2' );



    }
//
//
//
}
