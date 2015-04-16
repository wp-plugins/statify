<?php


/* Quit */
defined('ABSPATH') OR exit;


/**
* Statify_Deactivate
*
* @since 1.4.0
*/

class Statify_Deactivate
{


	/**
	* Plugin deactivation actions
	*
	* @since   1.4.0
	* @change  1.4.0
	*/

	public static function init()
	{
		/* Delete transients */
		delete_transient('statify_data');

		/* Delete cron event */
		wp_clear_scheduled_hook('statify_cleanup');
	}
}