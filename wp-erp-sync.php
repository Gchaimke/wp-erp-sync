<?php

/**
 * Plugin Name: Wordpress ERP Sync
 * Plugin URI: http://gchaim.com/wp-erp-sync
 * Description: Sync your ERP products with wordpress wocommerce. To connect to google drive
 * Version: 0.1.1
 * Author: Chaim Gorbov
 * Author URI: http://gchaim.com
 * License: GPL2
 */
if (!defined('WPINC')) {
	die;
}
$version = '0.1.1';
define('GDATA_FOLDER', plugin_dir_path(__FILE__) . 'inc/gdrive_data/');
define('ERP_DATA_FOLDER', plugin_dir_path(__FILE__) . 'erp-data/');
define('PLUGIN_NAME_VERSION', $version);
define('BASE_PATH', plugin_dir_path(__FILE__));
define('BASE_URL', plugin_dir_url(__FILE__));
// include the Composer autoload file
require BASE_PATH . 'vendor/autoload.php';
// use the classes namespaces
use WpErpSync\Shortcodes;
use WpErpSync\Plugin;
use WpErpSync\Product;
use WpErpSync\Order;
use WpErpSync\Cron;
use WpErpSync\Logger;

// instantiate classes
$displayDate = new Shortcodes\Today();
$plugin    = new Plugin();
$product = new Product();
$order = new Order();
$cron = new Cron();
//register all shortcodes
$plugin->addShortcode($displayDate);
// initialise the plugin
$plugin->init();


function wes_add_admin_pages()
{
	require_once plugin_dir_path(__FILE__) . 'inc/views/admin_pages_functions.php';
	add_menu_page('CRM Dashboard', 'CRM Dashboard', 'edit_pages', 'dashboard', 'wes_dashboard', 'dashicons-businessman', 3);
	add_submenu_page('dashboard', 'Clients', "CRM Clients", 'edit_pages', 'wesClients', 'wes_clients');
	add_submenu_page('dashboard', "Products", "CRM Products", 'edit_pages', 'wesProducts', 'wes_products');
	add_submenu_page('dashboard', "Settings", "Settings", 'edit_pages', 'wesSettings', 'wes_settings');
}

add_action('admin_menu', 'wes_add_admin_pages');

function wes_admin_scripts()
{
	global $version;
	wp_enqueue_style('wes', plugin_dir_url(__FILE__) . 'inc/css/wes-admin.css', [], $version);
	wp_register_script('wes', plugin_dir_url(__FILE__) . 'inc/js/wes-admin.js', ['jquery'], $version, true);
	wp_localize_script('wes', 'settings', [
		'nonce' => wp_create_nonce('wes') // Add a nonce for security
	]);
	wp_enqueue_script('wes');
}

add_action('admin_enqueue_scripts', 'wes_admin_scripts');

register_deactivation_hook( __FILE__, 'wes_deactivate' ); 
 
function wes_deactivate() {
	Cron::remove_cron('wes_crm_sync_data');
	Logger::log_message("Plugin CRM deactivated");
}
