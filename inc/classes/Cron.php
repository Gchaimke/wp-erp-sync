<?php

namespace WpErpSync;

class Cron
{
    public function __construct()
    {
        add_action('wes_crm_sync_data', [$this, 'wes_cron_exec']);
        add_filter('cron_schedules', [$this, 'wes_cron_interval']);
        $timestamp = date('d-m-Y H:i:s', wp_next_scheduled('wes_crm_sync_data'));
        if (!wp_next_scheduled('wes_crm_sync_data')) {
            wp_schedule_event(time(), 'ten_minutes', 'wes_crm_sync_data');
        }
    }

    public static function remove_cron($job)
    {
        $timestamp = wp_next_scheduled($job);
        wp_unschedule_event($timestamp, $job);
    }

    public static function wes_cron_exec()
    {
        Logger::log_message('Cron job running.');
        //Cron Sync data files with Gdrive
        $google_helper = new Google_Helper();
        $client = $google_helper->get_client();
        $token = file_get_contents($google_helper->tokenPath);
        $tokenObj = json_decode($token);
        $client->setAccessToken($tokenObj->access_token);
        $google_helper->get_sync_files($google_helper->get_service());
        //Cron Update products data
        $product_class = new Product();
        $product_class->update_all_products();
        Logger::log_message('Cron job End.');
    }

    public static function get_all_jobs()
    {
        echo ' <table class="widefat striped ">';
        echo '<tr><th>Time</th><th>Job Name</th><th>Interval</th><th>Action</th></tr>';
        foreach (_get_cron_array() as $key => $job) {
            foreach ($job as $jkey => $job_name) {
                foreach ($job_name as $data) {
                    echo '<tr><td>' . date('d-m-Y H:i:s', $key) . '</td>';
                    if ($jkey == 'wes_crm_sync_data') {
                        echo '<td><b>' . $jkey . '</b></td>';
                    } else {
                        echo '<td>' . $jkey . '</td>';
                    }
                    echo '<td>' . $data['schedule'] . '</td>';
                    echo '<td><a class="button" href="?page=wesSettings&remove_cron=' . $jkey . '">remove</a></td>';
                }
            }
            echo '</tr>';
        }
    }

    function wes_cron_interval($schedules)
    {
        $schedules['ten_minutes'] = array(
            'interval' => 600,
            'display'  => esc_html__('Every Ten Minutes'),
        );
        return $schedules;
    }
}
