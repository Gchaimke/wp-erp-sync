<style>
    #log_list {
        position: relative;
        float: right;
        width: 10%;
        min-width: 70px;
    }

    #log_view {
        position: relative;
        float: right;
        width: 90%;
    }
</style>
<h1>Logs</h1>

<div id="log_list">
    <a class="button" href='admin.php?page=wesLogs&clear_logs=true'>Clear Logs Folder</a>
    <?php print($view_log_list) ?>
</div>
<div id="log_view">
    <pre><?php print($view_log) ?></pre>
</div>