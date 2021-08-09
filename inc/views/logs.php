<h1>Logs</h1>
<h2>Server time: <?= date('H:i:s') ?></h2>
<div class="form_control">
    <label>Last 10 Logs: </label><?php print($view_log_list) ?>
</div>
<div class="form_control">
    <label>Select log date: </label><input id="log_date" type="date" value="">
</div>
<a class="button" href='admin.php?page=wesLogs&clear_logs=true'>Clear Logs</a>
<div id="log_view">
    <pre><?php print($view_log) ?></pre>
</div>
<script>
    document.getElementById("log_date").value = '<?= date('Y-m-d', strtotime($_GET['log'])) ?>'
</script>