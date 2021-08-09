<?php
//Get the active tab from the $_GET param
$default_tab = null;
$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
?>

<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
<div class="form_control">
    <label class="label">TODO: Update all existing WP clients from XML</label>
    <div class='update_all_clients_button button action'>Update All</div>
</div>
<div class="form_control">
    <label class="label">Search XML clients by email, name, phone, erp number</label>
    <div class="form_control">
        <input type="text" id="search_client" name='search_client' value="">
        <div id='search_for_client' class='button action'>Search</div>
    </div>
</div>
<div class="spinner" style="float: none;"></div>
<div id='admin_message' class="updated notice" style="display: none;"></div>
<hr>
<nav class="nav-tab-wrapper">
    <a href="?page=wesClients" class="nav-tab <?php if ($tab === null) : ?>nav-tab-active<?php endif; ?>">WP Users </a>
    <a href="?page=wesClients&tab=resellers" class="nav-tab <?php if ($tab === 'resellers') : ?>nav-tab-active<?php endif; ?>">Resellers </a>
    <a href="?page=wesClients&tab=all" class="nav-tab <?php if ($tab === 'all') : ?>nav-tab-active<?php endif; ?>">All </a>
</nav>
<div class="tab-content">
    <div class='result'>
        <?php switch ($tab):
            case 'resellers':
                $html = $clientsCl->buildClientsTable($clientsCl->get_clients(1));
                echo "<script>jQuery('.nav-tab-active').append($clientsCl->clientsCount)</script>";
                echo $html;
                break;
            case 'all':
                echo $clientsCl->buildClientsTable($clientsCl->get_clients(2));
                echo "<script>jQuery('.nav-tab-active').append($clientsCl->clientsCount)</script>";
                break;
            default:
                echo $clientsCl->buildClientsTable($clientsCl->get_clients());
                echo "<script>jQuery('.nav-tab-active').append($clientsCl->clientsCount)</script>";
                break;
        endswitch; ?>
    </div>
</div>