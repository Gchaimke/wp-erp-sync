<h1>Logs</h1>

<div id="log_list">
<label>Last 10 Logs: </label><?php print($view_log_list) ?><br>
<label>Select log date: </label><input id="log_date" type="date" value=""><div id="get_date" class="button" >View Log</div><br>
    <a class="button" href='admin.php?page=wesLogs&clear_logs=true'>Clear Logs</a>
    
</div>
<div id="log_view">
    <pre><?php print($view_log) ?></pre>
</div>