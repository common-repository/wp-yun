<?php include('header.php'); ?>
<div class="wrap">
    <?php
    $posts = get_posts('posts_per_page=5');
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM  $wpdb->postmeta WHERE  meta_key =  'plinkShortURL'", OBJECT);
    ?>
    <table id="myTable" class="display">
        <thead>
        <tr>
            <th><?php echo __('Long URL', 'wp-yun') ?></th>
            <th style="width: 200px"><?php echo __('Short URL', 'wp-yun') ?></th>
            <th style="width: 200px"><?php echo __('Actions', 'wp-yun') ?></th>
        </tr>
        </thead>
    </table>
</div>
<script>
    jQuery(document).ready(function () {
        jQuery('#myTable').DataTable({
            'ajax': {
                'url': '/wp-json/wp-yun/shorted-urls',
                'type': 'GET',
                'beforeSend': function (request) {
                    request.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce( 'wp_rest' ) ?>');
                }
            },
            'columns': [
                {
                    'data': 'longUrl',
                    'render': function (data, type, row) {
                        return '<a href="' + data + '" target="_blank">' + row['title'] + '</a>';
                    },
                },
                {
                    'data': 'shortUrl',
                    'render': function (data, type, row) {
                        return  '<div style="text-align: center"><a href="https://' + data + '" target="_blank">' + data + '</a></div>';
                    },
                },
                {
                    'data': 'statsUrl',
                    'render': function (data, type, row) {
                        return  '<div style="text-align: center"><a class="button button-primary" href="' + row['updateUrl'] + '" target="_blank"><?php echo _('Update', 'wp-yun') ?></a>&nbsp;' +
                                '<a class="button button-secondary" href="' + data + '" target="_blank"><?php echo _('Statistics', 'wp-yun') ?></a></div>'
                        ;
                    },
                },
            ]
        });
    });
</script>