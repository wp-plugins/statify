<?php
/* Remove settings */
delete_option('statify');

/* Remove Table */
$GLOBALS['wpdb']->query("DROP TABLE IF EXISTS `" .$GLOBALS['wpdb']->prefix. "statify`");