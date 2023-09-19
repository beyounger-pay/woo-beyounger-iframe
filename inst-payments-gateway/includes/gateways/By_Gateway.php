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
//            'api_webhook' => array (
//                'title'       => 'Your Domain',
//                'type'        => 'text',
//                'default'     => 'https://yourdomain.com',
//            ),
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
//        wp_enqueue_script('custom-gallery', plugins_url('/asset/gallery.js', __FILE__));
        ?>

        <div class="currencyContainer">
            <div style="background-color: #fff">
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
                <button  type="button" class="button alt wp-element-button" name="woocommerce_checkout_place_order" id="my_place_order"> Place order</button>
            </div>
            <script>

                const loadJS = (url, callback) => {
                    var script = document.createElement('script');
                    var fn = callback || function () {
                    };
                    script.type = 'text/javascript';

                    // IE
                    if (script.readyState) {
                        script.onreadystatechange = function () {
                            if (script.readyState === 'loaded' || script.readyState === 'complete') {
                                script.onreadystatechange = null;
                                fn();
                            }
                        };
                    } else {
                        //其他浏览器
                        script.onload = function () {
                            fn();
                        };
                    }
                    script.src = url;
                    document.getElementsByTagName('head')[0].appendChild(script);
                }
                let err = true;
                let loading = false;
                let publicKey = "";
                let apiKey = '<?= $this->api_key; ?>';
                console.log('===js===');
                const tokenUrl = "https://api.checkout.com/tokens";
                const baseUrl = "https://api.beyounger.com";
                const publicKeyUrl = `${baseUrl}/v1/saas/checkout?apiKey=`+apiKey;

                const getPublickKeyMethod = () => {
                    return new Promise(function (resolve, reject) {
                        fetch(publicKeyUrl, {
                            method: "GET", // *GET, POST, PUT, DELETE, etc.
                            headers: {
                                "Content-Type": "application/json",
                            },
                        })
                            .then((response) => response.json())
                            .then((result) => {
                                console.log("Success:", result);
                                resolve(result);
                            })
                            .catch((error) => {
                                console.error("Error:", error);
                                reject(error);
                            });
                    });
                };

                const addEventHandler = () => {
                    Frames.addEventHandler(
                        Frames.Events.FRAME_VALIDATION_CHANGED,
                        (event) => {
                            var e = event.element;
                            console.log("Frames.Events.FRAME_VALIDATION_CHANGED", e);
                            document.getElementById("api_err_msg").innerText = "";
                            if (event.isValid || event.isEmpty) {
                                if (e === "card-number" && !event.isEmpty) {
                                    document.getElementById("card_err_msg").innerText = "";
                                } else if (e === "expiry-date") {
                                    document.getElementById("date_err_msg").innerText = "";
                                } else if (e === "cvv") {
                                    document.getElementById("cvv_err_msg").innerText = "";
                                }
                            } else {
                                if (e === "card-number") {
                                    let msg = "Please enter a valid card number";
                                    document.getElementById("card_err_msg").innerText = msg;
                                    err = false;
                                } else if (e === "expiry-date") {
                                    let msg = "Please enter a valid expiry date";
                                    document.getElementById("date_err_msg").innerText = msg;
                                } else if (e === "cvv") {
                                    let msg = "Please enter a valid cvv code";
                                    document.getElementById("cvv_err_msg").innerText = msg;
                                    err = false;
                                }
                            }
                        }
                    );

                    function onCardTokenizationFailed(error) {
                        console.log("CARD_TOKENIZATION_FAILED: %o", error);
                        loading = false;
                        document.getElementById("pay-button").disabled = false;
                        document.getElementById("pay-button").innerText = "Pay";
                        Frames.enableSubmitForm();
                    }

                    Frames.addEventHandler(Frames.Events.CARD_VALIDATION_CHANGED, () => {
                        console.log("!Frames.isCardValid", !Frames.isCardValid());
                        document.getElementById("pay-button").disabled =
                            !Frames.isCardValid();
                        err = !Frames.isCardValid();
                    });

                    Frames.addEventHandler(
                        Frames.Events.CARD_TOKENIZATION_FAILED,
                        onCardTokenizationFailed
                    );
                    Frames.addEventHandler(Frames.Events.CARD_TOKENIZED, (event) => {
                        console.log("event.token", event.token);
                        submitResult(event.token);
                    });
                };

                getPublickKeyMethod().then((res) => {
                    console.log(res);
                    publicKey = res.result.api_key
                });

                const initCard = () => {
                    if (publicKey) {
                        Frames.init({
                            publicKey: publicKey,
                            localization: {
                                cardNumberPlaceholder: "Card number",
                                expiryMonthPlaceholder: "MM",
                                expiryYearPlaceholder: "YY",
                                cvvPlaceholder: "CVV",
                            },
                        });
                        addEventHandler();
                    } else {
                        getPublickKeyMethod().then((res) => {
                            console.log(res);
                            publicKey = res.result.api_key

                            Frames.init({
                                publicKey: publicKey,
                                localization: {
                                    cardNumberPlaceholder: "Card number",
                                    expiryMonthPlaceholder: "MM",
                                    expiryYearPlaceholder: "YY",
                                    cvvPlaceholder: "CVV",
                                },
                            });
                            addEventHandler();
                        });
                    }
                };

                const submitResult = (token) => {
                    document.getElementById("api_err_msg").innerText = "";
                    document.getElementById("js_var").value = token;
                    document.getElementById("place_order").click()
                };

                function submitCard(e) {
                    // e.preventDefault();
                    if (loading) {
                        return;
                    }

                    console.log("err", err);
                    if (err) {
                        document.getElementById("api_err_msg").innerText = "请填写信息";
                        console.log("err");
                        return;
                    }
                    document.getElementById("api_err_msg").innerText = "";
                    loading = true;
                    document.getElementById("pay-button").disabled = true;
                    document.getElementById("pay-button").innerText = "Loading";
                    Frames.submitCard();
                }

                // document.getElementById("place_order").onclick = function (e) {
                //     console.log('触发place_order')
                //     submitCard(e)
                // };
                document.getElementById("my_place_order").addEventListener('click',(e)=>{
                    console.log('触发place_order')
                    submitCard(e)
                })

            </script>
            <script>
                loadJS("https://cdn.checkout.com/js/framesv2.min.js", () => {
                    console.log("js load");
                    initCard();
                });
            </script>
        </div>

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
