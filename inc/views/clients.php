<?php
$count = 0;
$table_data = '';
foreach ($clients as $client) {
    if ($client['PricelistCode'] == 2) {
        $table_data .= "<tr class='client'>
            <td class='client_number'>{$client['number']}</td>
            <td class='client_name'>{$client['name']}</td>
            <td class='client_email'>{$client['Email']}</td>
            <td class='client_street'>{$client['street']}</td>
            <td class='client_city'>{$client['city']}</td>
            </tr>";
        $count++;
    }
}
?>

<h2><?= $count ?> Resellers</h2>
<table class="widefat striped">
    <tr>
        <th>ERP Number</th>
        <th>Name</th>
        <th>Email</th>
        <th>Street</th>
        <th>City</th>
    </tr>
    <?php echo $table_data ?>
</table>