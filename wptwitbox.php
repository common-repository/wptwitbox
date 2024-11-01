<?php
/*
Plugin Name: wpTwitBox
Text Domain: wptwitbox
Domain Path: /lang
Description: Tool box with useful power tools around Twitter and Bit.ly shortener.
Author: Sergej M&uuml;ller
Author URI: http://www.wpSEO.org
Plugin URI: http://playground.ebiene.de/2375/wptwitbox-wordpress-plugin/
Version: 0.5.1
*/


if (!function_exists('is_admin')) {
header('Status: 403 Forbidden');
header('HTTP/1.1 403 Forbidden');
exit();
}
class wpTwitBox {
function wpTwitBox() {
$this->base = plugin_basename(__FILE__);
$this->oauth = false;
if (defined('DOING_CRON')) {
add_action(
'publish_future_post',
array(
$this,
'ping_twitter_api'
)
);
return;
}
if (is_admin()) {
if (defined('DOING_AJAX')) {
add_action(
'wp_ajax_wtb_ajax_response',
array(
$this,
'get_ajax_response'
)
);
} else {
add_action(
'admin_menu',
array(
$this,
'init_admin_menu'
)
);
if ($this->is_current_page('home')) {
add_action(
'init',
array(
$this,
'verify_twitter_response'
)
);
add_action(
'init',
array(
$this,
'load_plugin_lang'
)
);
add_action(
'admin_init',
array(
$this,
'add_plugin_sources'
)
);
} else if ($this->is_current_page('index')) {
add_action(
'init',
array(
$this,
'load_plugin_lang'
)
);
add_action(
'admin_notices',
array(
$this,
'check_twitter_connect'
)
);
} else if ($this->is_current_page('plugins')) {
add_action(
'init',
array(
$this,
'load_plugin_lang'
)
);
add_action(
'activate_' .$this->base,
array(
$this,
'init_plugin_options'
)
);
add_action(
'admin_notices',
array(
$this,
'verify_wp_version'
)
);
add_filter(
'plugin_row_meta',
array(
$this,
'init_row_meta'
),
10,
2
);
} else if ($this->is_current_page('post')) {
add_action(
'publish_post',
array(
$this,
'ping_twitter_api'
)
);
}
}
} else {
add_action(
'comment_post',
array(
$this,
'replace_comment_links'
),
1,
1
);
}
add_action(
'wptwitbox_followers_count',
array(
$this,
'the_followers_count'
)
);
add_action(
'wptwitbox_tweet_link',
array(
$this,
'the_tweet_link'
)
);
}
function load_plugin_lang() {
load_plugin_textdomain(
'wptwitbox',
false,
'wptwitbox/lang'
);
}
function verify_twitter_response() {
if (!empty($_GET['denied'])) {
$this->update_options(
array(
'oauth_token'=> '',
'oauth_secret' => ''
)
);
wp_safe_redirect(
admin_url('options-general.php?page='. $this->base)
);
exit();
}
if (empty($_GET['oauth_token']) || empty($_GET['oauth_verifier'])) {
return;
}
@session_start();
require_once(dirname(__FILE__). '/inc/twitteroauth.php');
require_once(dirname(__FILE__). '/inc/config.php');
$connect = new TwitterOAuth(
CONSUMER_KEY,
CONSUMER_SECRET,
$_SESSION['oauth_token'],
$_SESSION['oauth_token_secret']
);
$access = $connect->getAccessToken($_GET['oauth_verifier']);
$this->update_options(
array(
'oauth_token' => $access['oauth_token'],
'oauth_secret' => $access['oauth_token_secret']
)
);
wp_safe_redirect(
admin_url('options-general.php?page='. $this->base)
);
exit();
}
function init_twitter_oauth() {
$oauth_token = $this->get_option('oauth_token');
$oauth_secret = $this->get_option('oauth_secret');
if (empty($oauth_token) || empty($oauth_secret)) {
return false;
}
require_once(dirname(__FILE__). '/inc/twitteroauth.php');
require_once(dirname(__FILE__). '/inc/config.php');
$this->oauth = new TwitterOAuth(
CONSUMER_KEY,
CONSUMER_SECRET,
$oauth_token,
$oauth_secret
);
return true;
}
function exe_twitter_call($call, $type = 'get', $params = array()) {
if (!$this->init_twitter_oauth()) {
return false;
}
if ($type == 'get') {
$response = $this->oauth->get($call);
} else if ($type == 'post') {
$response = $this->oauth->post($call,$params);
} else {
return false;
}
if (empty($response->error)) {
return $response;
}
if ($response->error == 'Could not authenticate with OAuth.') {
$this->update_options(
array(
'oauth_token'=> '',
'oauth_secret' => ''
)
);
}
return false;
}
function check_twitter_connect() {
if (!$this->get_option('update_count') && !$this->get_option('ping_twitter')) {
return;
}
if ($this->get_option('oauth_token') && $this->get_option('oauth_secret')) {
return;
}
echo sprintf(
'<div class="error"><p><strong>%s</strong>: <a href="%s">%s</a></p></div>',
__('wpTwitBox reports', 'wptwitbox'),
admin_url('options-general.php?page='. $this->base),
__('Please sign in with Twitter', 'wptwitbox')
);
}
function init_row_meta($links, $file) {
if ($this->base == $file) {
return array_merge(
$links,
array(
sprintf(
'<a href="options-general.php?page=%s">%s</a>',
$this->base,
__('Settings')
)
)
);
}
return $links;
}
function init_plugin_options() {
add_option(
'wptwitbox',
array(),
'',
'no'
);
}
function get_option($field) {
if (!$options = wp_cache_get('wptwitbox')) {
$options = get_option('wptwitbox');
wp_cache_set(
'wptwitbox',
$options
);
}
return @$options[$field];
}
function update_option($field, $value) {
$this->update_options(
array(
$field => $value
)
);
}
function update_options($data) {
$options = array_merge(
(array)get_option('wptwitbox'),
$data
);
update_option(
'wptwitbox',
$options
);
wp_cache_set(
'wptwitbox',
$options
);
}
function init_admin_menu() {
$page = add_options_page(
'wpTwitBox',
'<img src="' .plugins_url('wptwitbox/img/icon.png'). '" id="wtb_icon" alt="wpTwitBox" />wpTwitBox',
'manage_options',
__FILE__,
array(
$this,
'show_admin_menu'
)
);
add_action(
'admin_print_scripts-' . $page,
array(
$this,
'add_enqueue_script'
)
);
add_action(
'admin_print_styles-' . $page,
array(
$this,
'add_enqueue_style'
)
);
}
function add_plugin_sources() {
$data = get_plugin_data(__FILE__);
wp_register_script(
'wtb_script',
plugins_url('wptwitbox/js/script.js'),
array('jquery'),
$data['Version']
);
wp_register_style(
'wtb_style',
plugins_url('wptwitbox/css/style.css'),
array(),
$data['Version']
);
}
function add_enqueue_script() {
wp_enqueue_script('wtb_script');
wp_localize_script(
'wtb_script',
'wtb_settings',
array(
'nonce' => wp_create_nonce('wtb_ajax_nonce'),
'ajax'=> admin_url('admin-ajax.php')
)
);
}
function add_enqueue_style() {
wp_enqueue_style('wtb_style');
}
function is_min_wp($version) {
return version_compare(
$GLOBALS['wp_version'],
$version. 'alpha',
'>='
);
}
function get_ajax_response() {
check_ajax_referer('wtb_ajax_nonce');
if (empty($_POST['_action_request'])) {
exit();
}
$output = -1;
switch ($_POST['_action_request']) {
case 'verify_login':
if (empty($_POST['_bitly_login']) || empty($_POST['_bitly_apikey'])) {
break;
}
$response = wp_remote_get(
sprintf(
'http://api.bit.ly/v3/validate?login=%1$s&apiKey=%2$s&x_login=%1$s&x_apiKey=%2$s&format=txt',
$_POST['_bitly_login'],
$_POST['_bitly_apikey']
)
);
if (is_wp_error($response)) {
break;
}
$output = wp_remote_retrieve_body($response);
break;
default:
break;
}
header('Content-Type: plain/text');
echo $output;
exit();
}
function verify_wp_version() {
if ($this->is_min_wp('2.8') && $this->is_min_php('5.0.0')) {
return;
}
echo sprintf(
'<div class="error"><p><strong>wpTwitBox</strong> %s</p></div>',
__('requires at least WordPress 2.8 and PHP 5', 'wptwitbox')
);
}
function show_plugin_info() {
$data = get_plugin_data(__FILE__);
echo sprintf(
'wpTwitBox %s %s <a href="http://eBiene.de" target="_blank">Sergej M&uuml;ller</a> | <a href="http://twitter.com/wpSEO" target="_blank">%s</a> | <a href="%s/?utm_source=wptwitbox&utm_medium=plugin&utm_campaign=plugins" target="_blank">%s</a>',
$data['Version'],
__('by', 'wptwitbox'),
__('Follow on Twitter', 'wptwitbox'),
__('http://www.wpSEO.org', 'wptwitbox'),
__('Learn about wpSEO', 'wptwitbox')
);
}
function is_min_php($version) {
return version_compare(
phpversion(),
$version,
'>='
);
}
function is_current_page($page) {
switch($page) {
case 'home':
return (!empty($_REQUEST['page']) && $_REQUEST['page'] == $this->base);
case 'index':
case 'post':
case 'plugins':
return (!empty($GLOBALS['pagenow']) && $GLOBALS['pagenow'] == sprintf('%s.php', $page));
default:
return false;
}
}
function decode_html_entity($data) {
$decoded = @html_entity_decode(
$data,
ENT_QUOTES,
get_bloginfo('charset')
);
return (empty($decoded) ? $data : $decoded);
}
function ping_twitter_api($id) {
if (!$this->get_option('ping_twitter')) {
return;
}
if (!intval($id)) {
return;
}
$post = get_post($id);
if ($post->post_status != 'publish' || (!empty($_POST['original_post_status']) && $_POST['original_post_status'] == 'publish')) {
return;
}
$url = $this->get_bitly_link(get_permalink($id));
$title = $this->decode_html_entity($post->post_title);
$title = stripcslashes(strip_tags($title));
$title = strlen($title) > 110 ? (substr($title, 0, 110). ' [...]') : $title;
$status = sprintf(
'%s %s',
$title,
$url
);
$this->exe_twitter_call(
'statuses/update',
'post',
array(
'status' => $status
)
);
}
function get_bitly_link($perma, $check = false) {
if (!$perma) {
return;
}
$login = $this->get_option('bitly_login');
$apikey = $this->get_option('bitly_apikey');
$jmp = $this->get_option('use_jmp');
if ($check !== false && (strlen($perma) <= ($jmp ? 18 : 20))) {
return $perma;
}
if (empty($login) || empty($apikey)) {
return $perma;
}
$response = wp_remote_get(
sprintf(
'http://api.bit.ly/v3/shorten?login=%s&apiKey=%s&longUrl=%s&format=txt&domain=' .($jmp ? 'j.mp' : 'bit.ly'),
$login,
$apikey,
urlencode($perma)
)
);
if (is_wp_error($response)) {
return $perma;
}
$bitly = wp_remote_retrieve_body($response);
if (!empty($bitly) && strpos($bitly, 'http://') !== false) {
return $bitly;
}
return $perma;
}
function is_published_post($id) {
if (!$id) {
return;
}
$post = get_post($id);
return $post->post_status == 'publish';
}
function cache_bitly_link($id, $url) {
if (!$id || !$url) {
return;
}
add_post_meta(
$id,
'_wptwitbox_bitly_url',
$url
);
}
function get_tweet_link() {
if (!$id = get_the_ID()) {
return;
}
if (!$url = get_post_meta($id, '_wptwitbox_bitly_url', true)) {
if ($this->is_published_post($id)) {
$url = $this->get_bitly_link(
get_permalink($id)
);
}
if (!$url) {
return sprintf(
'%s?p=%s',
get_bloginfo('url'),
$id
);
}
if ($this->get_option('cache_bitly')) {
$this->cache_bitly_link($id, $url);
}
}
return trim($url);
}
function the_tweet_link() {
echo sprintf(
'http://twitter.com/home?status=%s%s%s',
$this->get_tweet_link(),
urlencode(' '),
urlencode($this->decode_html_entity(get_the_title()))
);
}
function replace_comment_links($id) {
if (!$this->get_option('replace_links')) {
return;
}
$comment = get_comment($id, ARRAY_A);
if (empty($comment) || $comment['comment_approved'] == 'spam') {
return false;
}
$comment['comment_content'] = trim(
preg_replace_callback(
'#([\s>])((https?)://([^\s<>{}()]+[^\s.,<>{}()]))#i',
create_function(
'$a',
'return $a[1] .$GLOBALS["wpTwitBox"]->get_bitly_link($a[2], true);'
),
' ' .$comment['comment_content']. ' '
)
);
wp_update_comment($comment);
}
function get_followers_count() {
if (!$count = get_transient('wptwitbox_count')) {
if ($data = $this->exe_twitter_call('account/verify_credentials')) {
if ($count = $data->followers_count) {
set_transient(
'wptwitbox_count',
intval($count),
60 * 60 * 1
);
}
}
}
return intval($count);
}
function the_followers_count() {
$count = $this->get_followers_count();
echo (get_locale() == 'de_DE' ? number_format($count, 0, '', '.') : number_format_i18n($count));
}
function show_admin_menu() {
if (!empty($_POST)) {
check_admin_referer('wptwitbox');
$options = array(
'bitly_login'=> (string)(@$_POST['wptwitbox_bitly_login']),
'bitly_apikey'=> (string)(@$_POST['wptwitbox_bitly_apikey']),
'replace_links'=> (int)(!empty($_POST['wptwitbox_replace_links'])),
'cache_bitly'=> (int)(!empty($_POST['wptwitbox_cache_bitly'])),
'use_jmp'=> (int)(!empty($_POST['wptwitbox_use_jmp'])),
'ping_twitter'=> (int)(!empty($_POST['wptwitbox_ping_twitter'])),
'update_count'=> (int)(!empty($_POST['wptwitbox_update_count']))
);
$this->update_options($options); ?>
<div id="message" class="updated fade">
<p>
<strong>
<?php _e('Settings saved.') ?>
</strong>
</p>
</div>
<?php } ?>
<div class="wrap">
<div class="icon32"></div>
<h2>
wpTwitBox
</h2>
<form method="post" action="">
<?php wp_nonce_field('wptwitbox') ?>
<div id="poststuff">
<div class="postbox">
<h3>
<?php _e('Settings') ?>
</h3>
<div class="inside">
<fieldset>
<legend>
<?php _e('Bit.ly Actions', 'wptwitbox') ?>
</legend>
<ul id="wtb_bitly_user">
<li>
<label for="wptwitbox_bitly_login">
<?php _e('Bit.ly Login', 'wptwitbox') ?>
</label>
<input type="text" name="wptwitbox_bitly_login" id="wptwitbox_bitly_login" autocomplete="off" class="regular-text" value="<?php echo $this->get_option('bitly_login') ?>" />
</li>
<li>
<label for="wptwitbox_bitly_apikey">
<?php _e('Bit.ly API Key', 'wptwitbox') ?> [<a href="http://bit.ly/a/account" target="_blank">?</a>]
</label>
<input type="text" name="wptwitbox_bitly_apikey" id="wptwitbox_bitly_apikey" autocomplete="off" class="regular-text" value="<?php echo $this->get_option('bitly_apikey') ?>" />
</li>
<li>
<input type="checkbox" name="wptwitbox_replace_links" id="wptwitbox_replace_links" value="1" <?php checked($this->get_option('replace_links'), 1) ?> />
<label for="wptwitbox_replace_links">
<?php _e('Convert links to Bit.ly links in incoming comments', 'wptwitbox') ?>
</label>
</li>
<li>
<input type="checkbox" name="wptwitbox_cache_bitly" id="wptwitbox_cache_bitly" value="1" <?php checked($this->get_option('cache_bitly'), 1) ?> />
<label for="wptwitbox_cache_bitly">
<?php _e('Keep post permalinks as cachable Bit.ly links', 'wptwitbox') ?>
</label>
</li>
<li>
<input type="checkbox" name="wptwitbox_use_jmp" id="wptwitbox_use_jmp" value="1" <?php checked($this->get_option('use_jmp'), 1) ?> />
<label for="wptwitbox_use_jmp">
<?php _e('Use J.mp instead of Bit.ly', 'wptwitbox') ?>
</label>
</li>
</ul>
</fieldset>
<fieldset>
<legend>
<?php _e('Twitter Actions', 'wptwitbox') ?>
</legend>
<ul id="wtb_twitter_user">
<li>
<?php if ($data = $this->exe_twitter_call('account/verify_credentials')) { ?>
<a href="http://twitter.com/<?php echo $data->screen_name ?>" target="_blank">
<img src="<?php echo $data->profile_image_url ?>" />
</a>
<h4>
<a href="http://twitter.com/<?php echo $data->screen_name ?>" target="_blank">
<?php echo $data->screen_name ?>
</a>
</h4>
<?php } else { ?>
<a href="<?php echo plugins_url('wptwitbox/login/login.php') ?>?_callback=<?php echo urlencode(admin_url('options-general.php?page='. $this->base)) ?>" class="signup"><?php _e('Sign in with Twitter', 'wptwitbox') ?></a>
<?php } ?>
</li>
<li>
<input type="checkbox" name="wptwitbox_update_count" id="wptwitbox_update_count" value="1" <?php checked($this->get_option('update_count'), 1) ?> />
<label for="wptwitbox_update_count">
<?php _e('Hourly update for your twitter followers count', 'wptwitbox') ?>
</label>
</li>
<li>
<input type="checkbox" name="wptwitbox_ping_twitter" id="wptwitbox_ping_twitter" value="1" <?php checked($this->get_option('ping_twitter'), 1) ?> />
<label for="wptwitbox_ping_twitter">
<?php _e('Auto Tweet: Twitter notification if new posts', 'wptwitbox') ?>
</label>
</li>
</ul>
</fieldset>
<p>
<input type="submit" name="antispam_bee_submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</div>
</div>
<div class="postbox">
<h3>
<?php _e('About', 'wptwitbox') ?>
</h3>
<div class="inside">
<p>
<?php $this->show_plugin_info() ?>
</p>
</div>
</div>
</div>
</form>
</div>
<?php }
}
$GLOBALS['wpTwitBox'] = new wpTwitBox();