<?php

// Enable sessions
session_start();

// Start a timer
$start_time = microtime(TRUE);

// Starts output buffering
ob_start();

// TODO: Check for a cached version of the requested page

// Create a token if one doesn't exist or has timed out
if ( !isset($_SESSION['token']) && $_SESSION['TTL']<=time() )
{
    $_SESSION['token'] = sha1(uniqid(mt_rand(), TRUE));
    $_SESSION['TTL'] = time()+36000; // Time out in 10 minutes
}

// Include configuration files
include_once CMS_PATH . 'config/config.inc.php';
include_once CMS_PATH . 'config/database.inc.php';
include_once CMS_PATH . 'config/menu.inc.php';
include_once CMS_PATH . 'config/admin.inc.php';

// Include core classes
include_once CMS_PATH . 'core/class.utilities.inc.php';
include_once CMS_PATH . 'core/class.adminutilities.inc.php';
include_once CMS_PATH . 'core/class.imagecontrol.inc.php';
include_once CMS_PATH . 'core/class.page.inc.php';

// FirePHP class for debugging (requires Firefox)
include_once CMS_PATH . 'debug/fb.php';

// Define site-wide constants
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

// URL Parsing - Read the URL and break it apart for processing
$url_array = Utilities::readUrl();

if ( !is_array($url_array) && file_exists($url_array) )
{
    require_once $url_array;
}

// Creates a database object
$dbo = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Creates the database tables if set to true
if(CREATE_DB === TRUE)
{
    AdminUtilities::buildDB($menuPages);
}

// Load the page attributes from the menu array
$menuPage = Utilities::getPageAttributes($menuPages, $url_array[0]);

// Check if the admin page is being accessed
if ( $url_array[0]=='admin' )
{
    $menuPage = array('display'=>'Administrative Controls', 'type'=>'admin');
}

// Check if the search page is being accessed
if ( $url_array[0]=='search' )
{
    $menuPage = array('display'=>'Search', 'type'=>'search');
}

// If the supplied URL doesn't match any menu items, direct to the 404 page
if ( $menuPage===FALSE )
{
    $menuPage = array('display'=>'Invalid URL', 'type'=>'missing');
}

// If the menu item has an index called "showFull" that's FALSE, use the default
if ( isset($menuPage['showFull']) && $menuPage['showFull']===FALSE )
{
    header("Location: /".DEFAULT_PAGE);
    exit;
}

// Create a new object for the correct page type
try
{
    $obj = new $menuPage['type']($dbo, $url_array);
}
catch ( Exception $e )
{
    FB::error($e);
    die( $e->getMessage() );
}

// Define an autoload function for classes
function __autoload($classname)
{
    // File names are always lowercase
    $class = strtolower($classname);

    // First, check if a plugin class exists
    if ( file_exists("assets/plugins/$class/ecms.$class.inc.php") )
    {
        $path = "assets/plugins/$class/ecms.$class.inc.php";
    }

    // If not, check the inc folder
    elseif ( file_exists(CMS_PATH . 'inc/class.' . $class . '.inc.php') )
    {
        $path = CMS_PATH . 'inc/class.' . $class . '.inc.php';
    }

    // As a last resort, check the core folder
    elseif ( file_exists(CMS_PATH . 'core/class.' . $class . '.inc.php') )
    {
        $path = CMS_PATH . 'core/class.' . $class . '.inc.php';
    }

    else
    {
        throw new Exception("That class does not exist.");
    }

    // Include the file
    require_once $path;
    FB::log($path, "Class File");
}

/*
 * Load the page title
 */
$title = $obj->getPageTitle($menuPage);

?>