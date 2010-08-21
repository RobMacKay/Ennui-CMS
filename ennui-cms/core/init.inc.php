<?php

// Enable sessions
session_start();

// Start a timer
$start_time = microtime(TRUE);

// Starts output buffering
ob_start();

// TODO: Check for a cached version of the requested page

// Include configuration files
include_once CMS_PATH . 'config/config.inc.php';
include_once CMS_PATH . 'config/database.inc.php';
include_once CMS_PATH . 'config/menu.inc.php';
include_once CMS_PATH . 'config/admin.inc.php';

// Include utility classes
include_once CMS_PATH . 'core/utils/class.utilities.inc.php';
include_once CMS_PATH . 'core/utils/class.error.inc.php';
include_once CMS_PATH . 'core/utils/class.siv.inc.php';

// Include database classes
include_once CMS_PATH . 'core/entries/class.db_connect.inc.php';
include_once CMS_PATH . 'core/entries/class.db_actions.inc.php';

// Include admin class
include_once CMS_PATH . 'core/admin/class.adminutilities.inc.php';

// Include entry classes
include_once CMS_PATH . 'core/entries/class.page.inc.php';
include_once CMS_PATH . 'core/entries/class.entry.inc.php';

// Include form classes
include_once CMS_PATH . 'core/forms/class.form.inc.php';
include_once CMS_PATH . 'core/forms/class.input.inc.php';

// Include image classes
include_once CMS_PATH . 'core/images/class.imagecontrol.inc.php';
include_once CMS_PATH . 'core/images/class.imagegallery.inc.php';

// FirePHP class for debugging (requires Firefox)
include_once CMS_PATH . 'core/debug/fb.php';

// Define site-wide constants
foreach($_CONSTANTS as $key=>$value)
{
    define($key, $value);
}

// Handles debugging. If TRUE, displays all errors and enables FirePHP logging
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

// Check for a valid session
if ( !AdminUtilities::checkSession() )
{
    FB::log("Session found.");
}
FB::log($_SESSION['ecms'], "Session Info");

// URL Parsing - Read the URL and break it apart for processing
$url_array = Utilities::readUrl();

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
    $obj = new $menuPage['type']($url_array);
}
catch ( Exception $e )
{
    FB::error($e);
    Error::logException($e);
}

// Load the page title
$title = $obj->getPageTitle($menuPage);

// Load main content
$content = $obj->displayPublic();

// Load the meta description
$description = $obj->getPageDescription();

// Define an autoload function for classes
function __autoload($classname)
{
    // File names are always lowercase
    $class = strtolower($classname);

    // First, check if a custom plugin exists
    if ( file_exists("assets/plugins/$class/ecms.$class.inc.php") )
    {
        $path = "assets/plugins/$class/ecms.$class.inc.php";
    }

    // If not, check the class folder
    elseif ( file_exists(CMS_PATH . 'class/class.' . $class . '.inc.php') )
    {
        $path = CMS_PATH . 'class/class.' . $class . '.inc.php';
    }

    else
    {
        throw new Exception("That class does not exist.");
    }

    // Include the file
    require_once $path;
    FB::log($path, "Class File Location");
}
