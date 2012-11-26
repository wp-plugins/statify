<?php


/**
* Statify_XMLRPC
*
* @since 1.1
*/

class Statify_XMLRPC
{


	/**
	* Erweiterung der XMLRPC-Methode
	*
	* @since   1.1
	* @change  1.1
	*
	* @return  array  $methods  Array ohne Plugin-Callback
	* @return  array  $methods  Array mit Plugin-Callback
	*/
	
	public static function xmlrpc_methods($methods) {
		$methods['statify.getStats'] = array(
			__CLASS__,
			'xmlrpc_callback'
		);
		
		return $methods;
	}
	
	
	/**
	* Ausführung der XMLRPC-Anfrage
	*
	* @since   1.1
	* @change  1.1
	*
	* @param   array   $args  Array mit Parametern (Zugangsdaten)
	* @return  string         String mit Ergebnissen
	*/
	
	public static function xmlrpc_callback($args) {
		/* Keine Zugangsdaten? */
		if ( empty($args[0]) or empty($args[1]) ) {
			return '{"error": "Keine Zugangsdaten"}';
		}
		
		/* Nutzer einloggen */
		$user = wp_authenticate($args[0], $args[1]);

		/* Falsche Zugangsdaten */
		if ( !$user or is_wp_error($user) ) {
			return '{"error": "Falsche Zugangsdaten"}';
		}

		/* Berechtigung prüfen */
		if ( !user_can($user, 'level_2') ) {
			return '{"error": "Keine Berechtigung"}';
		}
		
		/* Leer? */
		if ( ! $data = Statify_Dashboard::get_stats() ) {
			return '{"error": "Keine Daten"}';
		}
		
		return json_encode($data['visits']);
	}
}