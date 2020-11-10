<?php

namespace WpErpSync;

class Product
{
    private $max_products;
    private $active_products;
    private $count_products;
    public function __construct()
    {
        $this->set_products_limit(500);
        add_action('wp_ajax_add_product', [$this, 'add_product']);
        add_action('wp_ajax_add_all_products', [$this, 'add_all_products']);
        add_action('wp_ajax_update_all_products', [$this, 'update_all_products']);
    }

    public function get_products_limit()
    {
        return $this->max_products;
    }

    public function set_products_limit($limit=500)
    {
        $this->max_products = $limit;
    }

    public function get_active_products()
    {
        return $this->active_products;
    }

    public function set_active_products($count){
        $this->active_products = $count;
    }

    public function get_count_products()
    {
        return $this->count_products;
    }

    public function set_count_products($count){
        $this->count_products = $count;
    }

    public function view_products()
    {
        $data = new ParseXml();
        $table_data = '';
        $count = 0;
        $active = 0;
        $products = $data->get_products_data()['products'];
        foreach ($products as $product) {
            if ($product['price'] > 0 && $product['stock'] > 0) {
                $table_data .= "<tr class='product'>
                    <td id='product_num'>{$count}</td>
                    <td id='product_sku'>{$product['SKU']}</td>
                    <td id='product_name'>{$product['name']}</td>
                    <td id='product_price'>{$product['price']}</td>
                    <td id='product_stock'>{$product['stock']}</td>
                    <td class='add_product_button button action'>Add</tr>";
                $active++;
            }
            $count++;
            if ($count > $this->get_products_limit())
                break;
        }
        $this->set_active_products($active);
        $this->set_count_products($count);
        return $table_data;
    }

    public function add_product()
    {
        check_ajax_referer('wes', 'security');
        $product_data = explode(",", $_POST['data']);
        $product = array(
            'post_title' => $product_data[2],
            'post_content' => '',
            'post_status' => 'draft',
            'post_type' => 'product'
        );
        $post_id = wp_insert_post($product);
        wp_set_object_terms($post_id, 'simple', 'product_type');
        update_post_meta($post_id, '_regular_price', intval($product_data[3], 10));
        update_post_meta($post_id, '_price', intval($product_data[3], 10));
        update_post_meta($post_id, '_sku', $product_data[1]);
        update_post_meta($post_id, '_manage_stock', 'yes');
        update_post_meta($post_id, '_backorders', 'yes');
        update_post_meta($post_id, '_stock', intval($product_data[4], 10));

        echo ($product_data[2] . " added successfuly!");
    }

    function add_all_products()
    {
        $data = new ParseXml();
        $products = $data->get_products_data()['products'];
        $count = 0;
        $success = 0;

        foreach ($products as $product) {
            if ($product['price'] > 0 && $product['stock'] > 0) {
                $product_array = array(
                    'post_title' => $product['name'],
                    'post_content' => '',
                    'post_status' => 'draft',
                    'post_type' => 'product'
                );
                $post_id = wp_insert_post($product_array);
                wp_set_object_terms($post_id, 'simple', 'product_type');
                update_post_meta($post_id, '_regular_price', intval($product['price'], 10));
                update_post_meta($post_id, '_price', intval($product['price'], 10));
                update_post_meta($post_id, '_sku', $product['SKU']);
                update_post_meta($post_id, '_manage_stock', 'yes');
                update_post_meta($post_id, '_backorders', 'yes');
                update_post_meta($post_id, '_stock', intval($product['stock'], 10));
                $success++;
            }
            $count++;
            if ($count > $this->get_products_limit())
                break;
        }
        echo ($success . ' new products added!');
    }


    function update_all_products()
    {
        global $wpdb;
        $data = new ParseXml();
        $products = $data->get_products_data()['products'];
        $count = 0;
        $success = 0;
        foreach ($products as $product) {
            if ($product['price'] > 0 && $product['stock'] > 0) {
                $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $product['SKU']));
                update_post_meta($product_id, '_regular_price', intval($product['price'], 10));
                update_post_meta($product_id, '_price', intval($product['price'], 10));
                update_post_meta($product_id, '_stock', intval($product['stock'], 10));
                $success++;
            }
            $count++;
            if ($count > $this->get_products_limit())
                break;
        }
        echo ($success . ' products updated!');
    }
}
