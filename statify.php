<?php
/*
Plugin Name: Statify
Text Domain: statify
Domain Path: /lang
Description: Kompakte, verständliche und anonyme Statistik für Blogseiten.
Author: Sergej M&uuml;ller
Author URI: http://www.wpSEO.org
Plugin URI: http://wpcoder.de
Version: 0.4
*/


if ( !function_exists ('is_admin') ) {
header('Status: 403 Forbidden');
header('HTTP/1.1 403 Forbidden');
exit();
}
class Statify
{
private static $table;
private static $stats;
private static $limit = 3;
private static $days = 14;
public function __construct()
{
if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) or (defined('DOING_CRON') && DOING_CRON) or (defined('DOING_AJAX') && DOING_AJAX) ) {
return;
}
self::$table = $GLOBALS['wpdb']->prefix. 'statify';
add_action(
'plugins_loaded',
array(
__CLASS__,
'init'
)
);
register_activation_hook(
__FILE__,
array(
__CLASS__,
'activate'
)
);
}
public static function init()
{
if ( is_admin() ) {
add_action(
'wp_dashboard_setup',
array(
__CLASS__,
'dashboard'
)
);
} else {
add_action(
'template_redirect',
array(
__CLASS__,
'push'
)
);
}
}
public static function dashboard()
{
if ( !current_user_can('administrator') ) {
return;
}
self::prepare();
wp_add_dashboard_widget(
'statify_dashboard',
'Statify',
array(
__CLASS__,
'front'
),
array(
__CLASS__,
'back'
)
);
add_action(
'admin_head',
array(
__CLASS__,
'style'
)
);
add_action(
'wp_print_scripts',
array(
__CLASS__,
'javascript'
)
);
}
public static function style()
{
wp_register_style(
'statify',
plugins_url('/css/dashboard.css', __FILE__)
);
wp_print_styles('statify');
}
public static function javascript() {
if ( (!$stats = self::$stats) or empty($stats['counts']) ) {
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
$stats['counts']
);
}
private static function options()
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
public static function front()
{
if ( (!$stats = self::$stats) or empty($stats['target']) or empty($stats['referrer']) ) {
return;
} ?>
<div id="statify_chart"></div>
<div class="table referrer">
<p class="sub"><?php esc_html_e('Top Referrer', 'statify'); ?></p>
<div>
<table>
<?php foreach ($stats['referrer'] as $referrer) { ?>
<tr class="first">
<td class="first b">
<a href="<?php echo esc_url($referrer['url']) ?>" target="_blank"><?php echo intval($referrer['count']) ?></a>
</td>
<td class="t">
<a href="<?php echo esc_url($referrer['url']) ?>" target="_blank"><?php echo esc_html($referrer['host']) ?></a>
</td>
</tr>
<?php } ?>
</table>
</div>
</div>
<div class="table target">
<p class="sub"><?php esc_html_e('Top Ziele', 'statify'); ?></p>
<div>
<table>
<?php foreach ($stats['target'] as $target) { ?>
<tr class="first">
<td class="b">
<a href="<?php echo esc_url($target['url']) ?>" target="_blank"><?php echo intval($target['count']) ?></a>
</td>
<td class="last t">
<a href="<?php echo home_url($target['url']) ?>" target="_blank"><?php echo esc_html($target['url']) ?></a>
</td>
</tr>
<?php } ?>
</table>
</div>
</div>
<?php }
public static function back()
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
$options = self::options();
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
<?php foreach(range(1, 12) as $num) { ?>
<option <?php selected($options['limit'], $num); ?>><?php echo $num; ?></option>
<?php } ?>
</select>
<label for="statify_limit">Anzahl der Einträge in Listen</label>
</td>
</tr>
</table>
<?php
}
public static function activate()
{
add_option(
'statify',
array(),
'',
'no'
);
$table = self::$table;
if ( $GLOBALS['wpdb']->get_var("SHOW TABLES LIKE '$table'") == $table ) {
return;
}
require_once(ABSPATH. 'wp-admin/includes/upgrade.php');
dbDelta(
"CREATE TABLE `$table` (
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
public static function push()
{
if ( is_feed() or is_trackback() or is_robots() or is_preview() or is_user_logged_in() or self::is_bot() ) {
return;
}
$data = array();
$home = home_url();
$data['created'] = strftime('%Y-%m-%d');
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
$GLOBALS['wpdb']->insert(
self::$table,
$data
);
}
private static function is_bot()
{
if ( empty($_SERVER['HTTP_USER_AGENT']) ) {
return true;
}
if ( !preg_match('/(?:Windows|Mac OS X|Macintosh|Linux)/', $_SERVER['HTTP_USER_AGENT']) ) {
return true;
}
return false;
}
private static function clean()
{
if ( get_transient('statify_cron') ) {
return;
}
global $wpdb;
$table = self::$table;
$options = self::options();
$wpdb->query(
$wpdb->prepare(
"DELETE FROM `$table` WHERE created <= SUBDATE(CURDATE(), %d)",
$options['days']
)
);
$wpdb->query(
"OPTIMIZE TABLE `$table`"
);
set_transient(
'statify_cron',
'ilovesweta',
60 * 60 * 12
);
}
private static function stats()
{
global $wpdb;
$table = self::$table;
$options = self::options();
return array(
'counts' => $wpdb->get_results(
$wpdb->prepare(
"SELECT `created`, COUNT(`created`) as `count` FROM `$table` GROUP BY `created` ORDER BY `created` DESC LIMIT %d",
$options['days']
),
ARRAY_A
),
'target' => $wpdb->get_results(
$wpdb->prepare(
"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$table` GROUP BY `target` ORDER BY `count` DESC LIMIT %d",
$options['limit']
),
ARRAY_A
),
'referrer'=> $wpdb->get_results(
$wpdb->prepare(
"SELECT COUNT(`referrer`) as `count`, `referrer` as `url`, SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host` FROM `$table`WHERE `referrer` != '' GROUP BY `host` ORDER BY `count` DESC LIMIT %d",
$options['limit']
),
ARRAY_A
)
);
}
private static function prepare()
{
if ( ($stats = get_transient('statify')) && self::$stats = $stats ) {
return;
}
self::clean();
if ( !$stats = self::stats() ) {
return;
}
if ( !$counts = $stats['counts'] ) {
return;
}
$output = array();
foreach($counts as $row) {
array_unshift($output, $row['count']);
}
$first = $counts[0];
$last = end($counts);
$start = '';
$end = $first['created'];
$max = max($output);
if ( $first != $last ) {
$start = sprintf(
'%s %s|',
human_time_diff(
strtotime($last['created']),
strtotime($end)
),
esc_html__('zuvor', 'statify')
);
}
if ( $end == strftime('%Y-%m-%d') ) {
$end = esc_html__('Heute', 'statify');
}
$stats['counts'] = array(
'counts' => implode('|', $output),
'x_axis' => sprintf('%s%s', $start, $end),
'y_axis' => sprintf('%d|%d', intval($max / 2), $max),
);
set_transient(
'statify',
$stats,
60 * 15);
self::$stats = $stats;
}
}
new Statify();