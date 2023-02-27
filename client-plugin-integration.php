<?php
/*
Plugin Name: MultiLit Woocommerce Integrations
Plugin URI: 
Description: Woocommerce / Woo Discounts / Event Tickets / Memberium / Xeroom Integrations
Version: 1.4.1
Author:
Author URI:
License:
Text Domain: multilit-woo
*/

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

define('MULTILIT_WOO_VERSION', '1.4.1');
define('MULTILIT_WOO_PLUGIN', __FILE__);
define('MULTILIT_WOO_DIR', __DIR__ . '/');
define('MULTILIT_WOO_CLASS_DIR', MULTILIT_WOO_DIR . 'classes/');
$multilit_woo_url = plugins_url('', __FILE__);
define('MULTILIT_WOO_URL', $multilit_woo_url . '/');
define('MULTILIT_WOO_ASSESTS_URL', MULTILIT_WOO_URL . 'assets/');
define('MULTILIT_WOO_PARTIALS_DIR', MULTILIT_WOO_DIR .'partials/');
define('MULTILIT_XEROOM_LOG', 0);

// Autoloader
include_once MULTILIT_WOO_CLASS_DIR . 'autoloader.php';

// Init The Plugin
add_action('plugins_loaded',function(){
	multilit_woo()->add_hooks();
});

// Gets the instance of the `multilit_woo` class
function multilit_woo(){
    return multilit_woo::get_instance();
}
