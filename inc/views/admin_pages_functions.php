<?php

use  WpErpSync\ParseXml;
use WpErpSync\Product;
use WpErpSync\Google_Helper;

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

    if ($_GET['code'] != '') {
        $google_helper->generate_token($_GET['code']);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $client->getAccessToken()) {
        $result = $google_helper->upload_file($_POST['order_num'],$google_helper->get_service());
    }

    if ($_GET['sync'] == 'true') {
        $google_helper->get_sync_files($google_helper->get_service());
    }
    include 'dashboard.php';
}

function wes_clients()
{
    $data = new ParseXml();
    $clients = $data->get_clients_data()['clients'];
    include 'clients.php';
}

function wes_products()
{
    $product_class = new Product();
    if (isset($_GET['limit'])) {
        $product_class->set_products_limit($_GET['limit']);
    }
    $table_data = $product_class->view_products();
    include 'products.php';
}
