<?php

/**
 * WP Shortcm Administration
 *
 * @package     wp-shortcm
 * @subpackage  admin
 * @author      Mark Waterous <mark@watero.us
 * @license     GPL-2.0+\
 * @since       2.0
 */

/**
 * Class WP_Shortcm_Admin
 * This handles everything we do on the dashboard side.
 *
 * @since 2.0
 */
class WP_Shortcm_Admin {

    /**
     * @var $_instance An instance of ones own instance
     */
    protected static $_instance = null;


    /**
     * This creates and returns a single instance of WP_Shortcm_Admin
     *
     * @since   2.0
     * @static
     * @uses    WP_Shortcm_Admin::action_filters() To set up any necessary WordPress hooks.
     * @return  WP_Shortcm_Admin
     */
    public static function get_in() {

        if (!isset(self::$_instance) && !(self::$_instance instanceof WP_Shortcm_Admin)) {
            self::$_instance = new self;
            self::$_instance->action_filters();
        }

        return self::$_instance;
    }


    /**
     * Hook any necessary WordPress actions or filters that we'll be needing for the admin.
     *
     * @since   2.0
     * @uses    wpbitly()
     */
    public function action_filters() {

        $wpbitly = shortcm();
        $token = $wpbitly->get_option('oauth_token');

        add_action('admin_init', array($this, 'register_settings'));
        add_action("admin_menu", array($this,"shortlist_io"));
        add_action('admin_enqueue_scripts', array($this,"wpdocs_enqueue_custom_shortcm_style"));
        add_action('admin_footer', array($this,"shrt_link_table_js"));

        if (empty($token))
            add_action('admin_notices', array($this, 'display_notice'));


        $post_types = $wpbitly->get_option('post_types');

        if (is_array($post_types)) {
            foreach ($post_types as $post_type) {
                add_action('add_meta_boxes_' . $post_type, array($this, 'add_metaboxes_yo'));
            }
        }

    }


    /**
     * Display a simple and unobtrusive notice on the plugins page after activation (and
     * up until they add their oauth_token).
     *
     * @since   2.0
     */
    public function display_notice() {

        $screen = get_current_screen();

        if ($screen->base != 'plugins')
            return;


        $prologue = __('WP Short.cm is almost ready!', 'wp-shortcm');
        $link = '<a href="options-writing.php">' . __('settings page', 'wp-shortcm') . '</a>';
        $epilogue = sprintf(__('Please visit the %s to configure WP Short.cm', 'wp-shortcm'), $link);

        $message = apply_filters('wpbitly_setup_notice', '<div id="message" class="updated"><p>' . $prologue . ' ' . $epilogue . '</p></div>');

        echo $message;

    }


    /**
     * Add our options array to the WordPress whitelist, append them to the existing Writing
     * options page, and handle all the callbacks.
     *
     * @since   2.0
     */
    public function register_settings() {

        register_setting('writing', 'shortcm-options', array($this, 'validate_settings'));

        add_settings_section('wpshortcm_settings', 'WP Shortcm Options', '_f_settings_section', 'writing');
        /**
         * @ignore
         */
        function _f_settings_section() {
            echo apply_filters('wpshortcm_settings_section', '<p>' . __('You will need a short.cm account to use this plugin. Click the link below for your secret key, and if necessary create a new account.', 'wp-shortcm') . '</p>');
        }
        add_settings_field('oauth_token', '<label for="oauth_token">' . __('Short.io Secret Key', 'wp-shortcm') . '</label>', '_f_settings_field_oauth', 'writing', 'wpshortcm_settings');
        add_settings_field('domain', '<label for="domain">' . __('Select domain', 'wp-shortcm') . '</label>', '_f_settings_field_domain', 'writing', 'wpshortcm_settings');
        add_settings_field('linkType', '<label for="domain_linkType">'.__('Slug Type' , 'linkType' ).'</label>' , '_f_settings_field_linkType', 'writing','wpshortcm_settings');
        add_settings_field('caseSensitive', '<label for="domain_caseSensitive">'.__('Case Sensitive' , 'caseSensitive' ).'</label>' , '_f_settings_field_caseSensitive', 'writing','wpshortcm_settings');
        add_settings_field('hideRefere', '<label for="domain_hideRefere">'.__('Referrer ' , 'hideRefere' ).'</label>' , '_f_settings_field_hideRefere', 'writing','wpshortcm_settings');
        add_settings_field('redirect404', '<label for="domain_redirect404">'.__('404 Redirect ' , 'redirect404' ).'</label>' , '_f_settings_field_redirect404', 'writing','wpshortcm_settings');
       // add_settings_field('rootredirect', '<label for="domain_rootredirect">'.__('root redirect ' , 'rootredirect' ).'</label>' , '_f_settings_field_rootredirect', 'writing','wpshortcm_settings');
        add_settings_field('purgeExpiredLinks', '<label for="domain_purgeExpiredLinks">'.__('Auto-delete expired links ' , 'purgeExpiredLinks' ).'</label>' , '_f_settings_field_purgeExpiredLinks', 'writing','wpshortcm_settings');
        add_settings_field('robots', '<label for="domain_robots">'.__('Search Engine Policy ' , 'robots' ).'</label>' , '_f_settings_field_robots', 'writing','wpshortcm_settings');
        add_settings_field('httpsLinks', '<label for="domain_httpsLinks">'.__('HTTPS Links ' , 'httpsLinks' ).'</label>' , '_f_settings_field_httpsLinks', 'writing','wpshortcm_settings');
        add_settings_field('httpsLevel', '<label for="domain_httpsLevel">'.__('HTTPS Redirect Policy ' , 'httpsLevel' ).'</label>' , '_f_settings_field_httpsLevel', 'writing','wpshortcm_settings');
        
        /**
         * @ignore
         */


     function domain_id(){
            $wpbitly = shortcm();
            $domain=$wpbitly->get_option("domain");
    
            $secret_key=$wpbitly->get_option("oauth_token");
            $domain_id='';
            $curl = curl_init();
                    
            curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.short.io/api/domains",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: $secret_key"
            ],
            ]);
    
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $domains = json_decode($response);
            $domain_id = '';
            foreach($domains as $key => $value){
                if($domain == $value->hostname){
                    $domain_id = $value->id;
                }
            }
            return $domain_id;
            
        }
     

function get_api_response(){
    $wpbitly = shortcm();
    $domian_id = domain_id();
    $secret_key=$wpbitly->get_option("oauth_token");
    $url = sprintf(shortcm_backend_api('domains/'.$domian_id), $secret_key);
    $response = shortcm_request('GET', $url, $secret_key);
   
    return $response;
}


        function _f_settings_field_oauth() {

            $wpshortcm = shortcm();
            $url = apply_filters('wpshortcm_oauth_url', 'https://app.short.cm/users/connected_apps');
            $auth_css = $wpshortcm->get_option('authorized') ? '' : ' style="border-color: #c00; background-color: #ffecec;" ';
            $output = '<input type="text" size="80" name="shortcm-options[oauth_token]" value="' . esc_attr($wpshortcm->get_option('oauth_token')) . '"' . $auth_css . ' />' . '<p class="description">' . __('Please provide your', 'wp-shortcm') . ' <a href="' . $url . '" target="_blank" style="text-decoration: none;"> ' . __('Secret Key', 'wp-shortcm') . '</a></p>';
            echo $output;

        }


        function _f_settings_field_domain() {
            $wpbitly = shortcm();
            $url = apply_filters('wpshortcm_oauth_url', 'https://short.cm/');
            $auth_css = $wpbitly->get_option('authorized') ? '' : ' style="border-color: #c00; background-color: #ffecec;" ';
            $output = '<input type="text" size="80" name="shortcm-options[domain]" value="' . esc_attr($wpbitly->get_option('domain')) . '"' . $auth_css . ' />' . '<p class="description">' . __('Please provide your', 'wp-shortcm') . ' <a href="' . $url . '" target="_blank" style="text-decoration: none;"> ' . __('Domain name', 'wp-shortcm') . '</a></p>';
            echo $output;

        }

        function _f_settings_field_linkType() { 
            $api_respone =  get_api_response();
            $linkType = $api_respone['linkType'];
            $random = $linkType == 'random'?"selected":"";
            $increment = $linkType == 'increment'?"selected":"";
            $secure = $linkType == 'secure'?"selected":"";
            echo '<select name="shortcm-options[linkType]" id="linkType">
                     <option ' .$random. '>random</option>
                      <option '.$increment.'>increment</option>
                      <option '.$secure.'>secure</option>
             </select>'. '<p class="description">' . __('Please choose links ', 'wp-shortcm') . '</p>' ;
        }
        function _f_settings_field_caseSensitive(){
        $api_respone =  get_api_response();
        $caseSensitive = $api_respone['caseSensitive'];
        $true = $caseSensitive == '1'?"selected":"";
        $false = $caseSensitive == '0'?"selected":"";
        echo '<select name="shortcm-options[caseSensitive]" id="caseSensitive">
                     <option value=""></option>
                      <option ' .$true. '>true</option>
                      <option ' .$false. '>false</option>
             </select>'. '<p class="description">' . __('Please choose links on this domain should by case-sensitive', 'wp-shortcm') . '</p>' ;
       }
       function  _f_settings_field_hideRefere(){
        $api_respone =  get_api_response();
        $hideReferer = $api_respone['hideReferer'];
        $true = $hideReferer == '1'? 'selected' : '';
        $false = $hideReferer == '0'? 'selected' : '';
        echo '<select name="shortcm-options[hideReferer]" id="hideReferer">
                      <option '.$true.'>true</option>
                      <option '.$false.'>false</option>
            </select>'. '<p class="description">' . __('Please choose links on this domain should by case-sensitive', 'wp-shortcm') . '</p>' ;
       }
        function  _f_settings_field_redirect404(){
        $wpbitly = shortcm();
        $api_respone =  get_api_response();
        $redirect404 = $api_respone['redirect404'];
        //$auth_css = $wpbitly->get_option('authorized') ? '' : ' style="border-color: #c00; background-color: #ffecec;" ';
        echo '<input type="text" size="80" id="redirect404" name="shortcm-options[redirect404]" value="'.$redirect404 .'"' . $auth_css . '/>'
               . '<p class="description">' . __('Please provide link to redirect customers on non-existing short link', 'wp-shortcm') . '</p>';
       }
    //     function  _f_settings_field_rootredirect(){
    //      $wpbitly = shortcm();
    //      $rootredirect = $wpbitly->get_option('rootredirect');
    //      $auth_css = $wpbitly->get_option('authorized') ? '' : ' style="border-color: #c00; background-color: #ffecec;" ';
    //      echo '<input type="text" size="80" id="rootredirect" name="shortcm-options[rootredirect]" value="'.$rootredirect.'"' . $auth_css . '/>'
    //            . '<p class="description">' . __('Please provide link to edirect customers on main page visit', 'wp-shortcm') . '</p>';
    //    }
       function _f_settings_field_purgeExpiredLinks(){
        $wpbitly = shortcm();
        $api_respone =  get_api_response();
        $purgeExpiredLinks = $api_respone['purgeExpiredLinks'];
        $true = $purgeExpiredLinks == '1'? 'selected' : '';
        $false = $purgeExpiredLinks == '0'? 'selected' : '';
        echo '<select name="shortcm-options[purgeExpiredLinks]" id="purgeExpiredLinks">
                      <option '.$true.'>true</option>
                      <option '.$false.'>false</option>
            </select>'. '<p class="description">' . __('Please choose option', 'wp-shortcm') . '</p>' ;

       }
       function _f_settings_field_robots(){
        $wpbitly = shortcm();
   
        $api_respone =  get_api_response();
        $robots = $api_respone['robots'];
        $allow = $robots == 'allow'? 'selected' : '';
        $disallow = $robots == 'disallow'? 'selected' : '';
        $noindex = $robots == 'noindex'? 'selected' : '';
        echo '<select name="shortcm-options[robots]" id="robots">
                      <option '.$allow.'>allow</option>
                      <option '.$disallow.'>disallow</option>
                      <option '.$noindex.'>noindex</option>
            </select>'. '<p class="description">' . __('Please choose option', 'wp-shortcm') . '</p>' ;
       }
       function _f_settings_field_httpsLinks(){
        $api_respone = get_api_response();
        $httpsLinks = $api_respone['httpsLinks'];
        $true = $httpsLinks == '1'? 'selected' : '';
        $false = $httpsLinks == '0'? 'selected' : '';
        echo '<select name="shortcm-options[httpsLinks]" id="httpsLinks">
                      <option '.$true.'>true</option>
                      <option '.$false.'>false</option>
            </select>'. '<p class="description">' . __('Please choose option', 'wp-shortcm') . '</p>' ;
       }
       function _f_settings_field_httpsLevel(){
        $api_respone = get_api_response();
        $httpsLevel = $api_respone['httpsLevel'];
        $none = $httpsLevel == 'none'? 'selected' : '';
        $redirect = $httpsLevel == 'redirect'? 'selected' : '';
        $hsts = $httpsLevel == 'hsts'? 'selected' : '';
        echo '<select name="shortcm-options[httpsLevel]" id="httpsLevel">
                      <option '.$none.'>none</option>
                      <option '.$redirect.'>redirect</option>
                      <option '.$hsts.'>hsts</option>
            </select>'. '<p class="description">' . __('Please choose option', 'wp-shortcm') . '</p>' ;

       }



        add_settings_field('post_types', '<label for="post_types">' . __('Post Types', 'wp-shortcm') . '</label>', '_f_settings_field_post_types', 'writing', 'wpshortcm_settings');
        /**
         * @ignore
         */
        function _f_settings_field_post_types() {
            $wpshortcm = shortcm();
            $post_types = apply_filters('shortcm_allowed_post_types', get_post_types(array('public' => true)));
            $output = '<fieldset><legend class="screen-reader-text"><span>Post Types</span></legend>';
            $current_post_types = $wpshortcm->get_option('post_types');
            foreach ($post_types as $label) {
                $output .= '<label for "' . $label . '>' . '<input type="checkbox" name="shortcm-options[post_types][]" value="' . $label . '" ' . checked(in_array($label, $current_post_types), true, false) . '>' . $label . '</label><br>';
            }
            $output .= '<p class="description">' . __('Automatically generate shortlinks for the selected post types.', 'wp-shortcm') . '</p>' . '</fieldset>';
            echo $output;
        }

        add_settings_field('debug', '<label for="debug">' . __('Debug WP Shortcm', 'wp-shortcm') . '</label>', '_f_settings_field_debug', 'writing', 'wpshortcm_settings');
        /**
         * @ignore
         */
        function _f_settings_field_debug() {

            $wpbitly = shortcm();

            $output = '<fieldset><legend class="screen-reader-text"><span>Debug WP Shortcm</span></legend>' . '<label title="debug"><input type="checkbox" id="debug" name="shortcm-options[debug]" value="1" ' . checked($wpbitly->get_option('debug'), 1, 0) . '><span> ' . __("Let's debug!", 'wpbitly') . '</span></label><br>' . '<p class="description">' . __("If you're having issues generating shortlinks, turn this on and create a thread in the", 'wpbitly') . ' ' . '<a href="http://wordpress.org/support/plugin/wp-shortcm" title="' . __('WP Shortio support forums on WordPress.org', 'wpbitly') . '">' . __('support forums', 'wpbitly') . '</a>.</p>' . '</fieldset>';

            echo $output;

        }

    }

  
    function shortlist_io() {
      add_submenu_page(
            'options-general.php',
            'shortlist_io API',
            'Short.io links',
            'administrator',
            'shortlist_io',
            array($this,'shortlist_io_settings_page') );
    }
    
    public function shortlist_io_settings_page(){ 
        set_time_limit(0);
        $wpbitly = shortcm();
        $domain=$wpbitly->get_option("domain");

        $secret_key=$wpbitly->get_option("oauth_token");
        if(empty($domain) || empty($secret_key)){
            echo "Missing Credential";
            exit();
        }
        $domain_id='';
        $curl = curl_init();
                
        curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.short.io/api/domains",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: $secret_key"
        ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $domains = json_decode($response);

        foreach($domains as $each_domain){

            if($each_domain->hostname==$domain){
                $domain_id=$each_domain->id;
            }
        }
        curl_close($curl);
        if(empty($domain_id)){
            echo "Domain Id missing, Please check the domain name or credential";
            exit();
        }
    
            $link_info=array();                                                                                                                                                       
                $curl = curl_init();
                
                curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.short.io/api/links?domain_id=$domain_id&limit=150",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "Authorization: $secret_key"
                ],
                ]);
                
                $response = curl_exec($curl);

                $err = curl_error($curl);
                
                curl_close($curl);
                
                $link_list = json_decode($response);
                $link_listid = json_decode($response,true);
                $idstring = implode(',',array_column($link_listid['links'],'idString'));
                
                $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => "https://api-v2.short.cm/statistics/domain/$domain_id/link_clicks?ids=$idstring",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 300,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_HTTPHEADER => [
                            "Accept: application/json",
                            "Authorization: $secret_key"
                        ],
                        ]);
                        $response = curl_exec($curl);
                        $clicks = json_decode($response);
                        curl_close($curl);
            

                
              
             
                foreach($link_list->links as $link){
                    if($link->DomainId == $domain_id){
                         $idString=$link->idString;
                         $photoURL = $link->User->photoURL;
                         $createdAt = $link->createdAt;
                         $shortURL = $link->shortURL;
                         $originalURL = $link->originalURL;
                    }
                    $link_info[]=array('by'=>$photoURL,'date'=>$createdAt,'short_link'=>$shortURL,'original_link'=>$originalURL,'clicks'=>$clicks->$idString);
                      
                }

                            
           
              echo '<div class="shortlink_div" style="padding-right:20px;">';
              echo '<h2> Links of domain: '.$domain.'</h2>';
                echo '<table id="shortlink_listing" class="display" style="width:100%">';
                echo ' <thead>
                <tr>
                    <th>By</th>
                    <th>Date</th>
                    <th>ShortLink</th>
                    <th>Original</th>
                    <th>Clicks</th>
                </tr>
            </thead><tbody>';
            foreach($link_info as $info){
                echo "<tr>
                <td><img src='".$info['by']."' width='50px'></td>
                <td>".date_format(date_create($info['date']),'Y/m/d')."</td>
                <td>".$info['short_link']."</td>
                <td>".$info['original_link']."</td>
                <td>".$info['clicks']."</td>
            </tr>";
            }
            echo '</tbody></table></div>';
             


     } 
    

     function wpdocs_enqueue_custom_shortcm_style() {
        wp_enqueue_style('boot_css', SHORTCM_URL . '/assets/jquery.dataTables.min.css');
        wp_enqueue_script('boot_js', SHORTCM_URL . '/assets/jquery.dataTables.min.js');
    }
     function shrt_link_table_js(){ ?>
        <script type="text/javascript">
            jQuery(document).ready(function () {
                jQuery('#shortlink_listing').DataTable();
            });
        </script>
    <?php }
    
    /**
     * Validate user settings. This will also authorize their OAuth token if it has
     * changed.
     *
     * @since   2.0
     * @uses    wpshortcm()
     *
     * @param   array $input WordPress sanitized data array
     *
     * @return  array           WP Bit.ly sanitized data
     */
    public function validate_settings($input) {
        $wpbitly = shortcm();
        $domain=$wpbitly->get_option("domain");
        
        $input['debug'] = ('1' == $input['debug']) ? true : false;
        $input['oauth_token'] = sanitize_text_field($input['oauth_token']);
        $domain_data = [];
        $domain_data['linkType'] = $input['linkType'];
        $domain_data['caseSensitive'] = $input['caseSensitive'];
        $domain_data['hideReferer'] = $input['hideReferer'];
        $domain_data['redirect404'] = $input['redirect404'];
       // $domain_data['rootredirect'] = $input['rootredirect'];
        $domain_data['purgeExpiredLinks'] = $input['purgeExpiredLinks'];
        $domain_data['robots'] = $input['robots'];
        $domain_data['httpsLinks'] = $input['httpsLinks'];
        $domain_data['httpsLevel'] = $input['httpsLevel'];
        $url = sprintf(shortcm_api('api/domains'), $input['oauth_token']);
        $response = shortcm_request('GET', $url, $input['oauth_token']);
        $domains_data = $this->find_domain_data($response,$wpbitly->get_option("domain"));
      
        shortcm_debug_log($response, 'Validate OAuth', $input['debug']);
        $input['authorized'] = (isset($response[$domains_data]['id'])) ? true : false;
        if($input['authorized']){
            $updateurl = sprintf(shortcm_backend_api('domains/settings/'.$response[$domains_data]['id']), $input['oauth_token']);
            $updateurlDOmain = shortcm_request('POST', $updateurl, $input['oauth_token'],$domain_data);
            shortcm_debug_log($updateurlDOmain, 'Update Domain', $input['debug']);
        }
      
       
        if (!isset($input['post_types'])) {
            $input['post_types'] = array();
        } else {
            $post_types = apply_filters('shortcm_allowed_post_types', get_post_types(array('public' => true)));

            foreach ($input['post_types'] as $key => $pt) {
                if (!in_array($pt, $post_types))
                    unset($input['post_types'][ $key ]);
            }

        }

        return $input;

    }

    public function find_domain_data($data , $domain){
      
      
       $domain_name = array_flip(array_column($data,'hostname'));
       
       return $domain_name[$domain];
    }
  
   

    /**
     * Add a fun little statistics metabox to any posts/pages that WP Bit.ly
     * generates a link for. There's potential here to include more information.
     *
     * @since   2.0
     * @TODO    Should the user can turn this on or off? You heard me.
     *
     * @param   object $post The post object passed by WordPress
     */
    public function add_metaboxes_yo($post) {

        $shortlink = get_post_meta($post->ID, '_shortcm', true);
        if (!$shortlink)
            return;

        /*add_meta_box('wpbitly-meta', 'WP Bit.ly', array(
                $this,
                'display_metabox'
            ), $post->post_type, 'side', 'default', array($shortlink));*/
    }


    /**
     * Handles the display of the metabox.
     *
     * @since   2.0
     *
     * @param   object $post WordPress passed $post object
     * @param   array  $args Passed by our call to add_meta_box(), just the $shortlink in this case.
     */
    public function display_metabox($post, $args) {

        $wpshortcm = shortcm();
        $shortlink = $args['args'][0];


        // Look for a clicks response
        /*$url = sprintf(wpbitly_api('link/clicks'), $wpshortcm->get_option('oauth_token'), $shortlink);
        $response = wpbitly_get($url);

        if (is_array($response))*/
            $clicks = 0;


        // Look for referring domains metadata
        /*$url = sprintf(wpbitly_api('link/refer'), $wpshortcm->get_option('oauth_token'), $shortlink);
        $response = wpbitly_get($url);

        if (is_array($response))*/
            $refer = 'http://short.cm/';


        echo '<label class="screen-reader-text" for="new-tag-post_tag">' . __('Short.cm Statistics', 'wp-shortcm') . '</label>';

        if (isset($clicks) && isset($refer)) {

            echo '<p>' . __('Global click through:', 'wp-shortcm') . ' <strong>' . $clicks . '</strong></p>';

            if (!empty($refer)) {
                echo '<h4 style="padding-bottom: 3px; border-bottom: 4px solid #eee;">' . __('Your link was shared on', 'wp-shortcm') . '</h4>';
                foreach ($refer as $domain) {
                    if (isset($domain['url'])) {
                        printf('<a href="%1$s" target="_blank" title="%2$s">%2$s</a> (%3$d)<br>', $domain['url'], $domain['domain'], $domain['clicks']);
                    } else {
                        printf('<strong>%1$s</strong> (%2$d)<br>', $domain['domain'], $domain['clicks']);
                    }
                }
            }

        } else {
            echo '<p class="error">' . __('There was a problem retrieving information about your link. There may be no statistics yet.', 'wp-shortcm') . '</p>';
        }

    }

}

// Get... in!
WP_Shortcm_Admin::get_in();
