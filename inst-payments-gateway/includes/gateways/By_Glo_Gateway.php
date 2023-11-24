<?php

namespace By\Gateways;

use Exception;
use WC_Payment_Gateway;
use ByPaymentGloController;

if (!defined('ABSPATH')) {
    exit;
}
define('AIRWALLEX_PLUGIN_URL3', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));

class By_Glo_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'beyounger-glo'; // 支付网关插件ID
        $this->icon = ''; // todo 将显示在结帐页面上您的网关名称附近的图标的URL
        $this->has_fields = true; // todo 如果需要自定义信用卡形式
        $this->method_title = 'Beyounger GLO Payments Gateway';
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
        $this->app_id = $this->get_option( 'app_id' );
        $this->api_webhook = $this->get_option( 'api_webhook' );


        
        // 这个action hook保存设置
//        add_action( 'wp_enqueue_scripts'. $this->id, [$this, 'payment_scripts'] );//payment_scripts
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        // apply_filters( 'wp_doing_ajax', true );

        $this->controller = new ByPaymentGloController;

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
            'app_id' => array (
                'title'       => 'APP ID',
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
       
        // if ( !wp_script_is( 'custom-device-token', 'enqueued' ) ) {
        //     wp_enqueue_script('custom-forter', plugins_url('/asset/forter.js', __FILE__));
        // }
        wp_enqueue_script('custom-forter', plugins_url('/asset/forter.js', __FILE__));
        wp_enqueue_script('custom-jquery-js', 'https://pay.glocashpayment.com/public/comm/js/jquery112.min.js', [], null, false);
        wp_enqueue_script('custom-glocashpayment-js', 'https://pay.glocashpayment.com/public/gateway/js/iframe.v0.1.js', [], null, false);
        wp_enqueue_script('custom-glocash', plugins_url('/asset/glocash.js', __FILE__), [], null, false);
        wp_localize_script( 'custom-glocash', 'plugin_name_ajax_object',
            array(
                'var_app_id'=> $this->app_id,
            )
        );

        wp_localize_script( 'custom-forter', 'plugin_name_ajax_object',
        array(
            'var_site_id'=> $this->site_id,
        )
    );
        ?>
        <style>
            #place_order{
                display: none ;
            }
        </style>

        <form  method="post">
            <!--这些参数都是商户自己的提交参数-->
            <div>

            </div>

            <!-- 指定表单要插入的位置 -->
            <div style="max-width: 400px; margin: 0 auto" id="testFrom"></div>

            <div style="max-width: 400px; margin: 0 auto; text-align: center">
                <!--                <input id="sub_order" class="button alt wp-element-button"   type="button" value="Pay" />-->
                <button  type="button" class="button alt wp-element-button" name="woocommerce_checkout_place_order" id="sub_order"> Place order</button>

            </div>

        </form>
        <input type="hidden" name="js_var2" id="js_var2" value="">

        <input type="hidden" name="glo_device_token" id="glo_device_token" value="">
        <input type="hidden" name="glo_forter_token" id="glo_forter_token" value="">
        <script>
            glocashPay.init({
                appId, //商户ID 必填
                payElement: "testFrom", //需要放入的支付表单的位置
                isToken,
                config: {
                    card_iframe: {
                        style: "border: none; width: 100%;height:300px;display:none",
                    },
                }, // 设置iframe样式
            });
            initDeviceToken();
            // 付款
            $("#sub_order").click(function () {
                document.getElementById("js_var2").value = '';
                console.log('触发place_order');
                glocashPay.checkout(function ({ data }) {
                    console.log('glocashPay.result:',data)
                    if (data.error) {
                        console.error("创建卡信息失败:" + data.error);
                        return false;
                    }
                    // var postData = $("#place_order").serializeArray();
                    var postData = {};
                    if (data.token) {
                        //   postData.push({ name: "BIL_TEMP_TOKEN", value: data.token });
                        postData.token = data.token;
                    }
                    if (data.bilToken) {
                        //   postData.push({ name: "BIL_TOKEN", value: data.bilToken });
                        postData.bilToken = data.bilToken;
                    }
                    document.getElementById("js_var2").value = data.token;
                    submitData(postData);
                });
            });
        </script>
        <?php

    }

    /*
     * 自定义CSS和JS，在大多数情况下，仅在使用自定义信用卡表格时才需要
     */
    public function payment_scripts() {


    }


}
