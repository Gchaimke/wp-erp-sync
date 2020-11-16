<h2>Settings</h2>

<?php if (isset($authUrl)) : ?>
    <div class="request">
        <a class='login' href='<?= $authUrl ?>'>Connect to Google</a>
    </div>
<?php endif ?>
<a class="button action Send" href="?page=wesSettings&sync=true" >Sync GDrive now.</a>