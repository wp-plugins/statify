<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify_Install
*
* @since 0.1
*/

class Statify_Install
{


	/**
	* Installation auch für MU-Blog
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   integer  ID des Blogs [optional]
	*/

	public static function init($id)
	{
		/* Global */
		global $wpdb;

		/* Neuer MU-Blog */
		if ( ! empty($id) ) {
			/* Im Netzwerk? */
			if ( ! is_plugin_active_for_network(STATIFY_BASE) ) {
				return;
			}

			/* Wechsel */
			switch_to_blog( (int)$id );

			/* Installieren */
			self::_apply();

			/* Wechsel zurück */
			restore_current_blog();

			/* Raus */
			return;
		}

		/* Multisite & Network */
		if ( is_multisite() && ! empty($_GET['networkwide']) ) {
			/* Blog-IDs */
			$ids = $wpdb->get_col(
				$wpdb->prepare("SELECT blog_id FROM `$wpdb->blogs`")
			);

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog($id);
				self::_apply();
			}

			/* Wechsel zurück */
			restore_current_blog();

			/* Raus */
			return;
		}

		/* Single-Blog */
		self::_apply();
	}


	/**
	* Anlegen der Daten
	*
	* @since   0.1
	* @change  0.1
	*/

	private static function _apply()
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
}