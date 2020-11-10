<h2>Dashboard</h2>

<?php if (isset($authUrl)) : ?>
    <div class="request">
        <a class='login' href='<?= $authUrl ?>'>Connect to Google</a>
    </div>
<?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST') : ?>
    <form method="POST">
        <input type="number" name="order_num">
        <input class="button action" type="submit" value="Upload to GDrive" class="Send" />
    </form>
    <?php if (is_object($result)) : ?>
        <div class="shortened">
            <h4>File is uploaded: <?php echo $result['name']?></h4>
        </div>
    <?php else : ?>
        <div class="shortened">
            <p><?= $result ?></p>
        </div>
    <?php endif ?>
<?php else : ?>
    <h3>To upload the order, enter his number</h3>
    <form method="POST">
        <input type="number" name="order_num">
        <input class="button action" type="submit" value="Upload to GDrive" class="Send" />
    </form>
<?php endif ?>