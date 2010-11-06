<?php

if( ( ( isset($_POST['page']) && isset($_POST['token']) )
        || ( isset($_GET['page']) && isset($_GET['action']) ) )
        && AdminUtilities::check_session() )
{
    $action_lookup = array(
            'entry-write' => array(
                    'method' => 'save_entry'
                ),
            'entry-edit' => array(
                    'method' => 'display_admin'
                ),
            'user-login' => array(
                    'method' => 'login'
                ),
            'user-create' => array(
                    'method' => 'create_user'
                ),
            'menu-update' => array(
                    'method' => 'update_menu'
                ),
            'comment-write' => array(
                    'method' => 'save_comment'
                ),
            'comment-flag' => array(
                    'method' => 'confirm_flag_comment'
                ),
            'comment-flag-confirmed' => array(
                    'method' => 'flag_comment'
                )
        );

    if( array_key_exists($_REQUEST['action'], $action_lookup) )
    {
        $action = SIV::clean_output($_REQUEST['action'], FALSE, FALSE);
    }
    else
    {
        // Sanitize the action just in case
        $bad_action = SIV::clean_output($_REQUEST['action'], FALSE, FALSE);

        // Throw an exception and die
        ECMS_Error::log_exception(
                new Exception("Unsupported action $bad_action.")
            );
    }

    // Make sure the page conforms to the slug format
    if( SIV::validate($_REQUEST['page'], SIV::SLUG) )
    {
        $page = strtolower($_REQUEST['page']);
        $page_data = DB_Actions::get_page_data_by_slug( $page );
    }
    else
    {
        // Throw an exception and die
        ECMS_Error::log_exception(
                new Exception("Page \"$page\" isn't valid.")
            );
    }

    // The Admin class is a special case, and needs to be loaded manually
    if( $page==='admin' )
    {
        require_once CMS_PATH . 'core/helper/class.admin.inc.php';
        $class = 'Admin';
    }

    // The Menu class is a special case, and needs to be loaded manually
    else if( $page==='menu' )
    {
        $class = 'Menu';
    }

    // The Comments class is a special case, and needs to be loaded manually
    else if( $page==='comments' )
    {
        require_once CMS_PATH . 'core/helper/class.comments.inc.php';
        $class = 'Comments';
    }

    // All other classes will be loaded from the __autoload function
    else if( is_object($page_data) )
    {
        $class = $page_data->type;
        if( empty($class) )
        {
            // Throw an exception and die
            ECMS_Error::log_exception(
                    new Exception("Page \"$page\" doesn't actually exist.")
                );
        }
    }

    // Throw an exception if no supported class type was passed
    else
    {
        // Throw an exception and die
        ECMS_Error::log_exception(
                new Exception("Unsupported page type \"$page\" supplied.")
            );
    }

    // Create a new instance of the appropriate class
    $obj = new $class(array($page));

    // Extract and sanitize the entry ID if one was passed
    $id = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : NULL;

    // Call the appropriate method and store the return value
    $ret = $obj->$action_lookup[$action]['method']($id);

    // If this is an AJAX call, echo the output
    if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['action']) )
    {
        echo $ret;
        exit;
    }

    // Otherwise, direct the user back to the page on which they were working
    else
    {
        if( $ret===TRUE )
        {
            if( isset($_SERVER['HTTP_REFERER'])
                    && stripos($_SERVER['HTTP_REFERER'], SITE_URL) )
            {
                $loc = $_SERVER['HTTP_REFERER'];
            }
            else if( property_exists($obj, 'url0') )
            {
                $loc = '/'.$obj->url0;
            }
            else if( $class==='Comments' )
            {
                $loc = SIV::clean_output($_POST['return-url'], FALSE, FALSE);
            }
            else
            {
                $loc = '/';
            }

            header("Location: $loc");
            exit;
        }
        else
        {
            // Throw an exception and die
            ECMS_Error::log_exception(
                    new Exception("Action " 
                                . $action_lookup[$action]['method'] . " failed."
                            )
                );
        }
    }
}

// If no conditions have been met, something fishy is going on
else
{
    // Throw an exception and die
    ECMS_Error::log_exception(
            new Exception("An unknown error has occurred.\n")
        );
}
