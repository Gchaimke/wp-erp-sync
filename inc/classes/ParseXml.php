<?php

namespace WpErpSync;

use Laravie\Parser\Xml\Reader;
use Laravie\Parser\Xml\Document;

class ParseXml
{
    public function get_products_data()
    {
        $file = BASE_PATH . 'erp-data/sync/ITEMS.XML';
        $xml = (new Reader(new Document()))->load($file);

        $data = $xml->parse([
            'products' => ['uses' => 'Product[ProductCode>SKU,ProductName>name,Pricelist1>price,Pricelist2>wholesele_price,StockBalance>stock]'],
        ]);
        return $data;
    }

    public function get_clients_data()
    {
        $file = BASE_PATH . 'erp-data/sync/CUSTOMERS.XML';
        $xml = (new Reader(new Document()))->load($file);

        $data = $xml->parse([
            'clients' => ['uses' => 'Customer[CustomerCode>number,CustomerName>name,CustomerStreetName>street,CustomerHouseNo>house,CustomerCity>city,CustomerZipCode>zip,Phone1,Phone2,Fax,Cellular,Email,Collect,PricelistCode,PersonalPricelistNo]'],
        ]);
        return $data;
    }
}
