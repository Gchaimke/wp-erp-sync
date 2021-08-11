<?php

namespace WpErpSync;

class WesDashboard
{
    public function __construct()
    {
        wp_add_dashboard_widget(
            'wporg_dashboard_widget',                          // Widget slug.
            esc_html__('ERP Dashboard v.'.PLUGIN_NAME_VERSION , 'wes'), // Title.
            [$this, 'dashboar_content']                    // Display function.
        );
    }

    function dashboar_content()
    {
        $dir = ERP_DATA_FOLDER . "sync/";
        $files = glob($dir . "*.XML");
        $html = "<h1>Last update</h1><div class='wes_dash_updates'>";
        foreach ($files as $file) {
            $html .= "<div class='wes_row'><span>" . basename($file) .  "</span>  <span>" . date("d-m-Y H:i:s", filemtime($file)) . "</span></div>";
        }
        $html .= "</div><a class='button' target='_blank' href='/wp-admin/admin.php?page=wesSettings&sync=true'>Sync Now</a>";
        echo $html;
    }
}
