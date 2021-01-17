<?php

/**
 * Plugin Name: Wordpress ERP Sync
 * Plugin URI: http://gchaim.com/wp-erp-sync
 * Description: Sync your ERP products with wordpress wocommerce. To connect to google drive
 * Version: 0.1.3
 * Author: Chaim Gorbov
 * Author URI: http://gchaim.com
 * License: GPL2
 */
if (!defined('WPINC')) {
	die;
}
$version = '0.1.3';
//sync timezone with wordpress
date_default_timezone_set(get_option('timezone_string'));
define('GDATA_FOLDER', plugin_dir_path(__FILE__) . 'inc/gdrive_data/');

define('PLUGIN_NAME_VERSION', $version);
define('BASE_PATH', plugin_dir_path(__FILE__));
define('BASE_URL', plugin_dir_url(__FILE__));
//define erp-data folder
$upload_dir = wp_upload_dir();
define('ERP_DATA_FOLDER', $upload_dir['basedir'] . '/erp-data/');
// Set this variable to specify a minimum order value
define('MIN_ORDER', 100);
//cerate data folders
$data_folders = array(ERP_DATA_FOLDER,ERP_DATA_FOLDER.'sync/', ERP_DATA_FOLDER.'orders/');
foreach ($data_folders as $folder) {
	if (!file_exists($folder)) {
		mkdir($folder, 0700);
		file_put_contents($folder . 'index.php', "<?php // Silence is golden.");
	}
}

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
try {
	$product = new Product();
} catch (\Throwable $th) {
	echo "XML not found";
}

$order = new Order();
$cron = new Cron();
//register all shortcodes
$plugin->addShortcode($displayDate);
// initialise the plugin
$plugin->init();


function wes_add_admin_pages()
{
	require_once plugin_dir_path(__FILE__) . 'inc/views/admin_pages_functions.php';
	add_menu_page('ERP Dashboard', 'ERP Dashboard', 'edit_pages', 'dashboard', 'wes_dashboard', 'dashicons-businessman', 3);
	add_submenu_page('dashboard', 'Clients', "Clients", 'edit_pages', 'wesClients', 'wes_clients');
	add_submenu_page('dashboard', "Products", "Products", 'edit_pages', 'wesProducts', 'wes_products');
	add_submenu_page('dashboard', "Settings", "Settings", 'edit_pages', 'wesSettings', 'wes_settings');
	add_submenu_page('dashboard', "Logs", "Logs", 'edit_pages', 'wesLogs', 'wes_logs');
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

register_deactivation_hook(__FILE__, 'wes_deactivate');

function wes_deactivate()
{
	Cron::remove_cron('wes_erp_sync_data');
	Logger::log_message("Plugin erp deactivated");
}

//Add ERP number to user profile
add_action('show_user_profile', 'extra_user_profile_fields');
add_action('edit_user_profile', 'extra_user_profile_fields');

function extra_user_profile_fields($user)
{ ?>
	<h3><?php _e("מספר לקוח ERP", "blank"); ?></h3>

	<table class="form-table">
		<tr>
			<th><label for="erp_num"><?php _e("מספר לקוח"); ?></label></th>
			<td>
				<input type="text" name="erp_num" id="erp_num" value="<?php echo esc_attr(get_the_author_meta('erp_num', $user->ID)); ?>" class="regular-text" /><br />
			</td>
		</tr>
	</table>
<?php }

add_action('personal_options_update', 'save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'save_extra_user_profile_fields');

function save_extra_user_profile_fields($user_id)
{
	if (!current_user_can('edit_user', $user_id)) {
		return false;
	}
	update_user_meta($user_id, 'erp_num', $_POST['erp_num']);
}

add_filter('manage_users_columns', 'wes_add_new_user_column');

function wes_add_new_user_column($columns)
{
	$columns['erp_num'] = 'מספר ERP';
	return $columns;
}

add_filter('manage_users_custom_column', 'wes_add_new_user_column_content', 10, 3);

function wes_add_new_user_column_content($content, $column, $user_id)
{
	if ('erp_num' === $column) {
		$content = get_the_author_meta('erp_num', $user_id);
	}
	return $content;
}

/**
 * Set a minimum order amount for checkout
 */
add_action( 'woocommerce_checkout_process', 'wes_minimum_order_amount' );
add_action( 'woocommerce_before_cart' , 'wes_minimum_order_amount' );
 
function wes_minimum_order_amount() {
    if ( WC()->cart->total < MIN_ORDER ) {
		
		if(is_rtl()){
			$msg ='הזמנה שלך היא %s מינימום הזמנה %s';
		}else{
			$msg = 'Your current order total is %s — you must have an order with a minimum of %s to place your order ' ;
		}
        if( is_cart() ) {
            wc_print_notice( 
                sprintf( $msg, 
                    wc_price( WC()->cart->total ), 
                    wc_price( MIN_ORDER)
                ), 'error' 
            );

        } else {
            wc_add_notice( 
                sprintf( $msg , 
                    wc_price( WC()->cart->total ), 
                    wc_price( MIN_ORDER )
                ), 'error' 
            );

        }
    }
}