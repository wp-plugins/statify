<?php


/**
* Statify_Dashboard
*
* @since 1.1
*/

class Statify_Dashboard
{
	
	
	/**
	* Anzeige des Dashboard-Widgets
	*
	* @since   0.1
	* @change  1.1
	*/

	public static function init()
	{
		/* Filter */
		if ( !current_user_can('level_2') ) {
			return;
		}
		
		/* Version definieren */
		self::_define_version();

		/* Widget */
		wp_add_dashboard_widget(
			'statify_dashboard',
			'Statify',
			array(
				__CLASS__,
				'print_frontview'
			),
			array(
				__CLASS__,
				'print_backview'
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
	* @change  1.1
	*/

	public static function add_style()
	{
		/* CSS registrieren */
		wp_register_style(
			'statify',
			plugins_url('/css/dashboard.min.css', STATIFY_FILE),
	  		array(),
			STATIFY_VERSION
		);

	  	/* CSS ausgeben */
	  	wp_enqueue_style('statify');
	}


	/**
	* Ausgabe von JavaScript
	*
	* @since   0.1
	* @change  1.1
	*/

	public static function add_js() {
		/* Keine Statistiken? */
		if ( ! $data = self::get_stats() ) {
			return;
		}
		
		/* Edit-Modus? */
		if ( isset($_GET['edit']) && $_GET['edit'] === 'statify_dashboard' ) {
			return;
		}
		
		/* Registrieren */
		wp_register_script(
			'statify',
			plugins_url('/js/dashboard.min.js', STATIFY_FILE),
			array(),
			STATIFY_VERSION
		);
		wp_register_script(
			'google_jsapi',
			'https://www.google.com/jsapi',
			false
		);

		/* Einbinden */
		wp_enqueue_script('google_jsapi');
		wp_enqueue_script('statify');

		/* Übergeben */
		wp_localize_script(
			'statify',
			'statify',
			$data['visits']
		);
	}
	
	
	/**
	* Ausgabe der Frontseite
	*
	* @since   0.1
	* @change  1.1
	*/

	public static function print_frontview()
	{
		/* Statistiken */
		if ( $data = self::get_stats() ) { ?>
			<div id="statify_chart">
				<noscript>Zur Darstellung der Statistik wird JavaScript benötigt.</noscript>
			</div>
			
			<div class="table target">
				<p class="sub">Top Inhalte</p>
				
				<div>
					<table>
						<?php if ( $data['target'] ) { ?>
							<?php foreach ($data['target'] as $target) { ?>
								<tr class="first">
									<td class="b">
										<a href="<?php echo esc_url($target['url']) ?>" target="_blank"><?php echo intval($target['count']) ?></a>
									</td>
									<td class="last t">
										<a href="<?php echo home_url($target['url']) ?>" target="_blank"><?php echo esc_url($target['url']) ?></a>
									</td>
								</tr>
							<?php } ?>
						<?php } else { ?>
							<tr>
								<td>Keine</td>
							</tr>
						<?php } ?>
					</table>
				</div>
			</div>
			
			<div class="table referrer">
				<p class="sub">Top Referrer</p>
				
				<div>
					<table>
						<?php if ( $data['referrer'] ) { ?>
							<?php foreach ($data['referrer'] as $referrer) { ?>
								<tr class="first">
									<td class="first b">
										<a href="<?php echo esc_url($referrer['url']) ?>" target="_blank"><?php echo intval($referrer['count']) ?></a>
									</td>
									<td class="t">
										<a href="<?php echo esc_url($referrer['url']) ?>" target="_blank"><?php echo esc_url($referrer['host']) ?></a>
									</td>
								</tr>
							<?php } ?>
						<?php } else { ?>
							<tr>
								<td>Keine</td>
							</tr>
						<?php } ?>
					</table>
				</div>
			</div>
		<?php } else { ?>
			<p>Keine Daten.</p>
		<?php } ?>
	<?php }


	/**
	* Ausgabe der Backseite
	*
	* @since   0.4
	* @change  1.1
	*/

	public static function print_backview()
	{
		/* Rechte */
		if ( !current_user_can('manage_options') ) {
			return;
		}
		
		/* Speichern */
		if ( !empty($_POST['statify']) ) {
			/* Formular-Referer */
			check_admin_referer('_statify');

			/* Optionen speichern */
			Statify::set_options(
				array(
					'days'	  => (int)@$_POST['statify']['days'],
					'limit'	  => (int)@$_POST['statify']['limit'],
					'today'	  => (int)@$_POST['statify']['today'],
					'snippet' => (int)@$_POST['statify']['snippet']
				)
			);

			/* Internen Cache Leeren */
			delete_transient('statify');
			
			/* Cachify Cache leeren */
			if ( has_action('cachify_flush_cache') ) {
				do_action('cachify_flush_cache');
			}
		}

		/* Optionen */
		$options = Statify::get_options();

		/* Security */
		wp_nonce_field('_statify'); ?>

		<table class="form-table">
			<tr>
				<td>
					<select name="statify[days]" id="statify_days">
						<?php foreach( array(7, 10, 14, 20, 21, 28, 30) as $num ) { ?>
							<option <?php selected($options['days'], $num); ?>><?php echo $num; ?></option>
						<?php } ?>
					</select>
					<label for="statify_days">Anzahl der Tage für Statistiken</label>
				</td>
			</tr>
			<tr>
				<td>
					<select name="statify[limit]" id="statify_limit">
						<?php foreach( range(0, 12) as $num ) { ?>
							<option <?php selected($options['limit'], $num); ?>><?php echo $num; ?></option>
						<?php } ?>
					</select>
					<label for="statify_limit">Anzahl der Einträge in Listen</label>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" name="statify[today]" id="statify_today" value="1" <?php checked($options['today'], 1) ?> />
					<label for="statify_today">Referrer und Ziele nur vom aktuellen Tag</label>
				</td>
			</tr>
			<tr>
				<td>
					<input type="checkbox" name="statify[snippet]" id="statify_snippet" value="1" <?php checked($options['snippet'], 1) ?> />
					<label for="statify_snippet">Tracking via JavaScript-Snippet</label>
				</td>
			</tr>
		</table>
		
		<p class="meta-links">
			<a href="http://playground.ebiene.de/statify-wordpress-statistik/" target="_blank">Dokumentation</a><a href="https://flattr.com/donation/give/to/sergej.mueller" target="_blank">Flattr</a><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5RDDW9FEHGLG6" target="_blank">PayPal</a>
		</p>
	<?php }


	/**
	* Rückgabe der Statistiken
	*
	* @since   0.1
	* @change  1.1
	*
	* @return  array  $data  Array mit Statistiken
	*/
	
	public static function get_stats()
	{
		/* Auf Cache zugreifen */
		if ( $data = get_transient('statify') ) {
			return $data;
		}
		
		/* DB reinigen */
		self::_clean_data();
		
		/* Stats abrufen */
		$data = self::_prepare_stats();
		
		/* Merken */
		set_transient(
		   'statify',
		   $data,
		   60 * 4 // = 4 Minuten
		);
		
		return $data;
	}
	
	
	/**
	* Liest Daten aus der DB aus und bereitet diese vor
	*
	* @since   0.1
	* @change  1.1
	*
	* @return  array  $data  Array mit ausgelesenen Daten
	*/
	
	public static function _prepare_stats()
	{
		/* Nix in der DB? */
		if ( ! $data = self::_select_data() ) {
			return false;
		}
		
		/* Noch keine Daten? */
		if ( empty($data['visits']) ) {
			return false;
		}
		
		/* Heute? */
		if ( $data['visits'][0]['date'] == date('d.m', current_time('timestamp')) ) {
			$data['visits'][0]['date'] = 'Heute';
		}
		
		return $data;
	}
	
	
	/**
	* Statistiken aus der DB
	*
	* @since   0.1
	* @change  1.1
	*
	* @return  array  Array mit ausgelesenen Daten
	*/

	private static function _select_data()
	{
		/* GLobal */
		global $wpdb;

		/* Optionen */
		$options = Statify::get_options();
		
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
					"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->statify` " .( $options['today'] ? 'WHERE created = DATE(NOW())' : '' ). " GROUP BY `target` ORDER BY `count` DESC LIMIT %d",
					(int)$options['limit']
				),
				ARRAY_A
			),
			'referrer' => $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(`referrer`) as `count`, `referrer` as `url`, SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(LEADING 'www.' FROM(TRIM(LEADING 'https://' FROM TRIM(LEADING 'http://' FROM TRIM(`referrer`))))), '/', 1), ':', 1) as `host` FROM `$wpdb->statify` WHERE `referrer` != '' " .( $options['today'] ? 'AND created = DATE(NOW())' : '' ). " GROUP BY `host` ORDER BY `count` DESC LIMIT %d",
					(int)$options['limit']
				),
				ARRAY_A
			)
		);
	}
	
	
	/**
	* Bereinigung der veralteten Werte in der DB
	*
	* @since   0.3
	* @change  1.1
	*/

	private static function _clean_data()
	{
		/* Überspringen? */
	    if ( get_transient('statify_cron') ) {
	    	return;
	    }
	
	    /* Global */
	    global $wpdb;

		/* Löschen */
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$wpdb->statify` WHERE created <= SUBDATE(CURDATE(), %d)",
				Statify::get_option('days')
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
	* Plugin-Version als Konstante
	*
	* @since   1.1
	* @change  1.1
	*/
	
	private static function _define_version()
	{
		/* Auslesen */
		$meta = get_plugin_data(STATIFY_FILE);
		
		/* Zuweisen */
		define('STATIFY_VERSION', $meta['Version']);
	}
}