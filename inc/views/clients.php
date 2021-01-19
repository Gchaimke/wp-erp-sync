<?php
//Get the active tab from the $_GET param
$default_tab = null;
$tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

?>

<!-- Our admin page content should all be inside .wrap -->
<div class="wrap">
    <!-- Print the page title -->
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
        <a href="?page=wesClients" class="nav-tab <?php if ($tab === null) : ?>nav-tab-active<?php endif; ?>">WP Users </a>
        <a href="?page=wesClients&tab=resellers" class="nav-tab <?php if ($tab === 'resellers') : ?>nav-tab-active<?php endif; ?>">Resellers </a>
        <a href="?page=wesClients&tab=all" class="nav-tab <?php if ($tab === 'all') : ?>nav-tab-active<?php endif; ?>">All </a>
    </nav>
    <div class="tab-content">
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