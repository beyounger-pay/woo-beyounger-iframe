<?php

namespace By;

use By\Gateways\By_Gateway;
use By\Gateways\By_Apg_Gateway;
use By\Gateways\By_Redirect_Gateway;
use By\Gateways\By_Glo_Gateway;
use ByPaymentRedirectController;
use ByPaymentController;
use ByPaymentGloController;


class Main
{
    const ROUTE_WEBHOOK = 'by_webhook';
    const ROUTE_CKO_WEBHOOK = 'by_cko_webhook';
    const ROUTE_GLO_WEBHOOK = 'by_glo_webhook';




    public static $instance;
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        $this->registerEvents();
    }

    public function registerEvents()
    {
        add_filter('woocommerce_payment_gateways', [$this, 'addPaymentGateways']);
        add_action('woocommerce_api_' . self::ROUTE_WEBHOOK, [new ByPaymentRedirectController, 'webhook']);
        add_action('woocommerce_api_' . self::ROUTE_CKO_WEBHOOK, [new ByPaymentController, 'webhook']);
        add_action('woocommerce_api_' . self::ROUTE_GLO_WEBHOOK, [new ByPaymentGloController, 'webhook']);

    }

    /**
     * woocommerce_payment_gateways, 将我们的PHP类注册为WooCommerce支付网关
     */
    public function addPaymentGateways($gateways)
    {
        $gateways[] = By_Gateway::class;
        $gateways[] = By_Apg_Gateway::class;
        $gateways[] = By_Redirect_Gateway::class;
        $gateways[] = By_Glo_Gateway::class;
        return $gateways;
    }
}
