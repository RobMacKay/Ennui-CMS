<?php

class AdminUtilities extends DB_Actions
{

    /**
     * Stores the length of the salt to be used in password hashing
     *
     * @var int
     */
    const SALT_LENGTH = 14;

    protected function admin_general_options( $page )
    {
        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            $form_action = FORM_ACTION;
            return <<<ADMIN_OPTIONS

<!--// BEGIN ADMIN OPTIONS //-->
<div class="admintopopts">
    <p>
        You are logged in as {$_SESSION['user']['name']}.<br />
        [ <a href="/$page/admin" class="ecms-edit">create a new entry</a> |
        <a href="/admin/logout">logout</a> ]
    </p>
</div>
<!--// END ADMIN OPTIONS //-->

ADMIN_OPTIONS;
        }
        else { return ''; }
    }

    protected function admin_entry_options($page,$id,$dynamic=true)
    {
        if ( $dynamic === true ) {
            $extra_options = <<<EXTRA_OPTIONS

    <form action="" method="post">
        <a href="/$page/admin/$id" class="delete">delete this entry</a>
    </form>
    |
    <a href="/$page/admin" class="ecms-edit">create a new entry</a>
    |
EXTRA_OPTIONS;
        } else {
            $extra_options = NULL;
        }

        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            $form_action = FORM_ACTION;
            return <<<ADMIN_OPTIONS

<!--// BEGIN ADMIN OPTIONS //-->
<div class="admintopopts">
    You are logged in as {$_SESSION['user']['name']}.<br />
    [ <a href="/$page/admin/$id" class="ecms-edit">edit this entry</a>
    |$extra_options
    <a href="/admin/logout">logout</a> ]
</div>
<!--// END ADMIN OPTIONS //-->

ADMIN_OPTIONS;
        }
        else
        {
            return '';
        }
    }

    protected function admin_simple_options($page,$id)
    {
        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            return <<<ADMIN_OPTIONS

<span class="adminsimpleoptions">
    [
    <a href="/$page/admin/$id" class="ecms-edit">edit</a>
    |
    <a href="javascript:showedit('$page','deletepost','$id');"
        onclick="return confirm('Are you sure you want to delete this entry?\\n\\nClick OK to continue?');">delete</a>
    ]
</span>

ADMIN_OPTIONS;
		}
		else
		{
			return '';
		}
	}
	/**
	  * @TODO - Remove inline JS and attach event handlers instead.
	  */
	protected function admin_gallery_options($page, $id, $n, $i)
	{
		$dir = GAL_SAVE_DIR;
		if(isset($_SESSION['loggedIn']) && $_SESSION['loggedIn']==1)
		{
			if($i==1)
			{
				$up = "move up";
				$down = "<a href=\"javascript:reorderEntry('$this->url0', '$i','down','$id');\">move down</a>";
			}
			elseif($i==$n)
			{
				$up = "<a href=\"javascript:reorderEntry('$this->url0', '$i','up','$id');\">move up</a>";
				$down = "move down";
			}
			else
			{
				$up = "<a href=\"javascript:reorderEntry('$this->url0', '$i','up','$id');\">move up</a>";
				$down = "<a href=\"javascript:reorderEntry('$this->url0', '$i','down','$id');\">move down</a>";
			}

			return <<<ADMIN_OPTIONS

<span class="adminsimpleoptions">
    [
    <a href="/$page/admin/$id" class="ecms-edit">edit</a>
    |
    <a href="/$page/gallery-admin/$id" class="ecms-gallery">add photos</a>
    |
    $up
    |
    $down
    |
    <a href="javascript:showedit('$page','deletepost','$id');"
        onclick="return confirm('Are you sure you want to delete this entry?\\n\\nClick OK to continue.');">delete</a>
    ]
</span>

ADMIN_OPTIONS;
        }
        else { return NULL; }
    }

    protected function admin_comment_options( $bid, $cid, $email )
    {
        $form_action = FORM_ACTION;
        if ( $this->isLoggedIn() )
        {
            try
            {
                $config = array(
                            'legend'=>'',
                            'class'=>'admin-delete'
                        );
                $form = new Form($config);
                $form->action = "comment_delete";

                $form->input_arr = array(
                    array(
                        'name' => 'bid',
                        'type' => 'hidden',
                        'value' => $bid
                    ),
                    array(
                        'name' => 'cmntid',
                        'type' => 'hidden',
                        'value' => $cid
                    ),
                    array(
                        'name' => 'delete-submit',
                        'type' => 'submit',
                        'value' => 'delete'
                    )
                );

                return $form;
            }
            catch ( Exception $e )
            {
                ECMS_Error::log_exception($e);
            }
        }
        else
        {
            return '';
        }
    }

    /**
     * DEPRECATED: Checks the administrative clearance
     *
     * @deprecated  Use AdminUtilities::check_clearance() instead
     *
     * @param int   $clearance  Required clearance level
     * @return bool             Whether or not the user is logged in
     */
    protected function isLoggedIn($clearance=1)
    {
        return self::check_clearance($clearance);
    }

    /**
     * Checks for a valid session
     *
     * Runs a few checks to make sure the same user agent and IP are used in
     * addition to the check for a token and timeout. Any failure results in a
     * full-on self-destruct for the session.
     *
     * @return boolean  Whether or not a valid session is present
     */
    public static function check_session()
    {
        // Create a token if one doesn't exist or has timed out
        if ( !isset($_SESSION['ecms']) || $_SESSION['ecms']['ttl']<=time() )
        {
            $_SESSION['ecms'] = array(
                    'token' => uniqid('php-sess_', TRUE),
                    'ttl' => time()+600,
                    'address' => $_SERVER['REMOTE_ADDR'],
                    'user-agent' => $_SERVER['HTTP_USER_AGENT']
                );
            return TRUE;
        }

        // If user agent and/or IP don't match, assume hostility: harakiri
        else if ( $_SESSION['ecms']['user-agent']!==$_SERVER['HTTP_USER_AGENT']
                || $_SESSION['ecms']['address']!==$_SERVER['REMOTE_ADDR'] )
        {
            // Destroy the session to avoid fixation and other such nonsense
            session_regenerate_id(TRUE);
            return FALSE;
        }

        // If a valid session exists, update the timeout and return TRUE
        else if ( is_array($_SESSION['ecms']) )
        {
            $_SESSION['ecms']['ttl'] = time()+600;
            return TRUE;
        }

        // If none of the above conditions are met, something's screwy
        else
        {
            $_SESSION = NULL;
            session_regenerate_id(TRUE);
            return FALSE;
        }
    }

    /**
     * Checks if a user has a given clearance level
     *
     * @param int $clearance    The clearance level
     * @return boolean          Whether or not the user has clearance
     */
    public static function check_clearance( $clearance=1 )
    {
        // Check for a valid session, logged in user, and proper clearance
        if ( self::check_session()
                && isset($_SESSION['user'])
                && $_SESSION['user']['clearance']>=$clearance )
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * Generates a salted hash
     *
     * @param string $string    A string to hash
     * @param string $salt      An optional salted hash
     * @return string           The salted hash
     */
    public static function createSaltedHash($string, $salt=NULL)
    {
        // Generate a salt if no salt is passed
        if ( $salt==NULL )
        {
            $salt = substr(md5(time()), 0, self::SALT_LENGTH);
        }

        // Extract the salt from the string if one is passed
        else
        {
            $salt = substr($salt, 0, self::SALT_LENGTH);
        }

        // Add the salt to the hash and return it
        return $salt . sha1($salt . $string);
    }

}
