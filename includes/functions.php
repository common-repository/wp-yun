<?php

function wp_yun_get_shorted_link($post_id)
{
    if ($meta = get_post_meta($post_id, WPYUN_META_KEY, true)) {
        return json_decode($meta, 1);
    }

    return null;
}

function wp_yun_request($url, $method = 'get', $body = null, $token = null)
{
    $response = wp_remote_request(WPYUN_API . $url, [
        'headers' => [
            'X-API-Key' => ($token ? $token : wpyun()->get_option('token')),
        ],
        'method' => strtoupper($method),
        'body' => $body
    ]);
    return json_decode(wp_remote_retrieve_body($response), true);
}

function wp_yun_generate_short_link($post_id)
{
    $wpYun = wpyun();

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    if (wp_is_post_revision($post_id))
        return;

    if (!$wpYun->get_option('authorized'))
        return;

    if (!in_array(get_post_type($post_id), $wpYun->get_option('post_types')) ||
        !in_array(get_post_status($post_id), array('publish', 'future', 'private'))) {
        return;
    }

    $title = get_the_title($post_id);
    $permalink = get_permalink($post_id);
    $shortLink = wp_yun_get_shorted_link($post_id);

    if (isset($shortLink['doc'])) {
        if ($shortLink['doc']['source']['url'] != $permalink) {
            $shortLink = wp_yun_request(WPYUN_API_URL . '/' . $shortLink['doc']['id'], 'put', [
                'title' => $title,
                'url' => $permalink
            ]);
            if (isset($shortLink['doc'])) {
                update_post_meta($post_id, WPYUN_META_KEY, json_encode($shortLink));
            }
        }
        if (isset($shortLink['doc'])) {
            return $shortLink['doc']['url'];
        }
    }

    $response = wp_yun_request(WPYUN_API_URL, 'post', [
        'title' => $title,
        'url' => $permalink
    ]);
    if (isset($response['doc'])) {
        update_post_meta($post_id, WPYUN_META_KEY, json_encode($response));
        return $response['doc']['url'];
    }
}

function wp_yun_get_short_link($original, $post_id)
{
    $wpYun = wpyun();

    // Verify this is a post we want to generate short links for
    if (!in_array(get_post_type($post_id), $wpYun->get_option('post_types'))) {
        return $original;
    }

    if (0 == $post_id) {
        $post = get_post();
        $post_id = $post->ID;
    }

    if (!$shortLink = wp_yun_get_shorted_link($post_id)) {
        $shortLink = wp_yun_generate_short_link($post_id);
    }

    return isset($shortLink['doc']['url']) ? $shortLink['doc']['url'] : $original;
}

function wp_yun_short_link($attrs = [])
{
    $post = get_post();
    $defaults = [
        'text' => '',
        'title' => '',
        'before' => '',
        'after' => '',
        'post_id' => $post->ID,
    ];

    extract(shortcode_atts($defaults, $attrs));

    $permalink = get_permalink($post_id);
    $shortLink = wp_yun_get_short_link($permalink, $post_id);

    if (empty($text)) {
        $text = $shortLink;
    }

    if (empty($title)) {
        $title = the_title_attribute(['echo' => false]);
    }

    $output = '';

    if (!empty($shortLink)) {
        $output = apply_filters('the_shortlink', '<a rel="shortlink" href="' . esc_url($shortLink) . '" title="' . $title . '">' . $text . '</a>', $shortLink, $text, $title);
        $output = $before . $output . $after;
    }

    return $output;
}
