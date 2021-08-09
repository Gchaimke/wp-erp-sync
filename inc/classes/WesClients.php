<?php

namespace WpErpSync;

class WesClients
{
    public $clientsCount, $clients;
    public function __construct()
    {
        $this->clinets = $this->get_clients();

        //before use ajax add calss init to main plugin file!
        add_action('wp_ajax_search_for_client', [$this, 'search_for_client']);
    }

    public function get_clients($filter = 0)
    {
        $data = new ParseXml();
        $clients = $data->get_clients_data()['clients'];
        $filtered_clients = array();
        $users_meta = $this->get_wp_users_meta();
        if ($filter == 1) { //resselers
            foreach ($clients as $client) {
                if ($this->isResseller($client)) {
                    array_push($filtered_clients, $client);
                }
            }
            $this->clientsCount = count($filtered_clients);
            return $filtered_clients;
        } else if ($filter == 2) { //all users
            $this->clientsCount = count($clients);
            return $clients;
        } else { //wp users
            foreach ($clients as $client) {
                if ($this->isWpUser($client, $users_meta)) {
                    array_push($filtered_clients, $client);
                }
            }
            $this->clientsCount = count($filtered_clients);
            return $filtered_clients;
        }
    }

    public function get_wp_users_meta()
    {
        $all_users = get_users();
        $users_meta = array();
        foreach ($all_users as $user) {
            $meta = get_user_meta($user->ID);
            if (is_array($meta)  && array_key_exists("billing_email", $meta) && $meta['billing_email'][0] != 0) {
                array_push($users_meta, $meta['billing_email'][0]);
            }
            if (is_array($meta) && array_key_exists("billing_phone", $meta) && $meta['billing_phone'][0] != 0) {
                array_push($users_meta, $meta['billing_phone'][0]);
            }
            if (is_array($meta) && array_key_exists("billing_address_1", $meta) && $meta['billing_address_1'][0] != 0) {
                array_push($users_meta, $meta['billing_address_1'][0]);
            }
        }
        return $users_meta;
    }

    public function buildClientsTable($clients)
    {
        $table_data = "<table class='widefat striped fixed_head'>" . $this->view_clients_table_head();
        foreach ($clients as $client) {
            $table_data .= $this->view_client_line($client);
        }

        $table_data .= " </table>";
        return $table_data;
    }

    function view_clients_table_head()
    {
        $html = "<thead>
        <tr>
            <th>ERP Number</th>
            <th>Name</th>
            <th>Email</th>
            <th>Cellular / Phone</th>
            <th>Street / City</th>
            <th>Reseller</th>
            <th>Wp User</th>
        </tr></thead>";
        return $html;
    }

    public function view_client_line($client)
    {
        $table_data = "";
        $reseler = $this->isResseller($client) ? '<b style="color:green">yes</b>' : '<b style="color:red">no</b>';
        $exists = $this->isWpUser($client, $this->get_wp_users_meta()) ? '<b style="color:green">yes</b>' : '<b style="color:red">no</b>';
        $table_data .= "<tr class='client'>
                <td class='client_number'>{$client['number']}</td>
                <td class='client_name'>{$client['name']}</td>
                <td class='client_email'>{$client['Email']}</td>
                <td class='client_cellular'>{$client['Cellular']} | {$client['Phone1']}</td>
                <td class='client_street'>{$client['street']} {$client['city']}</td>
                <td class='client_exists'>$reseler</td>
                <td class='client_exists'>$exists</td></tr>";
        return $table_data;
    }

    public function isWpUser($client, $users_meta)
    {
        if (in_array($client['Email'], $users_meta)) {
            return true;
        } else if (in_array($client['Cellular'], $users_meta)) {
            return true;
        } else if (in_array($client['Phone1'], $users_meta)) {
            return true;
        }
        return false;
    }

    public function isResseller($client)
    {
        if ($client['PricelistCode'] == 2) {
            return true;
        }
        return false;
    }

    function search_for_client()
    {
        $serch_txt = $_POST['data'] . '';
        $table_data = '';
        $count = 0;
        $clients = $this->get_clients(2);
        if (strlen($serch_txt) > 2) {
            $table_data .= "<table id='search_table' class='widefat striped fixed_head'>" . $this->view_clients_table_head();
            foreach ($clients as $client) {
                if (
                    stripos($client['name'], $serch_txt) !== false ||
                    stripos($client['Email'], $serch_txt) !== false ||
                    stripos($client['Phone1'], $serch_txt) !== false||
                    stripos($client['Cellular'], $serch_txt) !== false||
                    stripos($client['number'], $serch_txt) !== false
                ) {
                    $table_data .= $this->view_client_line($client);
                    $count++;
                }
            }
            $table_data .= "</table>";
            if (!$count > 0) {
                echo '<h2>' . $serch_txt . ' not found</h2>';
                return;
            }
        } else {
            echo "search must be more than 3 signs!";
        }
        echo $table_data;
        wp_die();
    }
}
