<?php
/**
 * @package   wp-shortcm
 * @author    Andrii Kostenko <andrii@short.cm>
 * @license   GPL-2.0+
 */


/**
 * Write to a WP Short.cm debug log file
 *
 * @since 2.2.3
 *
 * @param   string $towrite The data we want to add to the logfile
 */
function shortcm_debug_log($towrite, $message, $bypass = true) {

    $shortcm = shortcm();

    if (!$shortcm->get_option('debug') || !$bypass)
        return;


    $log = fopen(SHORTCM_LOG, 'a');

    fwrite($log, '# [ ' . date('F j, Y, g:i a') . " ]\n");
    fwrite($log, '# [ ' . $message . " ]\n\n");
    // There was a reason I wanted to export vars, so despite suggestions I'm leaving this in at present.
    fwrite($log, (is_array($towrite) ? print_r($towrite, true) : var_export($towrite, 1)));
    fwrite($log, "\n\n\n");

    fclose($log);

}


/**
 * What better way to store our api access call endpoints? I'm sure there is one, but this works for me.
 *
 * @since 2.0
 *
 * @param   string $api_call Which endpoint do we need?
 *
 * @return  string           Returns the URL for our requested API endpoint
 */
function shortcm_api($api_call) {

    return SHORTCM_API . '/' . $api_call;
}

/**
 * What better way to store our api access call endpoints? I'm sure there is one, but this works for me.
 *
 * @since 2.0
 *
 * @param   string $api_call Which endpoint do we need?
 *
 * @return  string           Returns the URL for our requested backend API endpoint
 */
function shortcm_backend_api($api_call) {

    return SHORTCM_API_BACKEND . '/' . $api_call;
}


/**
 * WP Short.cm wrapper for wp_remote_get. Why have I been using cURL when WordPress already does this?
 * Thanks to Otto, who while teaching someone else how to do it right unwittingly taught me the right
 * way as well.
 *
 * @since   2.1
 *
 * @param   string $url The API endpoint we're contacting
 *
 * @return  bool|array      False on failure, array on success
 */

function shortcm_request($method, $url, $api_key, $body = null) {
    $the = wp_remote_request($url, array(
        'method' => $method,
        'timeout' => '30',
        'body' => $body,
        'headers' => array(
            'Authorization' => $api_key
        )
    ));
    //$the = wp_remote_get($url, array('timeout' => '30',));

    if (is_array($the) && '200' == $the['response']['code'])
        return json_decode($the['body'], true);
    return $the;
}


/**
 * Generates the shortlink for the post specified by $post_id.
 *
 * @since   0.1
 *
 * @param   int $post_id The post ID we need a shortlink for.
 *
 * @return  bool|string          Returns the shortlink on success.
 */

function shortcm_generate_shortlink($post_id) {

    $shortcm = shortcm();

    // Avoid creating shortlinks during an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    // or for revisions
    if (wp_is_post_revision($post_id))
        return;

    // Token hasn't been verified, bail
    if (!$shortcm->get_option('authorized'))
        return;

    // Verify this is a post we want to generate short links for
    if (!in_array(get_post_type($post_id), $shortcm->get_option('post_types')) ||
        !in_array(get_post_status($post_id), array('publish', 'future', 'private'))) {

        return;
    }


    // We made it this far? Let's get a shortlink
    $permalink = get_permalink($post_id);
    $shortlink = get_post_meta($post_id, '_shortcm', true);
    $token = $shortcm->get_option('oauth_token');
    
    if (!empty($shortlink)) {
        if (strstr($permalink, $shortcm->get_option('domain')))
            return $shortlink;
    }

    $url = shortcm_api('links');
    $response = shortcm_request('POST', $url, $token, array(
        'originalURL' => $permalink,
        'domain' =>  $shortcm->get_option('domain')
    ));
    $shortlink = $response['shortURL'];

    shortcm_debug_log($response, '/shorten/');

    if (is_array($response)) {
        update_post_meta($post_id, '_shortcm', $shortlink);
    }
    return $shortlink;
}


/**
 * Short circuits the `pre_get_shortlink` filter.
 *
 * 
 * @since   0.1
 *
 * @param   bool $shortlink False is passed in by default.
 * @param   int  $post_id   Current $post->ID, or 0 for the current post.
 *
 * @return  string            A shortlink
 */
function shortcm_get_shortlink($original, $post_id) {

    $shortcm = shortcm();

    // Verify this is a post we want to generate short links for
    if (!in_array(get_post_type($post_id), $shortcm->get_option('post_types')))
        return $original;

    if (0 == $post_id) {
        $post = get_post();
        $post_id = $post->ID;
    }

    $shortlink = get_post_meta($post_id, '_shortcm', true);

    if (!$shortlink)
        $shortlink = shortcm_generate_shortlink($post_id);

    return ($shortlink) ? $shortlink : $original;
}


/**
 * This is our shortcode handler, which could also be called directly.
 *
 * @since   0.1
 *
 * @param   array $atts Default shortcode attributes.
 */
function shortcm_shortlink($atts = array()) {

    $post = get_post();

    $defaults = array(
        'text'    => '',
        'title'   => '',
        'before'  => '',
        'after'   => '',
        'post_id' => $post->ID, // Use the current post by default, or pass an ID
    );

    extract(shortcode_atts($defaults, $atts));

    $permalink = get_permalink($post_id);
    $shortlink = shortcm_get_shortlink($permalink, $post_id);
 
    if (empty($text))
        $text = $shortlink;

    if (empty($title))
        $title = the_title_attribute(array('echo' => false));

    $output = '';

    if (!empty($shortlink)) {
        $output = apply_filters('the_shortlink', '<a rel="shortlink" href="' . esc_url($shortlink) . '" title="' . $title . '">' . $text . '</a>', $shortlink, $text, $title);
        $output = $before . $output . $after;
    }

    return $output;
}




add_filter('manage_posts_columns', 'ST4_columns_head');
add_action('manage_posts_custom_column', 'ST4_columns_content', 10, 2);

global $URL_output;


function ST4_columns_head($defaults) {
    $defaults['Short URL'] = 'Short URL';
    return $defaults;
}
 
 
function ST4_columns_content($column_name,$post_ID) {
 

   
 if ($column_name == 'Short URL') {     
    $permalink = get_permalink($post_ID);
    $shortlink = shortcm_get_shortlink($permalink, $post_ID);
    $URL_output = esc_url($shortlink);
?>

<div class="copy_text"  data-toggle="tooltip" title="Copy to Clipboard" ><?php echo $URL_output ?></div><br>

<img style="width:20px;color: #2271b1;" src="<?php echo SHORTCM_URL ?>/shortlink_copy.png"
class="copy" cus_link="<?php echo $URL_output ?>">
<?php
 }
         
 //echo $URL_output;
}

function custom_js_copy(){
    ?>

<script type="text/javascript">
jQuery('.copy').click(function (e) {
   e.preventDefault();
        var copyText = jQuery(this).attr('cus_link');
 

   document.addEventListener('copy', function(e) {
      e.clipboardData.setData('text/plain', copyText);
      e.preventDefault();
   }, true);

   document.execCommand('copy');  
   console.log('copied text : ', copyText);
        alert('copied text: ' + copyText);

   
 });
</script>
    <?php
}

