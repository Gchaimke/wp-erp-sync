<h1>Settings</h1>
<?php 
print($required_plugins_str);

if (isset($authUrl)) : ?>
    <div class="request">
        <a class='login' href='<?= $authUrl ?>'>Connect to Google</a>
    </div>
<?php endif ?>

<div>
    <h3>Google Drive Sync</h3>
    <a class="button" href="?page=wesSettings&sync=clear">Clear Sync folder</a>
    <a class="button" href="?page=wesSettings&sync=true">Sync GDrive</a>
</div>
<div>
    <h3>Cron Data</h3>
    <a class="button" href="?page=wesSettings&cron=run">Run Job</a>
    <h4>Next CRM cron job <?php echo date('d-m-Y H:i:s', wp_next_scheduled('wes_crm_sync_data')) ?></h4>
</div>