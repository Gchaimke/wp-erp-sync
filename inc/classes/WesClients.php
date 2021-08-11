<?php

namespace WpErpSync;

class WesClients
{
    public $clientsCount, $clients;
    public function __construct()
    {
        //before use ajax add calss init to main plugin file!
        add_action('wp_ajax_search_for_client', [$this, 'search_for_client']);
        add_action('wp_ajax_add_update_user', [$this, 'add_update_user']);
    }

    public function get_clients($filter = 0)
    {
        $data = new ParseXml();
        $clients = $data->get_clients_data();
        if ($clients) {
            $clients = $clients['clients'];
            $filtered_clients = array();
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
                    if ($this->isWpUser($client)) {
                        array_push($filtered_clients, $client);
                    }
                }
                $this->clientsCount = count($filtered_clients);
                return $filtered_clients;
            }
        } else {
            return array();
        }
    }

    public function buildClientsTable($clients)
    {
        $table_data = "<table class='widefat striped fixed_head clients_table'>" . $this->view_clients_table_head();
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
            <th>email</th>
            <th>Phone</th>
            <th>Cellular</th>
            <th>Street</th>
            <th>City</th>
            <th>Zip</th>
            <th>Reseller</th>
            <th>Wp User</th>
            <th>Add / Update</th>
        </tr></thead>";
        return $html;
    }

    public function view_client_line($client)
    {
        if ($this->isResseller($client)) {
            $reseller = 'yes';
            $reseller_style = 'color:green';
        } else {
            $reseller = 'no';
            $reseller_style = 'color:red';
        }

        if ($this->isWpUser($client)) {
            $exists = 'yes';
            $exists_style = 'color:green';
        } else {
            $exists = 'no';
            $exists_style = 'color:red';
        }

        $table_data = "<tr class='client' id='{$client['erp_num']}'>
                <td data-column='erp_num'>{$client['erp_num']}</td>
                <td data-column='name'>{$client['name']}</td>
                <td data-column='email'>{$client['email']}</td>
                <td data-column='phone'>{$client['Phone1']}</td>
                <td data-column='cellular'>{$client['Cellular']}</td>
                <td data-column='street'>{$client['street']} </td>
                <td data-column='city'>{$client['city']}</td>
                <td data-column='zip'>{$client['zip']} </td>
                <td data-column='reseller' style='$reseller_style'>$reseller</td>
                <td class='client_exists' style='$exists_style'>$exists</td>";
        if ($exists == 'no') {
            $table_data .= "<td><button class='button action'>add</button></td>";
        } else {
            $table_data .= "<td><button class='button action'>update</button></td>";
        }
        $table_data .= "</tr>";
        return $table_data;
    }

    function isWpUser($client)
    {
        if (isset($client["email"]) && $client["email"] != "") {
            $email_domain = explode("@", $client["email"]);
            if (count($email_domain) > 1) {
                $erp_email = $client["erp_num"] . "@" . strtolower($email_domain[1]);
                if (strtolower($email_domain[1]) == "avdor.com") {
                    if (email_exists($erp_email) && username_exists("user_" . $client["erp_num"])) return true;
                } else {
                    if (email_exists($client["email"]) && username_exists($client["email"])) return true;
                }
            }
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
            $table_data .= "<table class='widefat striped fixed_head clients_table'>" . $this->view_clients_table_head();
            foreach ($clients as $client) {
                if (
                    stripos($client['name'], $serch_txt) !== false ||
                    stripos($client['email'], $serch_txt) !== false ||
                    stripos($client['Phone1'], $serch_txt) !== false ||
                    stripos($client['Cellular'], $serch_txt) !== false ||
                    stripos($client['erp_num'], $serch_txt) !== false
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

    function add_update_user()
    {
        $user = $_POST['data'];
        $user["password"] = "W1418es!";
        if ($user["email"] != "") {
            if (!$this->isWpUser($user)) {
                $user_email = explode("@", $user["email"]);
                if (count($user_email) > 1 && strtolower($user_email[1]) == "avdor.com") {
                    $user['id'] = wp_create_user("user_" . $user["erp_num"], $user["password"], $user["erp_num"] . "@avdor.com");
                    if ($user['id'] > 1) {
                        $this->set_user_data($user);
                    }
                    echo "New user created! username: {$user["erp_num"]}";
                    die;
                } else {
                    $user['id'] = wp_create_user($user["email"], $user["password"], $user["email"]);
                    if ($user['id'] > 1) {
                        $this->set_user_data($user);
                    }
                    echo "New user created! username: {$user["name"]} and email: {$user["email"]}";
                    die;
                }
            } else {
                $user['id'] = username_exists($user["email"]);
                if (
                    get_user_meta($user['id'], 'billing_address_1', true) != $user["street"] ||
                    get_user_meta($user['id'], 'billing_address_2', true) != $user["phone"] ||
                    get_user_meta($user['id'], 'billing_city', true) != $user["city"] ||
                    get_user_meta($user['id'], 'billing_phone', true) != $user["cellular"]
                ) {
                    if ($user['id'] > 1) {
                        $this->set_user_data($user);
                    }
                    echo "User with username:{$user["name"]} and email:{$user["email"]} updated. ";
                    die;
                } else {
                    echo "This user up to date!";
                    die;
                }
            }
        }
        echo "Can't add user without email!";
        die;
    }

    function set_user_data($user)
    {
        $user_id = $user['id'];
        if ($user_id) {
            wp_update_user(
                array(
                    'ID'       => $user_id,
                    'first_name'    => $user["name"],
                    'nickname' => $user["name"],
                    'display_name' => $user["name"],
                    'role' => $user["reseller"] == "yes" ? "wholesale_customer" : "customer"
                )
            );
            update_user_meta($user_id, 'billing_address_1', $user["street"]);
            update_user_meta($user_id, 'billing_address_2', $user["phone"]);
            update_user_meta($user_id, 'billing_city', $user["city"]);
            update_user_meta($user_id, 'billing_country', "IL");
            update_user_meta($user_id, 'billing_postcode', $user["zip"]);
            update_user_meta($user_id, 'billing_phone', $user["cellular"]);
            update_user_meta($user_id, 'erp_num', $user["erp_num"]);
            //wp_mail( $user["name"], 'Welcome!', "Your username:{$user["email"]} and password is: {$user["password"]}");
        }
    }
}
