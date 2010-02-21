<?php

/*
 * Initialize the session
 */
session_start();

/*
 * Includes configuration files
 */
include_once '../ennui-cms/config/config.inc.php';
include_once '../ennui-cms/config/database.inc.php';
include_once '../ennui-cms/config/menu.inc.php';
include_once '../ennui-cms/config/admin.inc.php';

/*
 * Define site-wide constants
 */
foreach($_CONSTANTS as $key=>$value)
{
	define($key, $value);
}

/*
 * Include the FirePHP class for debugging
 */
include_once '../ennui-cms/debug/fb.php';

/*
 * Handles debugging. If set to TRUE, displays all errors and enables logging 
 * through FirePHP.
 */
if( ACTIVATE_DEBUG_MODE===TRUE )
{
	ini_set("display_errors",1);
	ERROR_REPORTING(E_ALL);
	FB::setEnabled(TRUE);
	FB::warn("FirePHP logging enabled.");
}
else
{
	ini_set("display_errors",0);
	error_reporting(0);
	FB::setEnabled(FALSE);
}

/*
 * Includes core classes
 */
include_once '../ennui-cms/core/class.utilities.inc.php';
include_once '../ennui-cms/core/class.adminutilities.inc.php';
include_once '../ennui-cms/core/class.imagecontrol.inc.php';
include_once '../ennui-cms/core/class.page.inc.php';

/*
 * Creates a database object
 */
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

/*
 * Checks if the user is logged in
 */
AdminUtilities::isLoggedIn();

/*
 * Creates the database tables if set to true
 */
if(CREATE_DB === TRUE)
{
	AdminUtilities::buildDB($menuPages);
}

/*
 * URL Parsing - Read the URL and break it apart for processing
 */
$url_array = Utilities::readUrl();

// Build the Menu
$menu = Utilities::buildMenu($url_array, $menuPages);

/*
 * Load the page attributes from the menu array
 */
$menuPage = Utilities::getPageAttributes($menuPages, $url_array[0]);

/*
 * Check if the admin page is being accessed
 */
if ( $url_array[0]=='admin' )
{
	$menuPage = array('display'=>'Administrative Controls', 'type'=>'admin');
}

/*
 * If the supplied URL doesn't match any menu items, direct to the 404 page
 */
if ( $menuPage===FALSE )
{
	$menuPage = array('display'=>'Invalid URL', 'type'=>'missing');
}

/*
 * If the menu item has an index called "hide" that's TRUE, use the default
 */
if ( isset($menuPage['hide']) && $menuPage['hide']===TRUE )
{
	header("Location: /".DEFAULT_PAGE);
	exit;
}

// Build the Page Content
include_once '../ennui-cms/inc/class.'.$menuPage['type'].'.inc.php';
$obj = new $menuPage['type']($mysqli, $url_array);

$entry = $obj->displayPublic($url_array);

/*
 * This builds the content for the title tag. This should probably be moved to 
 * the Utilities class and cleaned up.
 * 
 * TODO Move to Utilities class
 */
$entrytitle = (isset($obj->url1)) ? ucfirst(urldecode($obj->url1)) . ' | ' : NULL;
$title = $entrytitle . $menuPage['display'] . ' | ' . SITE_TITLE;

?>