<?php

use By\Gateways\By_Gateway;

class ByPaymentApgController {

    /**
     * @throws Exception
     */
    public function payment($gateway, $payType) {
        
        $orderId = (int)WC()->session->get('beyounger_order');

        $order = wc_get_order($orderId);
        if (empty($order)) {
            throw new Exception('Order not found: ' . $orderId);
        }
        $sdk = new HttpUtil();
        $url = $gateway->domain . '';
        $key = $gateway->api_key . '';
        $secret = $gateway->api_secret . '';
        $api_webhook = $gateway->api_webhook . '?wc-api=by_webhook'; //http://127.0.0.1/?wc-api=by_webhook

        $customer = array(
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'country' => $order->get_billing_country(),
            'state' => $order->get_billing_state(),
            'city' => $order->get_billing_city(),
            'address' => $order->get_billing_address_1(),
            'zipcode' => $order->get_billing_postcode(),
        );

//        // todo 哪里获取商品信息？ https://stackoverflow.com/questions/39401393/how-to-get-woocommerce-order-details
//        $product_info = array(
//            'name' => 'name'
//        );
//
//        $shipping_info = array(
//            'phone' => $order->get_shipping_phone(),
//            'first_name' => $order->get_shipping_first_name(),
//            'last_name' => $order->get_shipping_last_name(),
//            'country' => $order->get_shipping_country(),
//            'state' => $order->get_shipping_state(),
//            'city' => $order->get_shipping_city(),
//            'address' => $order->get_shipping_address_1() . $order->get_shipping_address_2(),
//            'zipcode' => $order->get_shipping_postcode(),
//            'company' => $order->get_shipping_company(),
//        );
        $cart_items = [];
        foreach ($order->get_items() as $item_key => $item ):
            $item_id = $item->get_id();
            //$product      = $item->get_product(); // Get the WC_Product object
            //$product_id   = $item->get_product_id(); // the Product id
            $item_name    = $item->get_name(); // Name of the product
            $quantity     = $item->get_quantity();
            $total        = $item->get_total(); // Line total (discounted)
            //echo $item . "=====\n";
            $myitem = array(
                'id' => $item_id,
                'name' => $item_name,
                'quantity' => $quantity,
                'unitPrice' => array(
                    'currency' => $order->get_currency(),
                    'value' => $total
                ),
            );
            array_unshift($cart_items, $myitem);
        endforeach;

        $home_url = home_url();
        $home_url = rtrim(str_replace('https://','',str_replace('http://','',$home_url)));
        preg_match('@^(?:https://)?([^/]+)@i',str_replace('www.','',$home_url), $matches);
        $memo = $order->get_id();
        $website = $matches[1];


        $userIP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        $userIP = ($userIP) ? $userIP : $_SERVER["REMOTE_ADDR"];

        // WordPress 使用 CDN 后获取访客真实 IP
        $_IP = '';
        if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
            $get_HTTP_X_FORWARDED_FOR = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $_IP= trim($get_HTTP_X_FORWARDED_FOR[0]);
        }

        $user_ip=$_SERVER["HTTP_X_FORWARDED_FOR"];
        if ($user_ip=="")
        {
            $user_ip=$_SERVER["REMOTE_ADDR"];
        }
        $customer_ip = $userIP.'|'.$_IP.'|'.$user_ip;
        echo '000000004:'.$customer_ip."\n";

        $post_data = array(
            'currency' => $order->get_currency(),
            'amount' => $order->get_total(),
            'cust_order_id' => 'woo' . substr($key, 0 ,5) . date("YmdHis",time()) . $orderId,
            'customer' => $customer,
            'payment_method' => 'creditcard',
            'notification_url' => $api_webhook,
//            'shipping_info' => $shipping_info,
            'cart_items' => $cart_items,
            'return_url' => $order->get_view_order_url(),
            'network' => $payType,
            'website'  => $website,
            'memo' => $memo,
            'ip' => ($_IP) ? $_IP : $userIP,
        );
        //$order->set_transaction_id( $memo );

        //$post_data = $sdk->formatArray($post_data);

        $requestPath = "/api/v1/payment";
        $timeStamp = round(microtime(true) * 1000);
        $signatureData = $key .
            "&" . $post_data['cust_order_id'] .
            "&" . $post_data['amount'] .
            "&" . $post_data['currency'] .
            "&" . $secret .
            "&" . $timeStamp;

        $result = $sdk->post($url, $requestPath, $post_data, $signatureData, $key, $timeStamp);
        //echo $post_data['cust_order_id'] . "\n";
//        echo json_encode($order). "=====\n";;
        $result = json_decode($result, true);
        if ( $result['code'] === 0 ) {

            update_post_meta($orderId, 'orderNo', $result['result']['order_id']);
            //update_post_meta($orderId, 'by_url', $result['result']['redirect_url']);
            $order->set_transaction_id($result['result']['order_id']);
            
//            // 给客户的一些备注（用false代替true使其变为私有）
//            $order->add_order_note( 'Payment is processing on ' . $result['result']['redirect_url'], true );
//
//            // 空购物车
//            //WC()->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true), //http://demo6.local/checkout/order-pay/33/?key=wc_order_no9c1aCayjdop
                'payment_url' => $result['result']['redirect_url'], //https://api-sandbox.beyounger.com/v1/checkoutFrom/?id=23092003532811514
            );
            

        } else if ($result['code'] === 117008) {
            wc_add_notice('Transaction already exist. Please check in order-view page.', 'error' );
            return array(
                'result' => 'error',
            );
        } else {
            wc_add_notice(  'Please try again.', 'error' );
            return array(
                'result' => 'error',
            );
        }
    }
    
/*
    public function simplePayment($gateway, $payType, $currency, $amount, $cartItems, $customer) {
        $sdk = new HttpUtil();
        $url = $gateway->domain . '';
        $key = $gateway->api_key . '';
        $secret = $gateway->api_secret . '';


        $home_url = home_url();
        $home_url = rtrim(str_replace('https://','',str_replace('http://','',$home_url)));
        preg_match('@^(?:https://)?([^/]+)@i',str_replace('www.','',$home_url), $matches);


        $userIP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        $userIP = ($userIP) ? $userIP : $_SERVER["REMOTE_ADDR"];
        // WordPress 使用 CDN 后获取访客真实 IP
        $_IP = '';
        if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
            $get_HTTP_X_FORWARDED_FOR = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $_IP= trim($get_HTTP_X_FORWARDED_FOR[0]);
        }
        $user_ip=$_SERVER["HTTP_X_FORWARDED_FOR"];
        if ($user_ip=="")
        {
            $user_ip=$_SERVER["REMOTE_ADDR"];
        }
        echo('|:' . $_IP . '|:' . $userIP . "\n");

        $home_url = home_url();
        $home_url = rtrim(str_replace('https://','',str_replace('http://','',$home_url)));
        preg_match('@^(?:https://)?([^/]+)@i',str_replace('www.','',$home_url), $matches);
        //$memo = $order->get_id();
        $website = $matches[1];
        
        
        $post_data = array(
            'currency' => $currency,
            'amount' => $amount,
            'cust_order_id' => 'woo' . substr($key, 0 ,5) . date("YmdHis",time()),
            'customer' => $customer,
            'payment_method' => 'creditcard',
            'cart_items' => $cartItems,
            //'return_url' => $order->get_view_order_url(),
            'network' => $payType,
            'website'  => $website,
            //'memo' => $memo,
            //'ip' => '223.166.122.36',
            'ip' => ($_IP) ? $_IP : $userIP,
        );

        $requestPath = "/api/v1/payment";
        $timeStamp = round(microtime(true) * 1000);
        $signatureData = $key .
            "&" . $post_data['cust_order_id'] .
            "&" . $post_data['amount'] .
            "&" . $post_data['currency'] .
            "&" . $secret .
            "&" . $timeStamp;

        $result = $sdk->post($url, $requestPath, $post_data, $signatureData, $key, $timeStamp);
        return $result;
    }
    
    */
    public function webhook() { // todo 起一个service去做

        http_response_code(200);
        header('Content-Type: application/json');

//        $gateway = new By_Gateway;
//        $enabled = $gateway->api_webhook;
//        if ($enabled === 'no') {
//            echo json_encode([
//                'code' => 1,
//                'msg'  => 'REFUSE',
//            ]);
//            die;
//        }

        $this->webhook_internal();
    }


    private function webhook_internal() {
        http_response_code(200);
        header('Content-Type: application/json');

        // todo 验签
        $result = true;

        if ($result) { //check succeed
            $tmpData = strval(file_get_contents("php://input"));
            $dataArray = json_decode($tmpData, true);
            if (strcmp($dataArray['action'], 'order_result') == 0) {

                $event = $dataArray['event'];
                $order_id = $event['cust_order_id'];
                $order = wc_get_order($order_id);
                if (empty($order)) {
                    return;
                }
                $status = $event['status'];
                if ($status == 1) { // 成功
                    $order->payment_complete();
                    $order->add_order_note( 'Payment is completed. (By Webhook)', true);
                    $order->update_status('completed', 'completed. (By Webhook)');
                } elseif ($status == 4) { // 失败
                    $order->update_status('failed', 'Failed. (By Webhook)');
                } elseif ($status == 5) { // 取消
                    $order->update_status('cancelled', 'Cancelled. (By Webhook)');
                } elseif ($status == 6) { // 过期
                    $order->update_status('failed', 'Expired. (By Webhook)');
                }

            } // todo 是否需要接收其他推送action？
            echo json_encode([
                'code' => 0,
                'msg'  => 'SUCCESS',
            ]);
            die;
        } else {
            echo json_encode([
                'code' => 3,
                'msg'  => 'VERIFY SIG FAIL',
            ]);
            die;
        }
    }


}
