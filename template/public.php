<div style="margin-top:2em; margin-bottom: 0.5em">
    <?php echo __('Short Link', 'wp-yun') ?>:
    <a href="https://<?php echo $shortUrl ?>"><?php echo $shortUrl ?></a>
    <div style="height: 0.5em"></div>

    <a target="_blank" href="https://twitter.com/intent/tweet?text=<?php echo urlencode($shortUrl) ?>"><img
        style="display: inline; width: 32px"
        src="<?php echo WPYUN_URL . '/images/icons/twitter.png' ?>"
        title="<?php echo __('Tweet this link', 'wp-yun') ?>" alt="<?php echo $title ?>"/></a>

    <a target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($shortUrl) ?>"><img
        style="display: inline; width: 32px"
        src="<?php echo WPYUN_URL . '/images/icons/facebook.png' ?>"
        title="<?php echo __('Share on Facebook', 'wp-yun') ?>" alt="<?php echo $title ?>"/></a>

    <a target="_blank" href="https://web.whatsapp.com/send?text=<?php echo urlencode($shortUrl) ?>"><img
        style="display: inline; width: 32px"
        src="<?php echo WPYUN_URL . '/images/icons/whatsapp.png' ?>"
        title="<?php echo __('Share on Whatsapp', 'wp-yun') ?>" alt="<?php echo $title ?>"/></a>

    <a target="_blank" href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode("https://{$shortUrl}") ?>&title=<?php echo $title ?>"><img
        style="display: inline; width: 32px"
        src="<?php echo WPYUN_URL . '/images/icons/linkedin.png' ?>"
        title="<?php echo __('Share on Linked.in', 'wp-yun') ?>" alt="<?php echo $title ?>"/></a>

    <a target="_blank" href="https://t.me/share/url?url=<?php echo urlencode($shortUrl) ?>&text=<?php echo $title ?>"><img
        style="display: inline; width: 32px"
        src="<?php echo WPYUN_URL . '/images/icons/telegram.png' ?>"
        title="<?php echo __('Share on Telegram', 'wp-yun') ?>" alt="<?php echo $title ?>"/></a>
</div>