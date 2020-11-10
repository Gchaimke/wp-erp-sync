<div id='admin_message' class="updated notice" style="display: none;"></div>
<h2>First <?= $product_class->get_active_products() ?> Active Products from <?= $product_class->get_count_products() ?></h2>
<div class='add_all_product_button button action'>Add All</div>
<div class='update_all_products_button button action'>Update All</div>
<table>
    <tr>
        <th>ID</th>
        <th>SKU</th>
        <th>name</th>
        <th>price</th>
        <th>stock</th>
        <th>add</th>
    </tr>

    <?php echo $table_data ?>
</table>