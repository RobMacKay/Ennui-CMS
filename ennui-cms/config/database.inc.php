<?php

/*
 * Database credentials
 */
$_CONSTANTS['DB_HOST'] = 'localhost';
$_CONSTANTS['DB_USER'] = 'root';
$_CONSTANTS['DB_PASS'] = '';
$_CONSTANTS['DB_NAME'] = 'test';

/*
 * Assign a custom prefix for all database tables to avoid conflicts
 */
$_CONSTANTS['DB_PREFIX'] = 'ennui-cms_';

/*
 * If set to TRUE, ADMINUTILITIES::buildDB() is run, which creates the 
 * necessary tables in the database. IF NOT EXISTS is used to avoid accidental 
 * overwrites of data.
 */
$_CONSTANTS['CREATE_DB'] = TRUE;

?>