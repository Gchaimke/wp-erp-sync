<?php

namespace WpErpSync;

class Clients
{
    public $clientsCount;
    public function __construct()
    {
    }

    public function get_clients($filter = 0)
    {
        $data = new ParseXml();
        $clients = $data->get_clients_data()['clients'];
        $filtered_clients = array();
        $users_meta = $this->get_wp_users_meta();
        if ($filter == 1) {
            foreach ($clients as $client) {
                if ($this->isResseller($client)) {
                    array_push($filtered_clients, $client);
                }
            }
            $this->clientsCount = count($filtered_clients);
            return $filtered_clients;
        } else if ($filter == 2) {
            $this->clientsCount = count($clients);
            return $clients;
        } else {
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
            if ($meta['billing_email'][0] != '') {
                array_push($users_meta, $meta['billing_email'][0]);
            }
            if ($meta['billing_phone'][0] != '') {
                array_push($users_meta, $meta['billing_phone'][0]);
            }
            if ($meta['billing_address_1'][0] != '') {
                array_push($users_meta, $meta['billing_address_1'][0]);
            }
        }
        return $users_meta;
    }

    public function buildClientsTable($clients)
    {
        $table_data = "<table class='widefat striped'>
        <tr>
            <th>ERP Number</th>
            <th>Name</th>
            <th>Email</th>
            <th>Cellular</th>
            <th>Phone</th>
            <th>Street</th>
            <th>City</th>
            <th>Reseller</th>
            <th>Wp User</th>
        </tr>";

        foreach ($clients as $client) {
            $reseler = $this->isResseller($client) ? '<b style="color:green">yes</b>' : '<b style="color:red">no</b>';
            $exists = $this->isWpUser($client, $this->get_wp_users_meta()) ? '<b style="color:green">yes</b>' : '<b style="color:red">no</b>';
            $table_data .= "<tr class='client'>
                <td class='client_number'>{$client['number']}</td>
                <td class='client_name'>{$client['name']}</td>
                <td class='client_email'>{$client['Email']}</td>
                <td class='client_cellular'>{$client['Cellular']}</td>
                <td class='client_phone'>{$client['Phone1']}</td>
                <td class='client_street'>{$client['street']}</td>
                <td class='client_city'>{$client['city']}</td>
                <td class='client_exists'>$reseler</td>
                <td class='client_exists'>$exists</td></tr>";
        }

        $table_data .= " </table>";
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
}
