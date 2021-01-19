<?php

namespace WpErpSync;

use SimpleXMLElement;

class Order
{
    public function __construct()
    {
        add_action('woocommerce_thankyou_order_id', [$this, 'wes_sync_order']);
    }

    function wes_sync_order($order_id)
    {
        $order_data = wc_get_order($order_id);
        
        $erp_num = get_the_author_meta('erp_num',$order_data->get_customer_id());
        if($erp_num==''){
            $erp_num = 30378;
        }
        $date_paid = $order_data->get_date_paid();
        $paid = 'F';
        if($date_paid != NULL){
            $paid = 'T';
        }
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
        $documents = $xml->addChild('Documents');
        $documents->addChild('DocumentDate', date('d/m/Y'));
        $documents->addChild('DocumentType', 17);
        $documents->addChild('DocumentSeries', 1);
        $documents->addChild('DocumentDesign', 0);
        $documents->addChild('DocumentNumber', $order_data->get_id());
        $documents->addChild('SellToCustomerNo', $erp_num);
        $documents->addChild('SellToCustomerName', $order_data->get_billing_first_name());
        $documents->addChild('CustomerStreetName', $order_data->get_billing_address_1());
        $documents->addChild('CustomerStreetNameExt', $order_data->get_billing_address_2());
        $documents->addChild('CustomerHouseNo', '');
        $documents->addChild('CustomerCity', $order_data->get_billing_city());
        $documents->addChild('CustomerZipcode', $order_data->get_billing_postcode());
        $documents->addChild('Phone1', $order_data->get_billing_phone());
        $documents->addChild('DeliveryStreetName', $order_data->get_shipping_address_1());
        $documents->addChild('DeliveryStreetNameExt', $order_data->get_shipping_address_2());
        $documents->addChild('DeliveryHouseNo', '');
        $documents->addChild('DeliveryCity', $order_data->get_shipping_city());
        $documents->addChild('DeliveryZipcode', $order_data->get_shipping_postcode());
        $documents->addChild('Phone2', $order_data->get_billing_phone());
        $documents->addChild('CustomerOrderNo', '');
        $documents->addChild('CompanyNumber', '');
        $documents->addChild('DueDate', '');
        $documents->addChild('DeliveryDate', '');
        $documents->addChild('DeliveryType', $order_data->get_shipping_method());
        $documents->addChild('DocumentRem', $order_data->get_customer_note());
        $documents->addChild('DocumentComment1', $order_data->get_customer_note());
        $documents->addChild('DocumentComment2', $order_data->get_billing_email());
        $documents->addChild('DocumentPaid', $paid);
        $documents->addChild('BankAccount', '');
        $documents->addChild('ReceiptAmount', $order_data->get_total());
        $documents->addChild('TotalLines', $order_data->get_subtotal());
        $documents->addChild('SalesDocumentTotal', $order_data->get_total());
        $order = $documents->addChild('DocumentLines', '');
        foreach ($order_data->get_items() as $item) {
            $product_data = wc_get_product($item->get_product_id());
            $product = $order->addChild('SalesLine');
            $product->addChild('ProductCode', $product_data->get_sku());
            $product->addChild('AdditionalProductCode', $item->get_product_id());
            $product->addChild('Quantity', $item->get_quantity());
            $product->addChild('UnitPrice', $product_data->get_regular_price());
            $product->addChild('PctLineDiscount', 0);
            $product->addChild('TotalLine', $item->get_total());
            $product->addChild('LineComment', $item->get_name());
        }
        if ($order_data->get_shipping_method() != 'Pickup') {
            $product = $order->addChild('SalesLine');
            $product->addChild('ProductCode', 'TRANSPORT');
            $product->addChild('AdditionalProductCode', '');
            $product->addChild('Quantity', 1);
            $product->addChild('UnitPrice', $order_data->get_shipping_total());
            $product->addChild('PctLineDiscount', 0);
            $product->addChild('TotalLine', $order_data->get_shipping_total());
            $product->addChild('LineComment', '');
        }
        $result = $xml->asXML(ERP_DATA_FOLDER . "orders/SITEDOC_".$order_id . '.xml');
        //Google Upload
        $google_helper = new Google_Helper();
        $client = $google_helper->get_client();
        $token = file_get_contents($google_helper->tokenPath);
        $tokenObj = json_decode($token);
        $client->setAccessToken($tokenObj->access_token);
        $sync_status = $google_helper->try_to_sync($google_helper->get_service());
        if ($sync_status > 0) {
            Logger::log_message('Uploading order:'.$order_id);
            $google_helper->upload_file($order_id,$google_helper->get_service());
        }else if($sync_status == -1){
            Logger::log_message('Try to get token from refresh.');
            $google_helper->get_token_from_refresh();
            Logger::log_message('Uploading order:'.$order_id);
            $google_helper->upload_file($order_id,$google_helper->get_service());
        }        
        //$google_helper->redirect_to_url('/shop',5000);
        Logger::log_message('New order created! SITEDOC_'.$order_id.'.xml');
        return $result;
    }
}
