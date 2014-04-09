<?php
/*
Plugin Name: Statify
Description: Kompakte, begreifliche und datenschutzkonforme Statistik für WordPress.
Author: Sergej M&uuml;ller
Author URI: http://wpcoder.de
Plugin URI: http://statify.de
Version: 1.2.7
*/


/* Quit */
defined('ABSPATH') OR exit;


/* Konstanten */
define('STATIFY_FILE', __FILE__);
define('STATIFY_BASE', plugin_basename(__FILE__));


/* Hooks */
add_action(
	'plugins_loaded',
	array(
		'Statify',
		'instance'
	)
);
register_activation_hook(
	__FILE__,
	array(
		'Statify_Install',
		'init'
	)
);
register_uninstall_hook(
	__FILE__,
	array(
		'Statify_Uninstall',
		'init'
	)
);


/* Autoload Init */
spl_autoload_register('statify_autoload');

/* Autoload Funktion */
function statify_autoload($class) {
	if ( in_array($class, array('Statify', 'Statify_Dashboard', 'Statify_Install', 'Statify_Uninstall', 'Statify_Table', 'Statify_XMLRPC')) ) {
		require_once(
			sprintf(
				'%s/inc/%s.class.php',
				dirname(__FILE__),
				strtolower($class)
			)
		);
	}
}