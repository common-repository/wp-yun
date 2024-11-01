<?php include('header.php'); ?>
<form action="options.php" method="post">
    <?php
    settings_fields('wp-yun');
    do_settings_sections('wp-yun'); ?>
    <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>"/>
</form>