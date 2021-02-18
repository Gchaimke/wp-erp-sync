<?php

use WpErpSync\Cron;
use WpErpSync\ParseXml;
use WpErpSync\WesProduct;
use WpErpSync\Clients;
use WpErpSync\Google_Helper;
use WpErpSync\Logger;

function wes_dashboard()
{
    $google_helper = new Google_Helper();
    $client = $google_helper->get_client();

    if (!empty($_SESSION['upload_token'])) {
        $client->setAccessToken($_SESSION['upload_token']);
        if ($client->isAccessTokenExpired()) {
            unset($_SESSION['upload_token']);
        }
    } else {
        if (!$google_helper->get_token_from_refresh()) {
            $authUrl = $client->createAuthUrl();
        }
    }

    if (isset($_GET['code']) && $_GET['code'] != '') {
        $google_helper->generate_token($_GET['code']);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $client->getAccessToken()) {
        $result = $google_helper->upload_file($_POST['order_num'], $google_helper->get_service());
    }
    include 'dashboard.php';
}

function wes_clients()
{
    $clientsCl = new Clients();
    include 'clients.php';
}

function wes_products()
{
    $product_class = new WesProduct();
    if (isset($_GET['limit'])) {
        if ($_GET['limit'] == 'no') {
            $product_class->set_products_limit(count($product_class->products));
        } else {
            $product_class->set_products_limit($_GET['limit']);
        }
    }
    $table_data = $product_class->view_products();
    include 'products.php';
}

function wes_settings()
{
    $required_plugins = array(
        'woocommerce-gateway-paypal-express-checkout' => 'WC_Gateway_PPEC_Plugin',
        'woocommerce-wholesale-prices' => 'WooCommerceWholeSalePrices'
    );

    $required_plugins_str = '<h4>תוספים שצרכים להיות מותקנים:</h4>';
    foreach ($required_plugins as $name => $class) {
        $required_plugins_str .= "<li>";
        if (class_exists($class)) {
            $required_plugins_str .= $name . '<span style="color:green;font-weight: 600;"> - פעיל';
        } else {
            $plugin_link = "plugin-install.php?tab=plugin-information&plugin={$name}";
            $required_plugins_str .= "$name<span style='color:red;font-weight: 600;'> - לא פעיל <a class='install-now button' href='$plugin_link' target='_blank'>Install</a>";
        }
        $required_plugins_str .= "</li>";
    }

    include 'settings.php'; //view settings page

    $google_helper = new Google_Helper();
    $client = $google_helper->get_client();
    if (!empty($_SESSION['upload_token'])) {
        $client->setAccessToken($_SESSION['upload_token']);
        if ($client->isAccessTokenExpired()) {
            unset($_SESSION['upload_token']);
        }
    } else {
        if (!$google_helper->get_token_from_refresh()) {
            $authUrl = $client->createAuthUrl();
        }
    }

    if (isset($_GET['sync']) && $_GET['sync'] == 'true') {
        $google_helper->get_sync_files($google_helper->get_service());
    }

    if (isset($_GET['sync']) && $_GET['sync'] == 'clear') {
        $google_helper->clear_sync_folder();
    }

    if (isset($_GET['cron']) && $_GET['cron'] == 'run') {
        Logger::log_message('Cron by Click');
        Cron::wes_cron_exec();
    }

    if (isset($_GET['remove_cron']) && $_GET['remove_cron'] != '') {
        Cron::remove_cron($_GET['remove_cron']);
        echo '<h4>' . $_GET['remove_cron'] . ' job removed</h4>';
    }
    Cron::get_all_jobs();
}

function wes_logs()
{
    $logs = Logger::getFileList();
    $i = 0;
    $view_log = '';
    $view_log_list = '<select name="logs" id="select_logs" onchange="view_selected_log()">';
    foreach ($logs as $log) {
        $log_name_array = explode('/', $log);
        $log_name = end($log_name_array);
        $log_date = substr($log_name, 0, -4);
        $view_log_list .= "<option value='$log_date'>$log_date</option>";
        $i++;
        if ($i > 10) {
            break;
        }
    }
    $view_log_list .= '</select>';
    if (isset($_GET['log'])) {
        $view_log = Logger::getlogContent($_GET['log']);
    } else {
        $view_log = Logger::getlogContent(Date('d-m-Y'));
    }

    if (isset($_GET['clear_logs'])) {
        $view_log = Logger::clearLogs();
    }


    include 'logs.php';
}
