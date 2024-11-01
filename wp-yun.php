<?php
/**
 * WP Yun
 * This plugin can be used to generate short links for your websites posts, pages, and custom post types.
 * Extremely lightweight and easy to set up, give it your Yun token and go!
 *
 * @package   wp-yun
 * @author    Saeed Vasheghani Farahani <info@yun.ir>
 * @license   GPL-2.0+
 * @link      https://wordpress.org/plugins/wp-yun
 * @copyright 2020 Yun
 * @wordpress-plugin
 *            Plugin Name:       WP Yun
 *            Plugin URI:        https://wordpress.org/plugins/wp-bitly
 *            Description:       WP Yun can be used to generate short links for your websites posts, pages, and custom post types. Extremely lightweight and easy to set up, give it your Yun oAuth token and go!
 *            Version:           1.1.1
 *            Author:            <a href="https://profiles.wordpress.org/wpyun/">Saeed Vasheghani Farahani</a>
 *            Text Domain:       wp-yun
 *            License:           GPL-2.0+
 *            License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *            Domain Path:       /languages
 */


if (!defined('WPINC')) {
    die;
}

define('WPYUN_VERSION', '1.1.1');

define('WPYUN_DIR', WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)));
define('WPYUN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
define('WPYUN_CONFIG', 'admin.php?page=wp-yun-options');
define('WPYUN_META_KEY', '_wpyun');

define('WPYUN_ERROR', __('WP Yun Error: No such option %1$s', 'wp-yun'));

define('WPYUN_API', 'https://yun.ir');
define('WPYUN_API_URL', '/api/v1/urls');
define('WPYUN_API_USER_CONFIG', '/api/v1/users/config');

/**
 * The primary controller class for everything wonderful that WP Yun does.
 * We're not sure entirely what that means yet; if you figure it out, please
 * let us know and we'll say something snazzy about it here.
 *
 * @TODO    : Update the class phpdoc description to say something snazzy.
 * @package wp-yun
 * @author  Saeed Vasheghani Farahani <info@yun.ir>
 */
final class WP_Yun
{
    private static $_instance;

    private $_options = [];

    public static function get_in()
    {
        if (null === self::$_instance) {
            self::$_instance = new self;
            self::$_instance->populate_options();
            self::$_instance->include_files();
            self::$_instance->action_filters();
        }

        return self::$_instance;
    }

    public function populate_options()
    {
        $defaults = apply_filters('wp_yun_default_options', [
            'version' => WPYUN_VERSION,
            'token' => '',
            'post_types' => ['post', 'page', 'attachment'],
            'authorized' => false,
            'share_buttons' => false,
        ]);
        $this->_options = wp_parse_args(get_option('wp-yun-options'), $defaults);
    }

    public function get_option($option)
    {
        if (!isset($this->_options[$option])) {
            trigger_error(sprintf(WPYUN_ERROR, ' <code>' . $option . '</code>'), E_USER_ERROR);
        }

        return $this->_options[$option];
    }

    public function set_option($option, $value)
    {
        if (!isset($this->_options[$option])) {
            trigger_error(sprintf(WPYUN_ERROR, ' <code>' . $option . '</code>'), E_USER_ERROR);
        }

        $this->_options[$option] = $value;
    }

    public function include_files()
    {
        require_once(WPYUN_DIR . '/includes/functions.php');
        if (is_admin()) {
            require_once(WPYUN_DIR . '/includes/admin.php');
        }
    }

    public function action_filters()
    {
        add_action('init', function () {
            $languages = apply_filters('wp_yun_languages_dir', WPYUN_DIR . '/languages/');
            $locale = apply_filters('plugin_locale', get_locale(), 'wp-yun');
            $file = $languages . $locale . '.mo';

            if (file_exists($file)) {
                load_textdomain('wp-yun', $file);
            } else {
                load_plugin_textdomain('wp-yun', false, $languages);
            }
        });
        add_action('rest_api_init', function () {
            register_rest_route('wp-yun', '/shorted-urls', [
                'methods' => 'GET',
                'callback' => '_wp_yun_handle_get_all',
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                }
            ]);
            function _wp_yun_handle_get_all($data)
            {
                global $wpdb;
                $list = $wpdb->get_results(sprintf("SELECT * FROM `%s` WHERE  meta_key = '%s' ORDER BY `meta_id` DESC", $wpdb->postmeta, WPYUN_META_KEY), OBJECT);

                $result = [];
                foreach ($list as $item) {
                    $item = json_decode($item->meta_value);
                    $result[] = [
                        'shortUrl' => $item->doc->url,
                        'longUrl' => $item->doc->source->url,
                        'title' => $item->doc->source->title,
                        'hits' => $item->doc->hits,
                        'statsUrl' => $item->doc->statsUrl,
                        'updateUrl' => $item->doc->updateUrl,
                    ];
                }
                return [
                    'data' => $result
                ];
            }
        });

        add_filter('pre_get_shortlink', 'wp_yun_get_short_link', 10, 2);
        add_filter('the_content', function ($content) {

            $post = get_post();
            if ($post->ID <= 0) {
                return $content;
            }

            $wpYun = wpyun();
            if(!$wpYun->get_option('share_buttons')){
                return $content;
            }

            $shortUrl = wp_yun_get_shorted_link($post->ID);
            if (!isset($shortUrl['doc']['url'])) {
                return $content;
            }

            $title = get_the_title();
            $shortUrl = $shortUrl['doc']['url'];

            ob_start();
            include WPYUN_DIR . '/template/public.php';
            $content .= ob_get_contents();
            ob_end_clean();

            return $content;
        });

        add_shortcode('wpyun', 'wp_yun_short_link');
    }
}

function wpyun()
{
    return WP_Yun::get_in();
}

wpyun();
