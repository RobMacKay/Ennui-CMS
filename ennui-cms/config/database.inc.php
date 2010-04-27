<?php

/*
 * Database credentials
 */
$_CONSTANTS['DB_HOST'] = '';
$_CONSTANTS['DB_USER'] = '';
$_CONSTANTS['DB_PASS'] = '';
$_CONSTANTS['DB_NAME'] = '';

/*
 * Assign a custom prefix for all database tables to avoid conflicts
 */
$_CONSTANTS['DB_PREFIX'] = 'ennui-cms_';

/*
 * Assign a default character set and collation for the database
 */
$_CONSTANTS['DEFAULT_CHARACTER_SET'] = "utf8";
$_CONSTANTS['DEFAULT_COLLATION'] = "utf8_unicode_ci";

/*
 * If set to TRUE, ADMINUTILITIES::buildDB() is run, which creates the 
 * necessary tables in the database. IF NOT EXISTS is used to avoid accidental 
 * overwrites of data.
 */
$_CONSTANTS['CREATE_DB'] = TRUE;

?>
