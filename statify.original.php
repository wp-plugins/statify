<?php
/*
Plugin Name: Statify
Description: Kompakte, begreifliche und datenschutzkonforme Statistik für WordPress.
Author: Sergej M&uuml;ller
Author URI: http://wpseo.de
Plugin URI: http://playground.ebiene.de/statify-wordpress-statistik/
Version: 0.8
*/


/* Sicherheitsabfrage */
if ( !class_exists('WP') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}


/**
* Statify
*
* @since 0.1
*/

class Statify
{


	/* Save me */
	private static $base;
	private static $stats;
	private static $days = 14;
	private static $limit = 3;
	private static $today = 0;


	/**
	* Konstruktor der Klasse
	*
	* @since   0.1
	* @change  0.6
	*/

	public static function init()
	{
		/* Filter */
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) or (defined('DOING_CRON') && DOING_CRON) or (defined('DOING_AJAX') && DOING_AJAX) or (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) ) {
			return;
		}

		/* Plugin-Base */
		self::$base = plugin_basename(__FILE__);
		
		/* Tabelle Init */
		Statify_Table::init();

		/* BE */
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
			add_filter(
				'plugin_action_links_' .self::$base,
				array(
					__CLASS__,
					'init_action'
				)
			);

		/* FE */
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
	
	
	/**
	* Hinzufügen der Action-Links (Einstellungen links)
	*
	* @since   0.8
	* @change  0.8
	*/

	public static function init_action($data)
	{
		/* Rechte? */
		if ( !current_user_can('manage_options') ) {
			return $data;
		}

		return array_merge(
			$data,
			array(
				sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'edit' => 'statify_dashboard#statify_dashboard'
						),
						admin_url('/')
					),
					__('Settings')
				)
			)
		);
	}


	/**
	* Links in der Plugins-Verwaltung
	*
	* @since   0.5
	* @change  0.8
	*
	* @param   array   $links  Array mit Links
	* @param   string  $file   Name des Plugins
	* @return  array   $links  Array mit erweitertem Link
	*/

	public static function init_meta($links, $file)
	{
		if ( self::$base == $file ) {
			return array_merge(
				$links,
				array(
					'<a href="http://flattr.com/thing/148966/Statify-Plugin-fur-Datenschutz-konforme-Statistik-in-WordPress" target="_blank">Plugin flattern</a>',
					'<a href="https://plus.google.com/110569673423509816572" target="_blank">Auf Google+ folgen</a>'
				)
			);
		}

		return $links;
	}


	/**
	* Anzeige des Dashboard-Widgets
	*
	* @since   0.1
	* @change  0.8
	*/

	public static function init_dashboard()
	{
		/* Filter */
		if ( !current_user_can('level_2') ) {
			return;
		}

		/* Vorbereiten */
		self::_prepare_stats();

		/* Widget */
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

		/* CSS laden */
		add_action(
			'admin_print_styles',
			array(
				__CLASS__,
				'add_style'
			)
		);

		/* JS laden */
		add_action(
			'admin_print_scripts',
			array(
				__CLASS__,
				'add_js'
			)
		);
	}


	/**
	* Ausgabe der Stylesheets
	*
	* @since   0.1
	* @change  0.8
	*/

	public static function add_style()
	{
		/* PLugin-Info */
		$plugin = get_plugin_data(__FILE__);
		
		/* CSS registrieren */
		wp_register_style(
			'statify',
			plugins_url('/css/dashboard.css', __FILE__),
	  		array(),
	  		$plugin['Version']
		);

	  	/* CSS ausgeben */
	  	wp_enqueue_style('statify');
	}


	/**
	* Ausgabe von JavaScript
	*
	* @since   0.1
	* @change  0.6
	*/

	public static function add_js() {
		/* Leer? */
		if ( (!$stats = self::$stats) or empty($stats['visits']) ) {
			return;
		}
		
		/* PLugin-Info */
		$plugin = get_plugin_data(__FILE__);
		
		/* Registrieren */
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

		/* Einbinden */
		wp_enqueue_script('google_jsapi');
		wp_enqueue_script('statify');

		/* Übergeben */
		wp_localize_script(
			'statify',
			'statify',
			$stats['visits']
		);
	}


	/**
	* Rückgabe der Optionen
	*
	* @since   0.4
	* @change  0.8
	*
	* @return  array  $options  Array mit Optionen
	*/

	private static function get_options()
	{
		/* Im Cache */
		if ( $options = wp_cache_get('statify') ) {
			return $options;
		}

		/* Zusammenführen */
		$options = wp_parse_args(
			get_option('statify'),
			array(
				'days'	=> self::$days,
				'limit'	=> self::$limit,
				'today' => self::$today
			)
		);

		/* Ins Cache */
		wp_cache_set(
			'statify',
			$options
		);

		return $options;
	}


	/**
	* Ausgabe der Frontseite
	*
	* @since   0.1
	* @change  0.6
	*/

	public static function front_view()
	{
		/* Leer? */
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


	/**
	* Ausgabe der Backseite
	*
	* @since   0.4
	* @change  0.8
	*/

	public static function back_view()
	{
		/* Rechte */
		if ( !current_user_can('manage_options') ) {
			return;
		}
		
		/* Speichern */
		if ( !empty($_POST['statify']) ) {
			/* Formular-Referer */
			check_admin_referer('_statify');

			/* Save */
			update_option(
				'statify',
				array(
					'days'	=> (int)@$_POST['statify']['days'],
					'limit'	=> (int)@$_POST['statify']['limit'],
					'today'	=> (int)@$_POST['statify']['today']
				)
			);

			/* Entleeren */
			delete_transient('statify');
		}

		/* Optionen */
		$options = self::get_options();

		/* Security */
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
			
			<tr>
				<td>
					<input type="checkbox" name="statify[today]" id="statify_today" value="1" <?php checked($options['today'], 1) ?> />
					<label for="statify_today">Referrer und Ziele nur vom aktuellen Tag zeigen</label>
				</td>
			</tr>
		</table>

		<?php
	}


	/**
	* Installation des Plugins auch für MU-Blogs
	*
	* @since   0.1
	* @change  0.6
	*/

	public static function install()
	{
		/* Global */
		global $wpdb;

		/* Multisite & Network */
		if ( is_multisite() && !empty($_GET['networkwide']) ) {
			/* Blog-IDs */
			$ids = $wpdb->get_col(
				$wpdb->prepare("SELECT blog_id FROM `$wpdb->blogs`")
			);

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog( (int)$id );
				self::_install_backend();
			}

			/* Wechsel zurück */
			restore_current_blog();

		} else {
			self::_install_backend();
		}
	}


	/**
	* Installation des Plugins bei einem neuen MU-Blog
	*
	* @since   0.6
	* @change  0.6
	*/

	public static function install_later($id) {
		/* Kein Netzwerk-Plugin */
		if ( !is_plugin_active_for_network(self::$base) ) {
			return;
		}

		/* Wechsel */
		switch_to_blog( (int)$id );

		/* Installieren */
		self::_install_backend();

		/* Wechsel zurück */
		restore_current_blog();
	}


	/**
	* Eigentliche Installation der Option und der Tabelle
	*
	* @since   0.6
	* @change  0.6
	*/

	private static function _install_backend()
	{
		/* Option */
		add_option(
			'statify',
			array(),
			'',
			'no'
		);

		/* Reset */
		delete_transient('statify');

		/* Tabelle setzen */
		Statify_Table::init();

		/* Tabelle anlegen */
		Statify_Table::create();
	}


	/**
	* Uninstallation des Plugins pro MU-Blog
	*
	* @since   0.6
	* @change  0.6
	*/

	public static function uninstall()
	{
		/* Global */
		global $wpdb;

		/* Multisite & Network */
		if ( is_multisite() && !empty($_GET['networkwide']) ) {
			/* Alter Blog */
			$old = $wpdb->blogid;

			/* Blog-IDs */
			$ids = $wpdb->get_col(
				$wpdb->prepare("SELECT blog_id FROM `$wpdb->blogs`")
			);

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog($id);
				self::_uninstall_backend();
			}

			/* Wechsel zurück */
			switch_to_blog($old);
		} else {
			self::_uninstall_backend();
		}
	}


	/**
	* Uninstallation des Plugins bei MU & Network-Plugin
	*
	* @since   0.6
	* @change  0.6
	*/

	public static function uninstall_later($id) {
		/* Kein Netzwerk-Plugin */
		if ( !is_plugin_active_for_network(self::$base) ) {
			return;
		}

		/* Wechsel */
		switch_to_blog( (int)$id );

		/* Installieren */
		self::_uninstall_backend();

		/* Wechsel zurück */
		restore_current_blog();
	}


	/**
	* Eigentliche Deinstallation des Plugins
	*
	* @since   0.6
	* @change  0.6
	*/

	private static function _uninstall_backend()
	{
		/* Option */
		delete_option('statify');

		/* Transient */
		delete_transient('statify');

		/* Tabelle setzen */
		Statify_Table::init();

		/* Tabelle anlegen */
		Statify_Table::drop();
	}


	/**
	* Update des Plugins
	*
	* @since   0.6
	* @change  0.6
	*/

	public static function update()
	{
		/* Updaten */
		self::_update_backend();
	}
	
	
	/**
	* Eigentlicher Update des Plugins
	*
	* @since   0.6
	* @change  0.6
	*/

	private static function _update_backend()
	{
		/* Transient */
		delete_transient('statify');
	}
	


	/**
	* Speicherung der Werte in die DB
	*
	* @since   0.1
	* @change  0.6
	*/

	public static function db_push()
	{
		/* Filter */
		if ( is_feed() or is_trackback() or is_robots() or is_preview() or is_user_logged_in() or is_404() or self::_is_bot() ) {
			return;
		}

		/* Global */
		global $wpdb, $wp_rewrite;

		/* Init */
		$data = array();
		$home = home_url();

		/* Timestamp */
		$data['created'] = strftime('%Y-%m-%d', current_time('timestamp'));

		/* Referrer */
		if ( !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $home) === false ) {
			$data['referrer'] = esc_url_raw($_SERVER['HTTP_REFERER']);
		}

		/* Referrer */
		$data['target'] = str_replace(
			$home,
			'',
			esc_url_raw(
				home_url(
					( empty($_SERVER['REQUEST_URI']) ? '/' : $_SERVER['REQUEST_URI'] )
				)
			)
		);
		
		/* Parameter entfernen */
		if ( $wp_rewrite->permalink_structure && !is_search() ) {
			$data['target'] = preg_replace('/\?.*/', '', $data['target']);
		}

		/* Insert */
		$wpdb->insert(
			$wpdb->statify,
			$data
		);
	}


	/**
	* Prüfung auf Bots
	*
	* @since   0.1
	* @change  0.6
	*
	* @return  boolean  TRUE, wenn Bot
	*/

	private static function _is_bot()
	{
		/* Leer? */
		if ( empty($_SERVER['HTTP_USER_AGENT']) ) {
			return true;
		}

		/* Parsen */
		if ( !preg_match('/(?:Windows|Mac OS X|Macintosh|Linux)/', $_SERVER['HTTP_USER_AGENT']) ) {
			return true;
		}

		return false;
	}


	/**
	* Bereinigung der veralteten Werte
	*
	* @since   0.3
	* @change  0.6
	*/

	private static function _clean_data()
	{
		/* Überspringen? */
	    if ( get_transient('statify_cron') ) {
	    	return;
	    }
	
	    /* Global */
	    global $wpdb;

		/* Optionen */
		$options = self::get_options();

		/* Löschen */
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$wpdb->statify` WHERE created <= SUBDATE(CURDATE(), %d)",
				$options['days']
			)
		);

   		/* DB optimieren */
		$wpdb->query(
			"OPTIMIZE TABLE `$wpdb->statify`"
		);

		/* Merken */
		set_transient(
			'statify_cron',
			'ilovesweta',
			60 * 60 * 12
		);
	}


	/**
	* Lesevorgang der Datenbank
	*
	* @since   0.1
	* @change  0.8
	*
	* @return  array  Array mit ausgelesenen Daten
	*/

	private static function _get_stats()
	{
		/* GLobal */
		global $wpdb;

		/* Optionen */
		$options = self::get_options();
		
		return array(
			'visits' => $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT(`created`, '%%d.%%m') as `date`, COUNT(`created`) as `count` FROM `$wpdb->statify` GROUP BY `created` ORDER BY `created` DESC LIMIT %d",
					$options['days']
				),
				ARRAY_A
			),
			'target' => $wpdb->get_results(
				$wpdb->prepare(
					sprintf(
						"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->statify` %s GROUP BY `target` ORDER BY `count` DESC LIMIT %d",
						( $options['today'] ? 'WHERE created = DATE(NOW())' : '' ),
						$options['limit']
					)
				),
				ARRAY_A
			),
			'referrer' => $wpdb->get_results(
				$wpdb->prepare(
					sprintf(
						"SELECT COUNT(`referrer`) as `count`, `referrer` as `url`, SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host` FROM `$wpdb->statify` WHERE `referrer` != '' %s GROUP BY `host` ORDER BY `count` DESC LIMIT %d",
						( $options['today'] ? 'AND created = DATE(NOW())' : '' ),
						$options['limit']
					)
				),
				ARRAY_A
			)
		);
	}


	/**
	* Verarbeitung und Caching der DB-Werte
	*
	* @since   0.1
	* @change  0.7
	*/

	private static function _prepare_stats()
	{
		/* Im Cache? */
		if ( ($stats = get_transient('statify')) && self::$stats = $stats ) {
			return;
		}

		/* Bereinigen */
		self::_clean_data();

		/* Daten holen */
		if ( !$stats = self::_get_stats() ) {
			return;
		}

		/* Zwischenspeichern */
		if ( !$visits = $stats['visits'] ) {
			return;
		}

		/* Heute? */
		if ( $visits[0]['date'] == date('d.m', current_time('timestamp')) ) {
			$visits[0]['date'] = 'Heute';
		}

		/* Init */
		$output = array(
			'created' => array(),
			'count' => array()
		);

		/* Zeilen loopen */
		foreach($visits as $item) {
			array_push($output['created'], $item['date']);
			array_push($output['count'], $item['count']);
		}

		/* Zusammenfassen */
		$stats['visits'] = array(
			'created' => implode(',', $output['created']),
			'count' => implode(',', $output['count'])
		);

		/* Cache */
		set_transient(
			'statify',
			$stats,
			60 * 15 // 15 Minuten
	    );

		/* Lokal speichern */
		self::$stats = $stats;
	}
}


/**
* Statify Table
*
* @since 0.6
*/

class Statify_Table
{


	/**
	* Definition der Tabelle
	*
	* @since   0.6
	* @change  0.6
	*/

	public function init()
	{
		/* Global */
		global $wpdb;

		/* Name */
		$table = 'statify';

		/* Als Array */
		$wpdb->tables[] = $table;

		/* Mit Prefix */
		$wpdb->$table = $wpdb->get_blog_prefix() . $table;
	}


	/**
	* Anlegen der Tabelle
	*
	* @since   0.6
	* @change  0.6
	*/

	public function create()
	{
		/* Global */
		global $wpdb;

		/* Existenz prüfen */
		if ( $wpdb->get_var("SHOW TABLES LIKE '$wpdb->statify'") == $wpdb->statify ) {
			return;
		}

		/* Einbinden */
		require_once(ABSPATH. 'wp-admin/includes/upgrade.php');

		/* Anlegen */
		dbDelta(
			"CREATE TABLE `$wpdb->statify` (
	  		`id` bigint(20) unsigned NOT NULL auto_increment,
			  `created` date NOT NULL default '0000-00-00',
			  `referrer` varchar(255) NOT NULL default '',
			  `target` varchar(255) NOT NULL default '',
			  PRIMARY KEY  (`id`),
			  KEY `referrer` (`referrer`),
			  KEY `target` (`target`),
			  KEY `created` (`created`)
			);"
		);
	}


	/**
	* Löschung der Tabelle
	*
	* @since   0.6
	* @change  0.6
	*/

	public function drop()
	{
		/* Global */
		global $wpdb;

		/* Remove */
		$wpdb->query("DROP TABLE IF EXISTS `$wpdb->statify`");
	}
}


/* Fire */
add_action(
	'plugins_loaded',
	array(
		'Statify',
		'init'
	)
);


/* Install */
register_activation_hook(
	__FILE__,
	array(
		'Statify',
		'install'
	)
);

/* Uninstall */
register_uninstall_hook(
	__FILE__,
	array(
		'Statify',
		'uninstall'
	)
);

/* Update */
if ( function_exists('register_update_hook') ) {
	register_update_hook(
		__FILE__,
		array(
			'Statify',
			'update'
		)
	);
}