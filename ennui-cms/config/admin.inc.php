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
 * The SHA1 hash of the default administrator's password. This can be generated 
 * here: http://www.daveproxy.co.uk/tools/sha1_hash_generator.php
 *
 * NOTE: The default password is "admin"
 */
$_CONSTANTS['DEV_PASS'] = 'd033e22ae348aeb5660fc2140aec35850c4da997';

/*
 * If set to TRUE, FirePHP logging is enabled and error reporting is set to 
 * E_ALL.
 * 
 * NOTE: Be sure to turn off debug mode before your site goes live!
 */
$_CONSTANTS['ACTIVATE_DEBUG_MODE'] = TRUE;

?>