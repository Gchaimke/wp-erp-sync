<?php

namespace WpErpSync;

use Laravie\Parser\Xml\Reader;
use Laravie\Parser\Xml\Document;

class ParseXml
{
    public function get_products_data()
    {
        $file = ERP_DATA_FOLDER . 'sync/ITEMS.XML';
        if (file_exists($file)) {
            $xml = (new Reader(new Document()))->load($file);

            $data = $xml->parse([
                'products' => ['uses' => 'Product[ProductCode>SKU,ProductName>name,Pricelist1>price,Pricelist2>wholesale_price,StockBalance>stock]'],
            ]);
            return $data;
        }
        return false;
    }

    public function get_clients_data()
    {

        $file = ERP_DATA_FOLDER . 'sync/CUSTOMERS.XML';
        if (file_exists($file)) {
            $xml = (new Reader(new Document()))->load($file);

            $data = $xml->parse([
                'clients' => ['uses' => 'Customer[CustomerCode>number,CustomerName>name,CustomerStreetName>street,CustomerHouseNo>house,CustomerCity>city,CustomerZipCode>zip,Phone1,Phone2,Fax,Cellular,Email,Collect,PricelistCode,PersonalPricelistNo]'],
            ]);
            return $data;
        }
        return false;
    }
}
