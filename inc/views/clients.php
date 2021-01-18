<?php
$users_count = 0;
$wp_users_count = 0;
$resalers_count = 0;
$table_data = '';
if (isset($clients)) {
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
    foreach ($clients as $client) {
        if ($client['PricelistCode']) {
            $resaler = ($client['PricelistCode']==2)?'yes':'no';
            $exists = 'no';
            if (in_array($client['Email'], $users_meta)) {
                $exists = 'yes';
            } else if (in_array($client['Cellular'], $users_meta)) {
                $exists = 'yes';
            } else if (in_array($client['Phone1'], $users_meta)) {
                $exists = 'yes';
            } 

            if ($exists == 'yes') {
                $wp_users_count++;
            }
            if($resaler == 'yes'){
                $resalers_count++;
            }
            $table_data .= "<tr class='client'>
            <td class='client_number'>{$client['number']}</td>
            <td class='client_name'>{$client['name']}</td>
            <td class='client_email'>{$client['Email']}</td>
            <td class='client_cellular'>{$client['Cellular']}</td>
            <td class='client_phone'>{$client['Phone1']}</td>
            <td class='client_street'>{$client['street']}</td>
            <td class='client_city'>{$client['city']}</td>
            <td class='client_exists'>$exists</td></tr>";//PricelistCode
            $users_count++;
        }
    }
}



?>

<h4><?= $users_count ?> Users</h4>
<h4><?= $wp_users_count ?> WP Users</h4>
<h4><?= $resalers_count ?> Reseler Users</h4>
<table class="widefat striped">
    <tr>
        <th>ERP Number</th>
        <th>Name</th>
        <th>Email</th>
        <th>Cellular</th>
        <th>Phone</th>
        <th>Street</th>
        <th>City</th>
        <th>Wp User</th>
    </tr>
    <?php echo $table_data ?>
</table>