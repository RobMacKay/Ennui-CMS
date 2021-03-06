<?php

/**
 * Displays and manipulates comments for a given entry
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Comments extends AdminUtilities
{
    public $url0,$url1,$url2,$url3,$dbo;

    /**
     * Displays the unsubscribe dialog or redirects to the home page
     *
     * @param array $url_array
     * @return string the unsubscribe dialog
     */
    public function displayPublic($url_array)
    {
        list($this->url0, $this->url1, $this->url2, $this->url3) = $url_array;
        $this->dbo = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ( $this->url1 == 'unsubscribe' )
        {
            // This allows the user to unsubscribe from a comment stream
            return $this->unsubscribe();
        }
        else
        {
            /*
             * If the user isn't trying to unsubscribe, they shouldn't be on
             * the /comment/ extension, so bounce them out to the home page
             */
            header('Location: /');
            exit;
        }
    }

    /**
     * Outputs HTML to display comments and a submission form for new comments
     *
     * @param int $id
     * @return string    The markup to display
     */
    public function showEntryComments($id)
    {
        // If no ID is supplied, crash
        if ( !isset($id) )
        {
            throw new Exception("Cannot display comments without an entry ID.");
        }

        // Load existing comments
        $comments = $this->_formatEntryComments($id);

        return $comments;
    }

    /**
     * Formats all comments for the supplied entry
     *
     * @param int $id
     * @return string    The XHTML to display comments
     */
    private function _formatEntryComments($id)
    {
        /*
         * Load the comments for supplied entry as an array
         */
        $comments = $this->getEntryComments($id);

        if ( count($comments)>0 )
        {
            $comment_array = array();
            foreach ( $comments as $c )
            {
                $c['site-url'] = SITE_URL;
                $c['bid'] = $id;

                $e = $this->_getEntryTitleAndAuthor($id);
                $c['page'] = $e['page'];
                $c['url'] = $e['url'];

                /*
                 * Load a gravatar for users, or supply a default photo
                 */
                $c['email'] = stripslashes($c['email']);

                /*
                 * If no default gravatar was provided, uses the default
                 */
                $default = NULL;
                if( GRAVATAR_DEFAULT_IMG_URL != "" )
                {
                    $default = GRAVATAR_DEFAULT_IMG_URL;
                }

                $gravatar = new Gravatar($c['email'], $default);
                $gravatar->size = GRAVATAR_SIZE;
                $gravatar->rating = GRAVATAR_RATING;
                $gravatar->border = GRAVATAR_BORDER_COLOR;

                /*
                 * If the user is logged in, show comment editing links
                 */
                $c['admin'] = $this->admin_comment_options($id, $c['id'], $c['email']);

                /*
                 * If a link was supplied, make the commenter's gravatar and name clickable
                 */
                if(!empty($c['link'])) {
                    $c['link'] = 'http://' . str_replace('http://', '', $c['link']);
                    $c['image'] = "<a href=\"$c[link]\" rel=\"external\">$gravatar</a>";
                    $c['user'] = "<a href=\"$c[link]\" rel=\"external\">$c[user]</a>";
                } else {
                    $c['link'] = 'http://en.gravatar.com/';
                    $c['image'] = "<a href=\"$c[link]\" title=\"Get a Gravatar!\" rel=\"external\">$gravatar</a>";
                }

                /*
                 * Generate a date string, format the comment
                 */
                $c['date'] = date('h:iA \o\n F d, Y',stripslashes($c['timestamp']));
                $c['comment'] = stripslashes(nl2br($c['comment']));

                $comment_array[] = $c;
            }

            $template_file = 'comments.inc';
        }

        else
        {
            $comment_array[] = array();
            $template_file = 'comments-none.inc';
        }

        $extra = array(
                'footer' => array(
                        'comment-form' => $this->_formatCommentForm($id)
                    )
            );

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        return UTILITIES::parseTemplate($comment_array, $template, $extra);
    }

    /**
     * Retrieves an entry's comments from the blogCmnt table
     *
     * @param int $id    The entry id
     * @return array    A multi-dimensional array of comments
     */
    private function getEntryComments($id)
    {
        $sql = "SELECT id, bid, user, email, link, comment, timestamp, subscribe
                FROM `".DB_NAME."`.`".DB_PREFIX."blogCmnt`
                WHERE bid=?
                ORDER BY timestamp ASC
                LIMIT 2000";
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $c = array();
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($id, $bid, $user, $email, $link, $comment, $timestamp, $subscribe);
            while($stmt->fetch()) {
                /*
                 * Store the values in an associative multi-dimensional array
                 */
                $c[] = array(
                    'id' => $id,
                    'bid' => $bid,
                    'user' => htmlentities(strip_tags($user)),
                    'email' => htmlentities(strip_tags($email)),
                    'link' => htmlentities(strip_tags($link)),
                    'comment' => strip_tags($comment, "<em><bold>"),
                    'timestamp' => $timestamp,
                    'subscribe' => $subscribe
                );
            }
            $stmt->close();
        }
        $mysqli->close();

        return $c;
    }

    /**
     * Creates markup to output a comment form
     *
     * @param int $id
     * @return string    The XHTML markup
     */
    private function _formatCommentForm($id)
    {
        $c['bid'] = $id;

        // If the form was not filled out properly, supplies an error message
        $c['errortext'] = NULL;
        $c['text-err'] = NULL;
        $c['robot-err'] = NULL;
        if ( isset($_SESSION['cmnt_error']) )
        {
            if ( $_SESSION['cmnt_error']==1 )
            {
                $errtext = "You must fill out the required fields in order "
                    . "to post a comment!";
                $c['text-err'] = " err";
            }
            else if ( $_SESSION['cmnt_error']==2 )
            {
                $errtext = "You appear to be a robot. Please check to be sure "
                    . "you solved the math equation in the highlighted field "
                    . "below.";
                $c['robot-err'] = " err";
            }
            $c['errortext'] = '<div class="c_error">'.$errtext.'</div>';
        }

        /*
         * Checks first for an existing session value, then for cookies,
         * finally defaulting to an empty value. This is for convenience; if
         * the user makes an error on the form, their information is stored in
         * a session so they don't have to re-type their comment and info, and
         * name/email/website is stored in a cookie to save returning visitors
         * the trouble of retyping their info for each comment.
         */
        $c['name'] = $this->_checkStoredValues('cmnt_name');
        $c['email'] = $this->_checkStoredValues('cmnt_email');
        $c['link'] = $this->_checkStoredValues('cmnt_link');
        $c['text'] = $this->_checkStoredValues('cmnt_txt');

        /*
         * Because CAPTCHA is annoying, we're going to trust repeat visitors.
         * If they successfully posted a comment before, we'll replace the
         * CAPTCHA text input with a hidden input that will validate that
         * they're human. Might not be bulletproof, but it's convenient for
         * the user, and that seems more important
         */
        $challenge = $this->_generateChallenge($c['robot-err']);
        if ( isset($_COOKIE['cmnt_human']) && $_COOKIE['cmnt_human']==1 )
        {
            $c['challenge'] = '<input type="hidden" name="cmnt_human" value="'
                . $_SESSION['challenge'] . '" />';
        }
        else
        {
            $c['challenge'] = $challenge;
        }

        $c['token'] = $_SESSION['token'];

        $c['form-action'] = FORM_ACTION;

        $template_file = 'comments-form.inc';

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        return UTILITIES::parseTemplate(array($c), $template);
    }

    private function _checkStoredValues( $key )
    {
        if (isset($_SESSION[$key]))
        {
            return $_SESSION[$key];
        }
        else if ( isset($_COOKIE[$key]) )
        {
            return stripslashes($_COOKIE[$key]);
        }
        else
        {
            return NULL;
        }
    }

    private function _generateChallenge( $class=NULL )
    {
        // Store two random numbers in an array
        $numbers = array(mt_rand(1,4), mt_rand(1,4));

        // Store the correct answer in a session
        $_SESSION['challenge'] = $numbers[0] + $numbers[1];

        // Convert the numbers to their ASCII codes
        $converted = array_map('ord', $numbers);

        // Generate a math question as HTML markup
        return "
        <label for=\"cmnt_human\">&#87;&#104;&#97;&#116;&#32;&#105;&#115;&#32;"
            . "&#$converted[0];&#32;&#43;&#32;&#$converted[1];&#63;</label>
        <input type=\"text\" name=\"s_q\" id=\"cmnt_human\" "
            . "class=\"commentInput$class\" />";
    }

    private function _verifyResponse( $resp )
    {
        if( isset($_SESSION['challenge']) && $resp!='' )
        {
            // Grab the session value and destroy it
            $val = $_SESSION['challenge'];
            //unset($_SESSION['challenge']);

            // Returns TRUE if equal, FALSE otherwise
            return $resp==$val;
        }
        else
        {
            return FALSE;
        }
    }

    public function postComment()
    {
        /*
         * Set session variables
         */
        $_SESSION['cmnt_name'] = $_POST['cmnt_name'];
        $_SESSION['cmnt_email'] = $_POST['cmnt_email'];
        $_SESSION['cmnt_link'] = $_POST['cmnt_link'];
        $_SESSION['cmnt_txt'] = $_POST['cmnt_txt'];

        /*
         * Check if required fields are filled out properly
         */
        if ( $_POST['cmnt_name']==''
                || $_POST['cmnt_name']=='Name (Required)'
                || $_POST['cmnt_email']==''
                || $_POST['cmnt_email']=='Email Address (Required, Not Displayed)'
                || $_POST['cmnt_txt']==''
                || $_POST['cmnt_txt']=='Enter your comment here.' )
        {
            $error = 1;
        }
        else if ( !isset($_COOKIE['cmnt_human'])
                && !$this->_verifyResponse($_POST['s_q']) )
        {
            $error = 2;
        }
        else
        {
            $error = 0;
        }

        /*
         * Load the author's name and title of the entry
         */
        $a_info = $this->_getEntryTitleAndAuthor($_POST['cmnt_bid']);
        $author = $a_info['author'];
        $title = stripslashes($a_info['title']);
        $link = !empty($a_info['url']) ? $a_info['url'] : urlencode($title);

        // Convert tags to HTML entities between <pre> tags
        $pattern = "/<(pre|tt)>(.+)<\/(pre|tt)>/i";
        function escapeTags($matches)
        {
            return "<tt>"
                . str_replace(" ", "&nbsp;", htmlentities($matches[2], ENT_QUOTES))
                . "</tt>";
        }

        $_POST['cmnt_txt'] = preg_replace_callback($pattern, 'escapeTags', $_POST['cmnt_txt']);
        if($error==0) {
            /*
             * Save the comment
             */
            $this->saveComment($_POST);

            /*
             * Set cookies
             */
            $expire = time()+2592000; // Set cookies to expire in 30 days
            setcookie('cmnt_name', $_POST['cmnt_name'], $expire, '/');
            setcookie('cmnt_email', $_POST['cmnt_email'], $expire, '/');
            setcookie('cmnt_link', $_POST['cmnt_link'], $expire, '/');
            setcookie('cmnt_human', 1, $expire, '/');

            /*
             * Pull the author email and comment subscribers
             */
            $author_email = $this->getAuthorEmail($author);
            $subscribers = $this->getSubscribers($_POST['cmnt_bid']);

            /*
             * Using the loaded info, send notification emails
             */
            if ( !$this->sendCommentNotification($_POST, $author, $author_email, $title, $_POST['cmnt_bid'], $subscribers) )
            {
                return "Location: /blog/$link/error/";
            }
            else
            {
                return "Location: /blog/$link/#comments";
            }
        }

        else
        {
            /*
             * If we found an error earlier, go back to the comment form and
             * display the corresponding error
             */
            $_SESSION['cmnt_error'] = $error;
            return "Location: /blog/$link/#cmnt_error";
        }
    }

    public function deleteComment($bid, $cmntid)
    {
        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $sql = "DELETE FROM `".DB_NAME."`.`".DB_PREFIX."blogCmnt` WHERE id=? LIMIT 1";
            if($stmt = $mysqli->prepare($sql))
            {
                $stmt->bind_param("i", $cmntid);
                $stmt->execute();
                $stmt->close();

                $sql = "SELECT title,data6 FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr` WHERE id=?";
                if($stmt = $mysqli->prepare($sql))
                {
                    $stmt->bind_param("i", $bid);
                    $stmt->execute();
                    $stmt->bind_result($title,$data6);
                    $stmt->fetch();
                    $urltitle = !empty($data6) ? $data6 : urlencode($title);
                    return "Location: /blog/$urltitle/#comments";
                }
                else
                {
                    return "Location: /blog/#error_title-err";
                }
            }
            else
            {
                return "Location: /blog/#error_delete-err";
            }
        }
        else
        {
            return "Location: /blog/";
        }
    }

    private function saveComment($p)
    {
        /*
         * Add a timestamp and check if the user subscribed
         */
        $p['timestamp'] = time();
        $p['subscribe'] = (isset($p['cmnt_sub'])) ? 1 : 0; // This is a check box input

        /*
         * Save the comment in the blogCmnt table
         */
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."blogCmnt`
                    (bid, user, email, link, comment, timestamp, subscribe)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("issssii", $p['cmnt_bid'], $p['cmnt_name'],
                    $p['cmnt_email'], $p['cmnt_link'],
                    strip_tags($p['cmnt_txt'], COMMENT_WHITELIST),
                    $p['timestamp'], $p['subscribe']);
            $stmt->execute();
            $stmt->close();
            unset($_SESSION['cmnt_name']);
            unset($_SESSION['cmnt_email']);
            unset($_SESSION['cmnt_link']);
            unset($_SESSION['cmnt_txt']);
            unset($_SESSION['cmnt_error']);
        } else {
            exit("Couldn't save the comment to the database.<br />\n".$mysqli->error);
        }
        $mysqli->close();

        return true;
    }

    private function getSubscribers($id)
    {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $subscribers = array();
        $sql = "SELECT user, email
                FROM `".DB_NAME."`.`".DB_PREFIX."blogCmnt`
                WHERE bid=?
                AND subscribe=1
                GROUP BY email";
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($user, $email);
            while($stmt->fetch()) {
                $subscribers[] = array(
                    'name' => $user,
                    'email' => $email
                );
            }
            $stmt->close();
        }
        $mysqli->close();

        return $subscribers;
    }

    private function _getEntryTitleAndAuthor($id)
    {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $info = NULL;
        $sql = "SELECT page, title, author, data6
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE id=?
                LIMIT 1";
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($page, $title, $author, $data6);
            while($stmt->fetch()) {
                $info = array(
                    'page' => $page,
                    'title' => $title,
                    'author' => $author,
                    'url' => $data6
                );
            }
            $stmt->close();
        }
        $mysqli->close();

        return $info;
    }

    private function getAuthorEmail($author)
    {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $info = NULL;
        $sql = "SELECT admin_e
                FROM `".DB_NAME."`.`".DB_PREFIX."adminMgr`
                WHERE admin_u=?
                LIMIT 1";
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $author);
            $stmt->execute();
            $stmt->bind_result($email);
            while($stmt->fetch()) {
                $info = $email;
            }
            $stmt->close();
        }
        $mysqli->close();

        return $info;
    }

    private function sendCommentNotification($c, $author, $author_email, $title, $id, $subs)
    {
        $site_fullname = SITE_NAME;
        $siteName = SITE_URL;
        $adminemail = SITE_CONTACT_EMAIL;
        $link = urlencode($title);
        $comment = stripslashes($c['cmnt_txt']);
        $admin_dup = FALSE; // Ensures the admin isn't notified twice

        /*
         * Create the message headers
         */
        $headers = <<<HEADERS
From: $site_fullname <$adminemail>
Content-Type: text/plain
HEADERS;

        /*
         * Message subject
         */
        $subject = "[$site_fullname] New Comment Posted on \"$title\"";

        /*
         * Format the message
         */
        $msg = <<<MESSAGE
$c[cmnt_name] posted a new comment on the blog entry "$title"
{$siteName}blog/$link/#comments


Comment:

$comment


Join the discussion! Reply to this comment here: 
{$siteName}blog/$link/#comments

--
$site_fullname
$adminemail


To stop receiving notifications for this entry: 
MESSAGE;

        foreach($subs as $s)
        {
            if($s['email']==$author_email)
            {
                $admin_dup = TRUE;
            }

            $name = (!empty($s['name'])) ? $s['name'].' ' : NULL;

            // Validate the email address to avoid server errors
            $pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$";
            if (eregi($pattern, $s['email']))
            {
                $email = "$name<$s[email]>";

                // Generate an unsubscribe link
                $u = "\n$siteName/comments/unsubscribe/$id/$s[email]";
            }
            else
            {
                continue;
            }

            if(isset($email)) {
                $flag = mail($email, $subject, $msg.$u, $headers);
            }
        }

        if(!$admin_dup && !empty($author_email))
        {
            $to = "$author <$author_email>";
            $flag = mail($to, $subject, $msg, $headers);
        }

        return $flag;
    }

    public function unsubscribe() {
        if ( $this->url1 == 'unsubscribe' ) {
            $bid = $this->url2;
            $bloginfo = $this->_getEntryTitleAndAuthor($bid);
            $blog_title = $bloginfo['title'];
            $email = $this->url3;
            $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."blogCmnt`
                    SET subscribe=0
                    WHERE email=?
                    AND bid=?";
            if($stmt = $this->dbo->prepare($sql))
            {
                $stmt->bind_param("si", $email, $bid);
                $stmt->execute();
                $stmt->close();

                $content = <<<SUCCESS_MSG

                <h1> You Have Unsubscribed </h1>
                <p>
                    You will no longer be notified when comments are
                    posted to the entry "$blog_title".
                </p>
                <p>
                    If you have any questions or if you
                    continue to get notifications, contact
                    <a href="mailto:answers@ennuidesign.com">answers@ennuidesign.com</a>
                    for further assistance.
                </p>
SUCCESS_MSG;
            } else {
                $content = <<<ERROR_MSG

                <h1> Uh-Oh </h1>
                <p>
                    Somewhere along the lines, something went wrong,
                    and we were unable to remove you from the mailing list.
                </p>
                <p>
                    Please try again, or contact
                    <a href="mailto:answers@ennuidesign.com">answers@ennuidesign.com</a>
                    for further assistance.
                </p>
ERROR_MSG;
            }
        } else {
            header('Location: /');
            exit;
        }

        return $content;
    }

    static function getCommentCount($blog_id)
    {
        $sql = "SELECT COUNT(id) AS theCount
                FROM `".DB_NAME."`.`".DB_PREFIX."blogCmnt`
                WHERE bid=?";
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $c = 0;
        if($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("i", $blog_id);
            $stmt->execute();
            $stmt->bind_result($count);
            while($stmt->fetch()) {
                /*
                 * Store the values in an associative multi-dimensional array
                 */
                $c = $count;
            }
            $stmt->close();
        }
        $mysqli->close();
        return $c;
    }
}

?>