<?php

/*
 * Enable sessions
 */
session_start();

/*
 * Create a token if one doesn't exist or has timed out
 */
if ( !isset($_SESSION['token']) && $_SESSION['TTL']<=time() )
{
    $_SESSION['token'] = sha1(uniqid(mt_rand(), TRUE));
    $_SESSION['TTL'] = time()+36000; // Time out in 10 minutes
}

/*
 * Include necessary files for execution
 */

// Configuration files
include_once CMS_PATH . 'config/config.inc.php';
include_once CMS_PATH . 'config/database.inc.php';
include_once CMS_PATH . 'config/menu.inc.php';
include_once CMS_PATH . 'config/admin.inc.php';

// Core classes
include_once CMS_PATH . 'core/class.utilities.inc.php';
include_once CMS_PATH . 'core/class.adminutilities.inc.php';
include_once CMS_PATH . 'core/class.imagecontrol.inc.php';
include_once CMS_PATH . 'core/class.page.inc.php';

// FirePHP class for debugging (requires Firefox)
include_once CMS_PATH . 'debug/fb.php';

/*
 * Define site-wide constants
 */
foreach($_CONSTANTS as $key=>$value)
{
    define($key, $value);
}

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
 * Creates a database object
 */
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

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
 * If the menu item has an index called "showFull" that's FALSE, use the default
 */
if ( isset($menuPage['showFull']) && $menuPage['showFull']===FALSE )
{
    header("Location: /".DEFAULT_PAGE);
    exit;
}

// Build the Page Content
include_once CMS_PATH . 'inc/class.'.$menuPage['type'].'.inc.php';
$obj = new $menuPage['type']($mysqli, $url_array);

$entry = $obj->displayPublic($url_array);

/*
 * Define an autoload function for classes
 */
function __autoload($classname)
{
    $file = CMS_PATH . 'inc/class.' . strtolower($classname) . '.inc.php';
    if ( file_exists($file) )
    {
        require_once $file;
    }
}

/*
 * Load the page title
 */
$title = $obj->getPageTitle($menuPage);

?>