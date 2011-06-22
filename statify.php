<?php
/*
Plugin Name: Statify
Text Domain: statify
Domain Path: /lang
Description: Kompakte, verständliche und datenschutzkonforme Statistik für WordPress.
Author: Sergej M&uuml;ller
Author URI: http://www.wpSEO.org
Plugin URI: http://wpcoder.de
Version: 0.6
*/


if ( !class_exists('WP') ) {
header('Status: 403 Forbidden');
header('HTTP/1.1 403 Forbidden');
exit();
}
class Statify
{
private static $base;
private static $stats;
private static $limit = 3;
private static $days = 14;
public static function init()
{
if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) or (defined('DOING_CRON') && DOING_CRON) or (defined('DOING_AJAX') && DOING_AJAX) ) {
return;
}
self::$base = plugin_basename(__FILE__);
Statify_Table::init();
if ( is_admin() ) {
add_action(
'wpmu_new_blog',
array(
__CLASS__,
'install_later'
)
);
add_action(
'delete_blog',
array(
__CLASS__,
'uninstall_later'
)
);
add_action(
'wp_dashboard_setup',
array(
__CLASS__,
'init_dashboard'
)
);
add_filter(
'plugin_row_meta',
array(
__CLASS__,
'init_meta'
),
10,
2
);
} else {
add_action(
'template_redirect',
array(
__CLASS__,
'db_push'
)
);
}
}
public static function init_meta($links, $file)
{
if ( self::$base == $file ) {
return array_merge(
$links,
array(
'<a href="http://flattr.com/thing/148966/Statify-Plugin-fur-Datenschutz-konforme-Statistik-in-WordPress" target="_blank">Plugin flattern</a>'
)
);
}
return $links;
}
public static function init_dashboard()
{
if ( !current_user_can('administrator') ) {
return;
}
self::_prepare_stats();
wp_add_dashboard_widget(
'statify_dashboard',
'Statify',
array(
__CLASS__,
'front_view'
),
array(
__CLASS__,
'back_view'
)
);
add_action(
'admin_head',
array(
__CLASS__,
'add_style'
)
);
add_action(
'wp_print_scripts',
array(
__CLASS__,
'add_js'
)
);
}
public static function add_style()
{
$plugin = get_plugin_data(__FILE__);
wp_register_style(
'statify',
plugins_url('/css/dashboard.css', __FILE__),
array(),
$plugin['Version']
);
wp_print_styles('statify');
}
public static function add_js() {
if ( (!$stats = self::$stats) or empty($stats['visits']) ) {
return;
}
$plugin = get_plugin_data(__FILE__);
wp_register_script(
'statify',
plugins_url('/js/dashboard.js', __FILE__),
array(),
$plugin['Version']
);
wp_register_script(
'google_jsapi',
'http://www.google.com/jsapi',
false
);
wp_enqueue_script('google_jsapi');
wp_enqueue_script('statify');
wp_localize_script(
'statify',
'statify',
$stats['visits']
);
}
private static function get_options()
{
if ( $options = wp_cache_get('statify') ) {
return $options;
}
$options = wp_parse_args(
get_option('statify'),
array(
'days'=> self::$days,
'limit'=> self::$limit
)
);
wp_cache_set(
'statify',
$options
);
return $options;
}
public static function front_view()
{
if ( (!$stats = self::$stats) or empty($stats['visits']) ) {
echo '<p>Heutige Werte werden erst gesammelt.</p>';
return;
} ?>
<div id="statify_chart"></div>
<?php if ( !empty($stats['target']) ) { ?>
<div class="table referrer">
<p class="sub">Top Referrer</p>
<div>
<table>
<?php if ( empty($stats['referrer']) ) { ?>
<tr>
<td>
Keine
</td>
</tr>
<?php } else { ?>
<?php foreach ($stats['referrer'] as $referrer) { ?>
<tr class="first">
<td class="first b">
<a href="<?php echo esc_url($referrer['url']) ?>" target="_blank"><?php echo intval($referrer['count']) ?></a>
</td>
<td class="t">
<a href="<?php echo esc_url($referrer['url']) ?>" target="_blank"><?php echo esc_url($referrer['host']) ?></a>
</td>
</tr>
<?php } ?>
<?php } ?>
</table>
</div>
</div>
<div class="table target">
<p class="sub">Top Ziele</p>
<div>
<table>
<?php foreach ($stats['target'] as $target) { ?>
<tr class="first">
<td class="b">
<a href="<?php echo esc_url($target['url']) ?>" target="_blank"><?php echo intval($target['count']) ?></a>
</td>
<td class="last t">
<a href="<?php echo home_url($target['url']) ?>" target="_blank"><?php echo esc_url($target['url']) ?></a>
</td>
</tr>
<?php } ?>
</table>
</div>
</div>
<?php } ?>
<?php }
public static function back_view()
{
if ( !empty($_POST['statify']) ) {
if (!current_user_can('edit_plugins')) {
wp_die(__('Cheatin&#8217; uh?'));
}
check_admin_referer('_statify');
update_option(
'statify',
array(
'days'=> (int)@$_POST['statify']['days'],
'limit'=> (int)@$_POST['statify']['limit']
)
);
delete_transient('statify');
}
$options = self::get_options();
wp_nonce_field('_statify'); ?>
<table class="form-table">
<tr>
<td>
<select name="statify[days]" id="statify_days">
<?php foreach(array(7, 10, 14, 20, 21, 28, 30) as $num) { ?>
<option <?php selected($options['days'], $num); ?>><?php echo $num; ?></option>
<?php } ?>
</select>
<label for="statify_days">Anzahl der Tage für Statistiken</label>
</td>
</tr>
<tr>
<td>
<select name="statify[limit]" id="statify_limit">
<?php foreach(range(0, 12) as $num) { ?>
<option <?php selected($options['limit'], $num); ?>><?php echo $num; ?></option>
<?php } ?>
</select>
<label for="statify_limit">Anzahl der Einträge in Listen</label>
</td>
</tr>
</table>
<?php
}
public static function install()
{
global $wpdb;
if ( is_multisite() && !empty($_GET['networkwide']) ) {
$ids = $wpdb->get_col(
$wpdb->prepare("SELECT blog_id FROM `$wpdb->blogs`")
);
foreach ($ids as $id) {
switch_to_blog( (int)$id );
self::_install_backend();
}
restore_current_blog();
} else {
self::_install_backend();
}
}
public static function install_later($id) {
global $wpdb;
if ( !is_plugin_active_for_network(self::$base) ) {
return;
}
switch_to_blog( (int)$id );
self::_install_backend();
restore_current_blog();
}
protected static function _install_backend()
{
add_option(
'statify',
array(),
'',
'no'
);
delete_transient('statify');
Statify_Table::init();
Statify_Table::create();
}
public static function uninstall()
{
global $wpdb;
if ( is_multisite() && !empty($_GET['networkwide']) ) {
$old = $wpdb->blogid;
$ids = $wpdb->get_col(
$wpdb->prepare("SELECT blog_id FROM `$wpdb->blogs`")
);
foreach ($ids as $id) {
switch_to_blog($id);
self::_uninstall_backend();
}
switch_to_blog($old);
} else {
self::_uninstall_backend();
}
}
public static function uninstall_later($id) {
global $wpdb;
if ( !is_plugin_active_for_network(self::$base) ) {
return;
}
switch_to_blog( (int)$id );
self::_uninstall_backend();
restore_current_blog();
}
protected static function _uninstall_backend()
{
delete_option('statify');
delete_transient('statify');
Statify_Table::init();
Statify_Table::drop();
}
public static function update()
{
self::_update_backend();
}
protected static function _update_backend()
{
delete_transient('statify');
}
public static function db_push()
{
if ( is_feed() or is_trackback() or is_robots() or is_preview() or is_user_logged_in() or is_404() or self::_is_bot() ) {
return;
}
global $wpdb, $wp_rewrite;
$data = array();
$home = home_url();
$data['created'] = strftime('%Y-%m-%d', current_time('timestamp'));
if ( !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $home) === false ) {
$data['referrer'] = esc_url_raw($_SERVER['HTTP_REFERER']);
}
$data['target'] = str_replace(
$home,
'',
esc_url_raw(
home_url(
( empty($_SERVER['REQUEST_URI']) ? '/' : $_SERVER['REQUEST_URI'] )
)
)
);
if ( $wp_rewrite->permalink_structure && !is_search() ) {
$data['target'] = preg_replace('/\?.*/', '', $data['target']);
}
$wpdb->insert(
$wpdb->statify,
$data
);
}
protected static function _is_bot()
{
if ( empty($_SERVER['HTTP_USER_AGENT']) ) {
return true;
}
if ( !preg_match('/(?:Windows|Mac OS X|Macintosh|Linux)/', $_SERVER['HTTP_USER_AGENT']) ) {
return true;
}
return false;
}
protected static function _clean_data()
{
if ( get_transient('statify_cron') ) {
return;
}
global $wpdb;
$options = self::get_options();
$wpdb->query(
$wpdb->prepare(
"DELETE FROM `$wpdb->statify` WHERE created <= SUBDATE(CURDATE(), %d)",
$options['days']
)
);
$wpdb->query(
"OPTIMIZE TABLE `$wpdb->statify`"
);
set_transient(
'statify_cron',
'ilovesweta',
60 * 60 * 12
);
}
protected static function _get_stats()
{
global $wpdb;
$options = self::get_options();
return array(
'visits' => $wpdb->get_results(
$wpdb->prepare(
"SELECT DATE_FORMAT(`created`, '%%d.%%m') as `created`, COUNT(`created`) as `count` FROM `$wpdb->statify` GROUP BY `created` ORDER BY `created` DESC LIMIT %d",
$options['days']
),
ARRAY_A
),
'target' => $wpdb->get_results(
$wpdb->prepare(
"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->statify` GROUP BY `target` ORDER BY `count` DESC LIMIT %d",
$options['limit']
),
ARRAY_A
),
'referrer' => $wpdb->get_results(
$wpdb->prepare(
"SELECT COUNT(`referrer`) as `count`, `referrer` as `url`, SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host` FROM `$wpdb->statify` WHERE `referrer` != '' GROUP BY `host` ORDER BY `count` DESC LIMIT %d",
$options['limit']
),
ARRAY_A
)
);
}
protected static function _prepare_stats()
{
if ( ($stats = get_transient('statify')) && self::$stats = $stats ) {
return;
}
self::_clean_data();
if ( !$stats = self::_get_stats() ) {
return;
}
if ( !$visits = $stats['visits'] ) {
return;
}
if ( $visits[0]['created'] == date('d.m', current_time('timestamp')) ) {
$visits[0]['created'] = 'Heute';
}
$output = array(
'created' => array(),
'count' => array()
);
foreach($visits as $item) {
array_push($output['created'], $item['created']);
array_push($output['count'], $item['count']);
}
$stats['visits'] = array(
'created' => implode(',', $output['created']),
'count' => implode(',', $output['count'])
);
set_transient(
'statify',
$stats,
60 * 15);
self::$stats = $stats;
}
}
class Statify_Table
{
public function init()
{
global $wpdb;
$table = 'statify';
$wpdb->tables[] = $table;
$wpdb->$table = $wpdb->get_blog_prefix() . $table;
}
public function create()
{
global $wpdb;
if ( $wpdb->get_var("SHOW TABLES LIKE '$wpdb->statify'") == $wpdb->statify ) {
return;
}
require_once(ABSPATH. 'wp-admin/includes/upgrade.php');
dbDelta(
"CREATE TABLE `$wpdb->statify` (
`id` bigint(20) unsigned NOT NULL auto_increment,
`created` date NOT NULL default '0000-00-00',
`referrer` varchar(255) NOT NULL default '',
`target` varchar(255) NOT NULL default '',
PRIMARY KEY(`id`),
KEY `referrer` (`referrer`),
KEY `target` (`target`),
KEY `created` (`created`)
);"
);
}
public function drop()
{
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS `$wpdb->statify`");
}
}
add_action(
'plugins_loaded',
array(
'Statify',
'init'
)
);
register_activation_hook(
__FILE__,
array(
'Statify',
'install'
)
);
register_uninstall_hook(
__FILE__,
array(
'Statify',
'uninstall'
)
);
if ( function_exists('register_update_hook') ) {
register_update_hook(
__FILE__,
array(
'Statify',
'update'
)
);
}