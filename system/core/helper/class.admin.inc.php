<?php

/**
 * Allows login/logout, as well as account creation and notification
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Admin extends Page
{
    public function display_public()
    {
        // See if the uer is logged in already
        if( AdminUtilities::check_clearance(1) )
        {
            // If so, send them to the index unless they're creating a new user
            if( $this->url1==='create' && AdminUtilities::check_clearance(2) )
            {
                return $this->_create_user_form();
            }
            else if( $this->url1==='logout' )
            {
                $this->logout();
                header( 'Location: /' );
                exit;
            }
            else
            {
                header( 'Location: /' );
                exit;
            }
        }

        // Check if this is a new user coming to verify an account
        else if( $this->url1==='verify' )
        {
            return $this->_verify_user_form();
        }

        // If none of the above are true, display the login form
        else
        {
            return $this->_display_login_form();
        }
    }

    private function _display_login_form()
    {
        if ( $this->url1=='error' )
        {
            $errTxt = "<span>There was an error logging you in. Please check "
                    ."your username and password and try again.</span>";
        }
        else
        {
            $errTxt = NULL;
        }

        try
        {
            // Create a new form object and set submission properties
            $form = new Form;
            $form->legend = 'Administrator Login';
            $form->page = 'admin';
            $form->action = 'user-login';

            // Set up input information
            $form->input_arr = array(
                array(
                    'name'=>'username',
                    'label'=>'Username'
                ),
                array(
                    'name'=>'password',
                    'label'=>'Password',
                    'type' => 'password'
                ),
                array(
                    'type' => 'submit',
                    'name' => 'form-submit',
                    'value' => 'Login'
                )
            );
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }

        return $errTxt.$form;
    }

    public function create_user($user, $email)
    {
        if( SIV::validate($_POST['email'], SIV::EMAIL) )
        {
            $vcode = sha1(uniqid(time(), TRUE));
            $email = $_POST['email'];
        }
        else
        {
            $email = htmlentities($_POST['email'], ENT_QUOTES);
            ECMS_Error::log_exception(
                    new Exception( "Invalid email address \"$email\".")
                );
        }
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."adminMgr`
                    (admin_u, admin_e, admin_v)
                VALUES (?, ?, ?)";
        if(FALSE !== $stmt = $this->mysqli->prepare($sql)) {
            $ver = sha1(time());
            $stmt->bind_param("sss", $user, $email, $ver);
            $stmt->execute();
            $stmt->close();
            $this->_send_verification_email($user, $email, $ver);
        }
    }

    private function _send_verification_email($admin_u, $admin_e, $admin_v)
    {
        $to = "$admin_u <$admin_e>";
        if(isset($_SESSION['admin_u'])) {
            $from = "{$_SESSION['admin_u']} <{$_SESSION['admin_e']}>";
        } else {
            $from = "Ennui Design <answers@ennuidesign.com>";
        }
        $subject = "[" . SITE_NAME . "] Please Verify Your Account";
        $mime_boundary = '_x'.sha1(time()).'x';
        $siteURL = SITE_URL;
        $siteName = SITE_NAME;
        $devEmail = DEV_EMAIL;

        $headers = <<<MESSAGE
From: $from
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary="==PHP-alt$mime_boundary"
MESSAGE;

        $msg = <<<EMAIL

--==PHP-alt$mime_boundary
Content-Type: text/plain; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit

You have a new account at $siteName!

This account will allow you to create, edit, and otherwise
manage content on $siteName.

To get started, please activate your account and choose a
password by following the link below.

Your Username: $admin_u

Activate your account: {$siteURL}admin/verify/$admin_v/

If you have any questions, please contact $_SESSION[admin_u] 
at $_SESSION[admin_e].

For technical questions and support, contact $devEmail.

--
This message was automatically generated at the request of $_SESSION[admin_u].

--==PHP-alt$mime_boundary
Content-Type: text/html; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit 
<html><body>
<h1>You have a new account at $siteName!</h1>
<p>This account will allow you to create, edit, and otherwise manage
content on $siteName.</p>
<p>To get started, please activate your account and choose a password
by following the link below.</p>
<h2>Your User Name: $admin_u</h2>
<h2><a href="{$siteURL}admin/verify/$admin_v/">Click to Activate Your Account</a></h2>
<p>If you have any questions, please contact $_SESSION[admin_u] at 
<a href="mailto:$_SESSION[admin_e]">$_SESSION[admin_e]</a>.</p>
<p>For technical questions and support, contact
<a href="mailto:$devEmail">$devEmail</a>.</p>
<p>--<br />
This message was automatically generated at the request of $_SESSION[admin_u].</p>
</body></html>
--==PHP-alt$mime_boundary--
EMAIL;

        return mail($to, $subject, $msg, $headers);
    }

    private function _create_user_form()
    {
        try
        {
            // Create a new form object and set submission properties
            $form = new Form;
            $form->legend = 'Create a New Administrator';
            $form->page = $this->url0;
            $form->action = 'user-create';

            // Set up input information
            $form->input_arr = array(
                    array(
                            'name'=>'email',
                            'label'=>'Email Address'
                        ),
                    array(
                            'name'=>'clearance',
                            'label'=>'Clearance',
                            'value' => '1'
                        ),
                    array(
                            'type' => 'submit',
                            'name' => 'form-submit',
                            'value' => 'Create User'
                        )
                );

            return $form;
        }
        catch( Exception $e )
        {
            Error::logException($e);
        }
    }

    private function _verify_user_form()
    {
        try
        {
            // Create a new form object and set submission properties
            $form = new Form;
            $form->legend = 'Activate Your Account';
            $form->page = $this->url0;
            $form->action = 'user-create';

            // Set up input information
            $form->input_arr = array(
                    array(
                            'name'=>'username',
                            'label'=>'Choose a Username (8-20 characters using '
                                        . 'only a-z, 0-9, -, and _)'
                        ),
                    array(
                            'name'=>'password',
                            'label'=>'Choose a Password',
                            'type' => 'password',
                            'id' => 'choose-password'
                        ),
                    array(
                            'name'=>'verify-password',
                            'label'=>'Verify Your Password',
                            'type' => 'password',
                            'id' => 'verify-password'
                        ),
                    array(
                            'type' => 'submit',
                            'name' => 'form-submit',
                            'value' => 'Activate'
                        )
                );

            return $form;
        }
        catch( Exception $e )
        {
            Error::logException($e);
        }
    }

    public function verify_user($p)
    {
        $newpass = $this->createSaltedHash($p['admin_p']);
        $verpass = $this->createSaltedHash($p['check_p'], $newpass);
        $admin_v = $p['admin_v'];

        if($newpass==$verpass) {
            $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."adminMgr`
                    SET admin_p=?
                    WHERE admin_v=?
                    LIMIT 1";
            if($stmt = $this->mysqli->prepare($sql)) {
                $stmt->bind_param("ss", $newpass, $admin_v);
                $stmt->execute();
                $stmt->close();
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function login(  )
    {
        // Sanitize the username and store the password for hashing
        if( SIV::validate($_POST['username'], SIV::USERNAME)===TRUE )
        {
            $username = $_POST['username'];
            $password = $_POST['password'];
        }
        else
        {
            return FALSE;
        }

        // Load user data that matches the supplied username
        $userdata = $this->get_user_data( $username );

        // Make sure a user was loaded before continuing
        if( array_key_exists('email', $userdata)
                || array_key_exists('password', $userdata)
                || array_key_exists('username', $userdata)
                || array_key_exists('display', $userdata)
                || array_key_exists('clearance', $userdata) )
        {
            // Extract password hash
            $db_pass = $userdata['password'];

            // Make sure the passwords match
            if( $db_pass===$this->createSaltedHash($password, $db_pass)
                    && AdminUtilities::check_session() )
            {
                // Save the user data in a session variable
                $_SESSION['user'] = array(
                        'name' => $userdata['display'],
                        'email' => $userdata['email'],
                        'clearance' => $userdata['clearance']
                    );

                // Set a cookie to store the username that expires in 30 days
                setcookie('username', $username, time()+2592000, '/');

                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }
        else
        {
            return FALSE;
        }
    }

    public function logout()
    {
        $_SESSION = NULL;
        return session_regenerate_id(TRUE);
    }
}
