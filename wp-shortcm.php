<?php
/**
 * Short.io
 * This plugin can be used to generate shortlinks for your websites posts, pages, and custom post types.
 * Extremely lightweight and easy to set up, give it your Bitly oAuth token and go!
 * ಠ_ಠ
 *
 * @package   wp-bitly
 * @author    Short.cm Team
 * @author    Mark Waterous <mark@watero.us>
 * @author    Chip Bennett
 * @license   GPL-2.0+
 * @link      http://wordpress.org/plugins/wp-shortcm
 * @copyright 2022 Short.cm Team 2014 Mark Waterous & Chip Bennett
 * @wordpress-plugin
 *            Plugin Name:       short.io
 *            Plugin URI:        http://wordpress.org/plugins/wp-shortcm
 *            Description:       WP Shortio can be used to generate shortlinks for your websites posts, pages, and custom post types. Extremely lightweight and easy to set up, give it your Short.io secret key and go!
 *            Version:           2.4.0
 *            Author:            <a href="https://short.cm/">Andrii Kostenko</a>
 *            Text Domain:       wp-shortcm
 *            License:           GPL-2.0+
 *            License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *            Domain Path:       /languages
 *            Plugin URI: https://bitbucket.org/shortcm/shortcm-wordpress
 */


if (!defined('WPINC'))
    die;



define('SHORTCM_VERSION', '2.3.3');

define('SHORTCM_DIR', WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)));
define('SHORTCM_URL', plugins_url() . '/' . basename(dirname(__FILE__)));

define('SHORTCM_LOG', SHORTCM_DIR . '/log/debug.txt');
define('SHORTCM_ERROR', __('WP Shortcm Error: No such option %1$s', 'wp-bitly'));

define('SHORTCM_API', 'https://api.short.cm');
define('SHORTCM_API_BACKEND', 'https://backend-2.short.io');






/**
 * The primary controller class for everything wonderful that WP Shortcm does.
 * We're not sure entirely what that means yet; if you figure it out, please
 * let us know and we'll say something snazzy about it here.
 *
 * @TODO    : Update the class phpdoc description to say something snazzy.
 * @package wp-bitly
 * @author  Mark Waterous <mark@watero.us>
 */
final class WP_Shortcm {

    /**
     * @var $_instance An instance of ones own instance
     */
    private static $_instance;

    /**
     * @var array The WP Shortcm configuration is stored in here
     */
    private $_options = array();


    /**
     * This creates and returns a single instance of WP_Shortcm.
     * If you haven't seen a singleton before, visit any Starbucks; they're the ones sitting on expensive laptops
     * in the corner drinking a macchiato and pretending to write a book. They'll always be singletons.
     *
     * @since   2.0
     * @static
     * @uses    WP_Shortcm::populate_options()     To create our options array.
     * @uses    WP_Shortcm::includes_files()       To do something that sounds a lot like what it sounds like.
     * @uses    WP_Shortcm::check_for_upgrade()    You run your updates, right?
     * @uses    WP_Shortcm::action_filters()       To set up any necessary WordPress hooks.
     * @return  WP_Shortcm
     */
    public static function get_in() {
        if (null === self::$_instance) {
            self::$_instance = new self;
            self::$_instance->populate_options();
            self::$_instance->include_files();
            self::$_instance->check_for_upgrade();
            self::$_instance->action_filters();
        }

        return self::$_instance;
    }


    /**
     * Populate WP_Shortcm::$options with the configuration settings stored in 'shortcm-options',
     * using an array of default settings as our fall back.
     *
     * @since 2.0
     */
    public function populate_options() {

        $defaults = apply_filters('shortcm_default_options', array(
            'version'     => SHORTCM_VERSION,
            'domain_id'   =>'',
            'oauth_token' => '',
            'domain'      => '',
            'post_types'  => array('post', 'page'),
            'authorized'  => false,
            'debug'       => false,
            'linkType'    => 'random',
            'caseSensitive'=> false,
            'hideReferer' => false,
            'redirect404' => '',
            'purgeExpiredLinks'=>'',
            'robots'=>'',
            'httpsLinks'=>'',
            'httpsLevel'=>','
        ));

        $this->_options = wp_parse_args(get_option('shortcm-options'), $defaults);

    }


    /**
     * Access to our WP_Shortcm::$_options array.
     *
     * @since 2.2.5
     *
     * @param  $option string The name of the option we need to retrieve
     *
     * @return         mixed  Returns the option
     */
    public function get_option($option) {
        if (!isset($this->_options[ $option ]))
            trigger_error(sprintf(SHORTCM_ERROR, ' <code>' . $option . '</code>'), E_USER_ERROR);

        return $this->_options[ $option ];
    }


    /**
     * Sets a single WP_Shortcm::$_options value on the fly
     *
     * @since 2.2.5
     *
     * @param $option string The name of the option we're setting
     * @param $value  mixed  The value, could be bool, string, array
     */
    public function set_option($option, $value) {
        if (!isset($this->_options[ $option ]))
            trigger_error(sprintf(SHORTCM_ERROR, ' <code>' . $option . '</code>'), E_USER_ERROR);

        $this->_options[ $option ] = $value;
    }


    /**
     * WP Shortcm is a pretty big plugin. Without this function, we'd probably include things
     * in the wrong order, or not at all, and cold wars would erupt all over the planet.
     *
     * @since   2.0
     */
    public function include_files() {
        require_once(SHORTCM_DIR . '/includes/functions.php');
        if (is_admin())
            require_once(SHORTCM_DIR . '/includes/class.wp-shortcm-admin.php');
    }


    /**
     * Simple wrapper for making sure everybody (who actually updates their plugins) is
     * current and that we don't just delete all their old data.
     *
     * @since   2.0
     */
    public function check_for_upgrade() {

        // We only have to upgrade if it's pre v2.0
        $upgrade_needed = get_option('shortcm_options');
        if ($upgrade_needed !== false) {

            if (isset($upgrade_needed['post_types']) && is_array($upgrade_needed['post_types'])) {
                $post_types = apply_filters('shortcm_allowed_post_types', get_post_types(array('public' => true)));

                foreach ($upgrade_needed['post_types'] as $key => $pt) {
                    if (!in_array($pt, $post_types))
                        unset($upgrade_needed['post_types'][ $key ]);
                }

                $this->set_option('post_types', $upgrade_needed['post_types']);
            }

            delete_option('shortcm_options');

        }

    }


    /**
     * Hook any necessary WordPress actions or filters that we'll be needing in order to make
     * the plugin work its magic. This method also registers our super amazing slice of shortcode.
     *
     * @since 2.0
     * @todo  Instead of arbitrarily deactivating the Jetpack module, it might be polite to ask.
     */
    public function action_filters() {

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));

        add_action('save_post', 'shortcm_generate_shortlink');
        add_filter('pre_get_shortlink', 'shortcm_get_shortlink', 10, 2);

        add_action('init', array($this, 'load_plugin_textdomain'));
        add_action( 'admin_bar_menu', 'wp_admin_bar_shortlink_menu', 90 );

        add_shortcode('shortcm', 'shortcm_shortlink');
        add_action('admin_footer','custom_js_copy');

        if (class_exists('Jetpack')) {

            add_filter('jetpack_get_available_modules', '_bad_wpme');
            function _bad_wpme($modules) {
                unset($modules['shortlinks']);

                return $modules;
            }

        }

    }







    /**
     * Add a settings link to the plugins page so people can figure out where we are.
     *
     * @since   2.0
     *
     * @param   $links An array returned by WordPress with our plugin action links
     *
     * @return  array The slightly modified 'rray.
     * 
     * 
     * 
     */
    public function add_action_links($links) {

        return array_merge(array('settings' => '<a href="' . admin_url('options-writing.php') . '">' . __('Settings', 'wp-shortcm') . '</a>'), $links);

    }


    /**
     * This would be much easier if we all spoke Esperanto (or Old Norse).
     *
     * @since   2.0
     */
    public function load_plugin_textdomain() {

        $languages = apply_filters('shortcm_languages_dir', SHORTCM_DIR . '/languages/');
        $locale = apply_filters('plugin_locale', get_locale(), 'wp-bitly');
        $mofile = $languages . $locale . '.mo';

        if (file_exists($mofile)) {
            load_textdomain('wp-bitly', $mofile);
        } else {
            load_plugin_textdomain('wp-bitly', false, $languages);
        }

    }

}






/**
 * Call this in place of WP_Shortcm::get_in()
 * It's shorthand.
 * Makes life easier.
 * In fact, the phpDocumentor block is bigger than the function itself.
 *
 * @return WP_Shortcm
 */
function shortcm() {
    return WP_Shortcm::get_in(); // in.
}


shortcm();



