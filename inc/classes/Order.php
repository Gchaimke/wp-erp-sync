<?php

namespace WpErpSync;

use SimpleXMLElement;

class Order
{
    public function __construct()
    {
        add_action('woocommerce_thankyou_order_id', [$this, 'wes_sync_order']);
    }
    
    function wes_sync_order($order_id){
        $file_status = $this->wes_create_order($order_id);
        if($file_status){
            Logger::log_message('New order created! SITEDOC_' . $order_id . '.xml');
            $status = $this->wes_upload_order($order_id);
            if($status){
                Logger::log_message("Order: $order_id uploaded success!");
            }else{
                Logger::log_message("Order: $order_id not uploaded!");
            }
        }else{
            Logger::log_message("Error! SITEDOC_$order_id.xml not created!");
        }
        
    }
    
    function wes_create_order($order_id){
        
        $order_data = wc_get_order($order_id);

        $erp_num = get_the_author_meta('erp_num', $order_data->get_customer_id());
        $erp_num = $erp_num == '' ? 30378 : $erp_num;
        $payment_method = $order_data->get_payment_method_title() == "PayPal" ? 1305 : 1301;
        $date_paid = $order_data->get_date_paid();
        $paid = $date_paid == NULL ? 'F': 'T';
        
        $reportProducts = array();
        $total_lines = 0;
        $vat = 0;
        foreach ($order_data->get_items() as $item) {
            $product_data = wc_get_product($item->get_product_id());
            $total_lines += number_format($item->get_total(), 2, '.', '');
            $reportProducts[] = array(
                'ProductCode'                 => $product_data->get_sku(),
                'AdditionalProductCode'     => '',//$item->get_product_id()
                'Quantity'                    =>  $item->get_quantity(),
                'UnitPrice'                    => number_format($product_data->get_regular_price(), 2, '.', ''),
                'PctLineDiscount'             => 0,
                'TotalLine'                 => $item->get_total(),
                'LineComment'                => '',
            );
            $tax_rates = \WC_Tax::get_base_tax_rates($item->get_tax_class(true));
            if (!empty($tax_rates)) {
                $tax_rate = reset($tax_rates);
            }

            $vat = $tax_rate['rate'];
        }
    
        if ($order_data->get_shipping_total() > 0) {
            $shiping_without_VAT = $order_data->get_shipping_total() / ((100+$vat)/100);
            $reportProducts[] = array(
                'ProductCode'                 => "TRANSPORT",
                'AdditionalProductCode'     => '',
                'Quantity'                    => 1,
                'UnitPrice'                    => number_format($shiping_without_VAT, 2, '.', ''),
                'PctLineDiscount'             => 0,
                'TotalLine'                 => number_format($shiping_without_VAT, 2, '.', ''),
                'LineComment'                => '',
            );
            
            $total_lines += $shiping_without_VAT;
        }

        $ReportOrder = array(
            "Documents" => array(
                'DocumentDate'             =>  date('d/m/Y'),
                'DocumentType'             => 17,
                'DocumentSeries'         => 1,
                'DocumentDesign'         => 0,
                'DocumentNumber'         => $order_data->get_id(),
                'SellToCustomerNo'         => $erp_num,
                'SellToCustomerName'     => $order_data->get_billing_first_name()." ".$order_data->get_billing_last_name(),
                'CustomerStreetName'     => $order_data->get_billing_address_1(),
                'CustomerStreetNameExt' => $order_data->get_billing_address_2(),
                'CustomerHouseNo'         => "",
                'CustomerCity'             => $order_data->get_billing_city(),
                'CustomerZipcode'         => $order_data->get_billing_postcode(),
                'Phone1'                 => $order_data->get_billing_phone(),
                'DeliveryStreetName'     => $order_data->get_shipping_address_1()." ".$order_data->get_shipping_address_2(),
                'DeliveryStreetNameExt' => '',
                'DeliveryHouseNo'         => '',
                'DeliveryCity'             => '',
                'DeliveryZipcode'         => '',
                'Phone2'                 => $order_data->get_billing_phone(),
                'CustomerOrderNo'         => '',
                'CompanyNumber'         => '',
                'DueDate'                 => '',
                'DeliveryDate'             =>  date('d/m/Y'),
                'DeliveryType'             => $order_data->get_shipping_total() > 0 ? 2 : 1,
                'DocumentRem'             => '注讘讜专: ' . $order_data->get_billing_first_name() . " " . $order_data->get_billing_last_name(),
                'DocumentComment1'         => $order_data->get_payment_method_title(),
                'DocumentComment2'         => $order_data->get_billing_email(),
                'DocumentPaid'             => $paid,
                'BankAccount'             => $payment_method, //bank 1301, paypal 1305
                'ReceiptAmount'         => number_format($order_data->get_total(), 2, '.', ''),//total cart + shiping with VAT
                'TotalLines'             => number_format($total_lines, 2, '.', ''), //total cart + shiping without VAT
                'SalesDocumentTotal'     => number_format($order_data->get_total(), 2, '.', ''),//total cart + shiping with VAT,
                'DocumentLines'         => $reportProducts,
            )
        );
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root></root>');
        $this->array_to_xml($ReportOrder, $xml, 'SalesLine');
        return $xml->asXML(ERP_DATA_FOLDER . "orders/SITEDOC_" . $order_id . '.xml');
    }
    
    function array_to_xml( $data, &$xml_data, $get_key = 'SalesLine' )
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = $get_key; //dealing with <0/>..<n/> issues
            }
            if (is_array($value)) {
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode, $get_key);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    function wes_upload_order($order_id)
    {
        //Google Upload
        $google_helper = new Google_Helper();
        $client = $google_helper->get_client();
        $token = $google_helper->get_token()->access_token;
        $client->setAccessToken($token);
        $sync_status = $google_helper->try_to_sync($google_helper->get_service());
        $count = 5;
        do {
            if ($sync_status > 0){
                Logger::log_message('Uploading order:' . $order_id);
                $google_helper->upload_file($order_id, $google_helper->get_service());
                return true;
            }
            if ($sync_status == -1) {
                Logger::log_message('Try to get token from refresh.');
                $google_helper->get_token_from_refresh();
            }
            $count--;
        } while ($sync_status > 0 || $count == 0);
        return false;
    }
}
