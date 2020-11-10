<?php

use  WpErpSync\ParseXml;
use WpErpSync\Product;
use WpErpSync\Google_Helper;

function wes_dashboard()
{
    $google_helper = new Google_Helper();
    $client = $google_helper->get_client();
    $google_helper->register_session();

    if ($_GET['code'] != '') {
        $google_helper->generate_token($_GET['code']);
    }
    // set the access token as part of the client
    if (empty($_SESSION['upload_token'])) {
        $authUrl = $google_helper->get_token_from_refresh();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && $client->getAccessToken()) {
        $result = $google_helper->upload_file($_POST['order_num'], $google_helper->get_service());
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
