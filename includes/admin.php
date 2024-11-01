<?php
/**
 * WP Yun Administration
 *
 * @package     wp-yun
 * @subpackage  admin
 * @author      Saeed Vasheghani Farahani <info@yun.ir>
 * @license     GPL-2.0+\
 * @since       1.0.0
 */

class WP_Yun_Admin
{
    protected static $_instance = null;

    public static function get_in()
    {
        if (!isset(self::$_instance) && !(self::$_instance instanceof WP_Yun_Admin)) {
            self::$_instance = new self;
            self::$_instance->action_filters();
        }

        return self::$_instance;
    }

    public function action_filters()
    {
        $wpYun = wpyun();

        add_action('admin_init', function () {
            register_setting('wp-yun', 'wp-yun-options', function ($input) {
                $input['authorized'] = false;
                $input['token'] = sanitize_text_field($input['token']);
                if ($input['token']) {
                    $response = wp_yun_request(WPYUN_API_USER_CONFIG, 'get', null, $input['token']);
                    $input['authorized'] = isset($response['config']) ? true : false;
                }
                if (!isset($input['post_types'])) {
                    $input['post_types'] = [];
                } else {
                    $post_types = apply_filters('wp_yun_allowed_post_types', get_post_types(['public' => true]));
                    foreach ($input['post_types'] as $key => $pt) {
                        if (!in_array($pt, $post_types)) {
                            unset($input['post_types'][$key]);
                        }
                    }
                }
                return $input;
            });
            add_settings_section('wp-yun-settings', __('WP Yun Options', 'wp-yun'), function () {
                echo apply_filters('wp_yun_settings_section', '<p>' . __('You will need a Yun account to use this plugin.', 'wp-yun') . '</p>');
            }, 'wp-yun');
            add_settings_field('token', '<label for="token">' . __('Yun Token', 'wp-yun') . '</label>', function () {
                $wpYun = wpyun();
                $style = $wpYun->get_option('authorized') ? '' : 'border-color: #c00; background-color: #ffecec;';
                echo '<input type="text" size="80" name="wp-yun-options[token]" value="' . esc_attr($wpYun->get_option('token')) . '" style="direction:ltr; ' . $style . '" />' . '<p class="description">' . __('To create a new token', 'wp-yun') . ' <a href="https://yun.ir/user/general/token/" target="_blank" style="text-decoration: none;"> ' . __('click here', 'wp-yun') . '</a></p>';
            }, 'wp-yun', 'wp-yun-settings');
            add_settings_field('post_types', '<label for="post_types">' . __('Post Types', 'wp-yun') . '</label>', function () {
                $wpYun = wpyun();

                $post_types = apply_filters('wp_yun_allowed_post_types', get_post_types(['public' => true]));
                $output = '<fieldset><legend class="screen-reader-text"><span>Post Types</span></legend>';

                $current_post_types = $wpYun->get_option('post_types');
                foreach ($post_types as $label) {
                    $output .= '<label><input type="checkbox" name="wp-yun-options[post_types][]" value="' . $label . '" ' . checked(in_array($label, $current_post_types), true, false) . '>' . __(sprintf('Type %s', ucfirst($label)), 'wp-yun') . '</label><br>';
                }

                $output .= '<p class="description">' . __('Automatically generate short links for the selected post types.', 'wp-yun') . '</p>' . '</fieldset>';
                echo $output;
            }, 'wp-yun', 'wp-yun-settings');
            add_settings_field('share_buttons', '<label for="share_buttons">' . __('Share Buttons', 'wp-yun') . '</label>', function () {
                $wpYun = wpyun();

                $output = '<fieldset><legend class="screen-reader-text"><span>Share Buttons</span></legend>';
                $output .= '<label><input type="checkbox" name="wp-yun-options[share_buttons]" value="1" ' . checked($wpYun->get_option('share_buttons'), true, false) . '>' . __('Show Share Buttons', 'wp-yun') . '</label><br>';
                $output .= '<p class="description">' . __('By activating this option, social media share buttons will be added to the posts public page.', 'wp-yun') . '</p>' . '</fieldset>';

                echo $output;
            }, 'wp-yun', 'wp-yun-settings');
        });
        add_action('admin_menu', function () {
            add_menu_page(__('WP Yun', 'wp-yun'), __('WP Yun', 'wp-yun'), 'administrator', 'wp-yun-options', function () {
                include WPYUN_DIR . '/template/settings.php';
            }, WPYUN_URL . '/images/icon.png');
            add_submenu_page('wp-yun-options', __('Settings', 'wp-yun'), __('Settings', 'wp-yun'), 'manage_options', 'wp-yun-options');
            add_submenu_page('wp-yun-options', __('Urls Archive', 'wp-yun'), __('Urls Archive', 'wp-yun'), 'administrator', 'wp-yun-options-shorted-urls', function () {
                include WPYUN_DIR . '/template/archive.php';
            });
        });
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_script('wp-yun-table', 'https://cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js', array('jquery'));
            wp_enqueue_style('wp-yun-table', 'https://cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css');
        });
        add_action('save_post', 'wp_yun_generate_short_link');

        if (!$wpYun->get_option('token')) {
            add_action('admin_notices', function () {
                $screen = get_current_screen();
                if ($screen->base != 'plugins') {
                    return;
                }

                $prologue = __('WP Yun is almost ready!', 'wp-yun');
                $link = '<a href="' . WPYUN_CONFIG . '">' . __('settings page', 'wp-yun') . '</a>';
                $epilogue = sprintf(__('Please visit the %s to configure WP Yun', 'wp-yun'), $link);

                $message = apply_filters('wp_yun_setup_notice', '<div id="message" class="updated"><p>' . $prologue . ' ' . $epilogue . '</p></div>');

                echo $message;

            });
        }

        $post_types = $wpYun->get_option('post_types');
        if (is_array($post_types)) {
            foreach ($post_types as $post_type) {
                add_action('add_meta_boxes_' . $post_type, function ($post) {
                    $shortUrl = wp_yun_get_shorted_link($post->ID);
                    if (!$shortUrl) {
                        return;
                    }
                    add_meta_box('wpyun-meta', __('WP Yun', 'wp-yun'), function ($post, $args) {
                        $wpYun = wpyun();
                        $authorized = $wpYun->get_option('authorized');
                        echo '<label class="screen-reader-text">' . __('Yun Statistics', 'wp-yun') . '</label>';
                        if (!$authorized) {
                            echo '<p class="error">' . __('Please activate plugin', 'wp-yun') . ': <a href="' . WPYUN_CONFIG . '">' . __('Activation', 'wp-yun') . '</a></p>';
                        } else {
                            $response = wp_yun_request(WPYUN_API_URL . '/' . $args['args'][0]['doc']['id']);
                            if (isset($response['doc'])) {
                                echo '<div style="text-align: center"><br>';
                                echo '<p style="direction: ltr;"><a href="https://' . $response['doc']['url'] . '" target="_blank"><b>' . $response['doc']['url'] . '</b></a></p>';
                                echo '<p><strong>' . (int)$response['doc']['hits'] . ' ' . __('Hit(s)', 'wp-yun') . '</strong></p>';
                                echo '<p>(<a href="' . $response['doc']['updateUrl'] . '" target="_blank">' . __('Settings', 'wp-yun') . '</a> - <a href="' . $response['doc']['statsUrl'] . '" target="_blank">' . __('Statistics', 'wp-yun') . '</a>)</p>';
                                echo '</div>';
                            } else {
                                if (isset($response['errors']['message'])) {
                                    echo '<p class="error">' . $response['errors']['message'] . '</p>';
                                }
                            }
                        }
                    }, $post->post_type, 'side', 'high', [$shortUrl]);
                }, 1);
            }
        }

        add_filter('plugin_action_links', function ($links, $file) {
            if (strpos($file, 'wp-yun.php') !== false) {
                $links = array_merge($links, [
                    '<a href="' . admin_url(WPYUN_CONFIG) . '">' . __('Settings', 'wp-yun') . '</a>'
                ]);
            }
            return $links;
        }, 10, 2);
        add_filter('plugin_row_meta', function ($links, $file) {
            if (strpos($file, 'wp-yun.php') !== false) {
                $links = array_merge($links, [
                    '<a href="https://yun.ir/user/general/token/" target="_blank">' . __('Create token', 'wp-yun') . '</a>',
                ]);
            }
            return $links;
        }, 10, 2);
        add_filter('post_row_actions', function ($actions, $post) {
            if ($shortUrl = wp_yun_get_shorted_link($post->ID)) {
                $actions['shorlink'] = sprintf('<a href="%1$s">%2$s</a>',
                    'https://' . $shortUrl['doc']['url'],
                    __('Short Link', 'wp-yun')
                );
            }
            return $actions;
        }, 10, 2);
    }
}

WP_Yun_Admin::get_in();
