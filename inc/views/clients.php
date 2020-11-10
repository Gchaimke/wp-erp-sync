<?php
$count = 0;
$table_data='';
foreach ($clients as $client) {
    $table_data.= "<tr class='client'>
            <td class='client_number'>{$client['number']}</td>
            <td class='client_name'>{$client['name']}</td>
            <td class='client_street'>{$client['street']}</td>
            <td class='client_city'>{$client['city']}</td>
            </tr>";
    $count++;
}
?>

<h2><?=$count?> Clients</h2>
<table>
<tr>
    <th>number</th>
    <th>name</th>
    <th>street</th>
    <th>city</th>
    </tr>
    <?php echo $table_data?>
</table>