<h1>XML Products</h1>
<div class="form_control">
    <label class="label">Add all products on this page from XML to WP</label>
    <div class='add_all_product_button button action'>Add All</div>
</div>
<div class="form_control">
    <label class="label">Update all existing WP products from XML</label>
    <div class='update_all_products_button button action'>Update All</div>
</div>
<div class="form_control">
    <label class="label">View all XML products</label>
    <a class='button action' href="?page=wesProducts&limit=no">View All</a>
</div>
<div class="form_control">
    <label class="label">Search XML products by SKU</label>
    <input type="text" id="search_product" name='product-search' value="">
    <div class='search_for_product button action'>Search</div>
    <div class='add_products_from_search button action' style="display: none;">Add All From Search</div>
</div>
<div class="spinner" style="float: none;"></div>
<div id='admin_message' class="updated notice" style="display: none;"></div>
<hr>
<h2>First <?= $product_class->get_active_products() ?> Active Products from <?= $product_class->get_counted_products() ?></h2>
<div class='result'>
    <?php try { ?>
    <?php } catch (\Throwable $th) { ?>
        <h2>No XML file, please sync!</h2>
    <?php } ?>

    <?php echo $table_data ?>
</div>