<?php
use By\Gateways\By_Gateway;
use By\Gateways\By_Glo_Gateway;
use By\Gateways\By_Apg_Gateway;
use By\Gateways\By_Redirect_Gateway;

// 显示物流号输入框
add_action('woocommerce_admin_order_data_after_billing_address', 'add_custom_shipping_tracking_field');

// 保存物流号数据
add_action('woocommerce_process_shop_order_meta', 'save_custom_shipping_tracking_field', 20, 2);

function add_custom_shipping_tracking_field($order)
{
    error_log('saron---添加物流号函数已调用！');
    woocommerce_wp_text_input(array(
        'id' => '_logistics_company',
        'label' => __('物流公司', 'woocommerce'),
        'value' => get_post_meta($order->get_id(), '_logistics_company', true),
    ));
    woocommerce_wp_text_input(array(
    'id' => '_custom_shipping_tracking_number',
    'label' => __('物流号', 'woocommerce'),
    'value' => get_post_meta($order->get_id(), '_custom_shipping_tracking_number', true),
));
}

function save_custom_shipping_tracking_field($order_id)
{
    // 添加一些条件检查

    $payment_Apg_gateway = new By_Apg_Gateway();
    $payment_gateway = new By_Gateway();
    $payment_Glo_gateway = new By_Glo_Gateway();
    $payment_Redirect_gateway = new By_Redirect_Gateway();


    // 初始化变量，用于存储开启的网关和相应的值
    $active_gateway = null;
    $api_key = null;
    $api_secret = null;
    $domain = null;

// 判断每个支付网关是否开启，并获取第一个开启的网关的值
    if ($payment_Apg_gateway->is_available()) {
        $active_gateway = $payment_Apg_gateway;
    }

    elseif ($payment_gateway->is_available()) {
        $active_gateway = $payment_gateway;
    }

    elseif ($payment_Glo_gateway->is_available()) {
        $active_gateway = $payment_Glo_gateway;
    }

    elseif ($payment_Redirect_gateway->is_available()) {
        $active_gateway = $payment_Redirect_gateway;
    }

    // 如果有开启的网关，则获取其值
    if ($active_gateway !== null) {
        $api_key = $active_gateway->get_option( 'api_key' );
        $api_secret = $active_gateway->get_option( 'api_secret' );
        $domain = $active_gateway->get_option( 'domain' );
    }


    $tracking_number = isset($_POST['_custom_shipping_tracking_number']) ? sanitize_text_field($_POST['_custom_shipping_tracking_number']) : '';
    $logistics_company = isset($_POST['_logistics_company']) ? sanitize_text_field($_POST['_logistics_company']) : '';
    if (!empty($tracking_number)) {
        $old_tracking_number = get_post_meta($order_id, '_custom_shipping_tracking_number', true);
        $new_tracking_number = sanitize_text_field($_POST['_custom_shipping_tracking_number']);

        $new_logistics_company = sanitize_text_field($_POST['_logistics_company']);
        $old_logistics_company = get_post_meta($order_id, '_logistics_company', true);
        if(($new_tracking_number !== $old_tracking_number)||($new_logistics_company !== $old_logistics_company)){


            $tracking_number_result = update_post_meta($order_id, '_custom_shipping_tracking_number', $tracking_number);
            $logistics_company_result = update_post_meta($order_id, '_logistics_company', $logistics_company);
            error_log('saron---保存物流号 物流公司:' . $logistics_company_result);
            error_log('saron---保存物流号 物流号:' . $tracking_number_result);

            if ($tracking_number_result || $logistics_company_result) {
                // 更新成功
                error_log('saron---保存物流号结束！更新成功--物流号:' . $tracking_number);


                // 要发送的JSON数据
                $json_data = array(
                    "cust_order_id" => 'woo' . substr($api_key, 0 ,5) . date("YmdHis",time()) . $order_id,
                    "delivery_details" => array(
                        "logistics_company" => $logistics_company,
                        "tracking_number" => $tracking_number
                    )
                );


                $sdk = new HttpUtil();
                $key = $api_key;
                $secret = $api_secret;
                $requestPath = "/api/v1/payment/delivery";
                $timeStamp = round(microtime(true) * 1000);
                $signatureData = $key .
                    "&" . $secret .
                    "&" . $timeStamp;

                $result = $sdk->post($domain, $requestPath, $json_data, $signatureData, $key, $timeStamp);
                $result = json_decode($result, true);
                error_log("saron----data:".print_r($result, true));
                error_log("saron----url:".$domain);
                error_log("saron----json_data:".$json_data);
                error_log("saron----key:".$key);



            } else {
                // 更新失败
                error_log('saron---保存物流号失败！更新失败--订单ID: ' . $order_id);
            }
        }

    } else {
        // 输出物流号为空的信息
        error_log('saron---保存物流号失败！物流号为空--订单ID: ' . $order_id);
    }

}