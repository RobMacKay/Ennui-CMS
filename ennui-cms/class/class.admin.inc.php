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
    public function displayPublic()
    {
        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            switch($this->url1) {
                case 'create':
                    return $this->createUserForm();
                    break;
                default:
                    header('Location: /');
                    break;
            }
        } else if($this->url1 == 'verify') {
            return $this->verifyUserForm();
        } else {
            return $this->loginForm();
        }
    }

    private function loginForm()
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
            $form = new Form(array('legend'=>'Administrator Login'));
            $form->page = 'admin';
            $form->action = 'user-login';

            // Set up input information
            $input_arr = array(
                array(
                    'name'=>'uname',
                    'label'=>'Username'
                ),
                array(
                    'name'=>'pword',
                    'label'=>'Password'
                ),
                array(
                    'type' => 'submit',
                    'name' => 'form-submit',
                    'value' => 'Login'
                )
            );

            // Build the inputs
            foreach ( $input_arr as $input )
            {
                $form->input($input);
            }
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }

        return $errTxt.$form;
    }

    public function createUser($user, $email)
    {
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."adminMgr`
                    (admin_u, admin_e, admin_v)
                VALUES (?, ?, ?)";
        if(FALSE !== $stmt = $this->mysqli->prepare($sql)) {
            $ver = sha1(time());
            $stmt->bind_param("sss", $user, $email, $ver);
            $stmt->execute();
            $stmt->close();
            $this->sendVerificationEmail($user, $email, $ver);
        }
    }

    private function sendVerificationEmail($admin_u, $admin_e, $admin_v)
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

    private function createUserForm()
    {
        $form = $this->createForm('create', '', 'Create an Administrator');

        $markup = $form['start'];
        $markup .= $this->createFormInput('admin_u', 'Administrator Name');
        $markup .= $this->createFormInput('admin_e', 'Administrator Email');
        $markup .= $form['end'];

        return $markup;
    }

    private function verifyUserForm()
    {
        $form = $this->createForm('verify', '', 'Activate Your Account');

        $markup = $form['start'];
        $markup .= $this->createFormInput('admin_p', 'Choose a Password');
        $markup .= $this->createFormInput('check_p', 'Verify Your Password');
        $markup .= $this->createFormInput('admin_v', $this->url2);
        $markup .= $form['end'];

        return $markup;
    }

    public function verifyUser($p)
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

    public function login($p)
    {
        $user = $_POST['admin_u'];
        $pass = $_POST['admin_p'];
        return $this->checkLogin($user, $pass);
    }

    private function checkLogin($user, $pass)
    {
        $sql = "SELECT admin_e, admin_p
                FROM `".DB_NAME."`.`".DB_PREFIX."adminMgr`
                WHERE admin_u=?
                LIMIT 1";
        try
        {

        }
        catch ( Exception $e )
        {

        }
        if($stmt = $this->mysqli->prepare($sql)) {
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $stmt->bind_result($admin_e, $admin_p);
            while($stmt->fetch()) {
                if($admin_p==$this->createSaltedHash($pass, $admin_p)) {
                    $_SESSION['user'] = array(
                            'name' => $user,
                            'email' => $admin_e,
                            'clearance' => 1 // TODO: Add clearance levels
                        );
                    $_SESSION['loggedIn'] = 1;
                    $_SESSION['admin_u'] = $user;
                    $_SESSION['admin_e'] = $admin_e;
                    $flag = true;
                } else {
                    $flag = false;
                }
            }
            $stmt->close();
        } else {
            $flag = false;
        }
        return $flag;
    }

    public function logout()
    {
        return session_regenerate_id(TRUE);
    }
}
