<?php

/*
 * The default administrator is created using this information. No 
 * confirmation email is sent, and no activation is required; the username and
 * password can be used immediately to log into the site.
 *
 * NOTE: By default, the default administrator login name is "admin"
 */
$_CONSTANTS['DEV_NAME'] = 'admin';
$_CONSTANTS['DEV_EMAIL'] = 'admin@example.com';

/*
 * The desired password for the administrator.
 *
 * CAUTION: Make sure to clear this value after the database is built!
 */
$_CONSTANTS['DEV_PASS'] = 'admin';

/*
 * If set to TRUE, FirePHP logging is enabled and error reporting is set to 
 * E_ALL.
 * 
 * NOTE: Be sure to turn off debug mode before your site goes live!
 */
$_CONSTANTS['ACTIVATE_DEBUG_MODE'] = TRUE;

?>