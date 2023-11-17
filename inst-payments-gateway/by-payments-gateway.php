<?php
/**
 * Plugin Name: WooCommerce Beyounger Payment Gateway
 * Plugin URI: https://www.beyounger.com/
 * Description: Take Credit/Debit Card payments on your store.
 * Author: Beyounger payment
 * Author URI: https://www.beyounger.com/
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Required minimums and constants
 */
define('BY_PLUGIN_PATH', __DIR__ . '/');
define('BY_PLUGIN_NAME', 'by-payments-gateway');


add_action('plugins_loaded', 'by_init');
function by_init() {

    require_once BY_PLUGIN_PATH . 'includes/Main.php';
    // 在主文件中引入新文件
    require_once(BY_PLUGIN_PATH . 'includes/controllers/GetTrackingNumber.php');

    foreach (glob(BY_PLUGIN_PATH . 'includes/*/*.php') as $includeFile) {
        require_once $includeFile;
    }

    $inst = \By\Main::getInstance();
    $inst->init();
//    add_action('wp_enqueue_scripts', [$inst, 'addJs']);
}
