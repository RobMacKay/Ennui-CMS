<?php

require_once CMS_PATH . 'core/helper/class.gravatar.inc.php';

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
class Comments extends Page
{

    public $entries = array();

    private $_entry_id,
            $_sdata,
            $_challenges = array(
                                array(
                                        'q' => 'Is the sky red or blue?',
                                        'a' => 'blue'
                                    ),
                                array(
                                        'q' => 'Are bananas yellow or pink?',
                                        'a' => 'yellow'
                                    ),
                                array(
                                        'q' => 'Are rocks heavy or light?',
                                        'a' => 'heavy'
                                    )
                            );

    public function __construct()
    {
        parent::__construct();

        if( isset($_SESSION['comments']) && !is_object($_SESSION['comments']) )
        {
            $_SESSION['comments'] = new stdClass;
        }

        $this->_sdata =& $_SESSION['comments'];

        FB::log($_SESSION['comments'], "Comment Session Data");
    }

    public function display_entry_comments( $entry_id )
    {
        // Set the entry ID for which comments should be loaded
        $this->_entry_id = (int) $entry_id;

        // Load entry comments
        $this->entries = $this->get_comments_by_entry_id($this->_entry_id);

        // Set the comments template
        $this->template = 'comments.inc';

        if( count($this->entries)===0 )
        {
            $this->entries[0]->name = NULL;
            $this->entries[0]->email = NULL;
            $this->entries[0]->url = NULL;
            $this->entries[0]->created = time();
            $this->entries[0]->entry = "No comments yet!";

            $this->template = 'comments-none.inc';
        }

        // Format entry comments
        $this->generate_template_tags();

        // Load markup to display the "add a comment" form
        $extra['footer']->comment_form = $this->_display_comment_form();

        // Return both entry comment markup and the form
        return $this->generate_markup($extra);
    }

    /**
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function generate_template_tags(  )
    {
        // Add custom tags here
        foreach( $this->entries as $comment )
        {
            // Check for threaded comments
            $comment->threaded_replies = '';

            $threaded_comments = $this->get_comments_by_entry_id($this->_entry_id, $comment->comment_id);

            if( count($threaded_comments)>0 )
            {
                $fmt = "\n" . str_repeat(' ', 12);
                foreach( $threaded_comments as $threaded )
                {
                    $gravatar = new Gravatar($threaded->email);
                    $comment->threaded_replies .= $fmt
                            . '<div class="threaded-reply">' . $fmt . '    '
                            . '<p class="thread-comment">'
                            . $threaded->comment . '</p>' . $fmt . '    '
                            . '<p class="thread-author">' . $gravatar
                            . '<strong>' . $threaded->name . '</strong></p>'
                            . $fmt . '</div><!-- end .threaded-reply -->';
                }
            }

            // Generate a gravatar if the user has one
            $comment->gravatar = new Gravatar($comment->email);

            // Format the creation date
            $comment->date = date('g:ia M j, Y', $comment->created);

            // For the comment flagging form, store the form action and token
            $comment->form_action = FORM_ACTION;
            $comment->token = $_SESSION['ecms']['token'];

            // Generate admin options if the user is logged in
            $comment->admin = $this->_comment_admin_options(
                                                    $comment->comment_id,
                                                    $comment->email
                                                );

            if( !empty($comment->url) )
            {
                $comment->linked_name = '<a href="' . $comment->url
                        . '" rel="nofollow">' . $comment->name . '</a>';
            }
            else
            {
                $comment->linked_name = $comment->name;
            }
        }
    }

    private function _generate_spam_challenge(  )
    {
        $n = mt_rand(0, count($this->_challenges)-1);
        $this->_sdata->challenge = $n;

        return $this->_challenges[$n]['q'];
    }

    private function _verify_spam_challenge(  )
    {
        if( $this->_is_verified_human() )
        {
            return TRUE;
        }
        else
        {
            $given = strtolower(SIV::clean_output($_POST['challenge'], FALSE, FALSE));
            $actual = strtolower($this->_challenges[$this->_sdata->challenge]['a']);

            if( $given===$actual )
            {
                $this->_sdata->verified = 1;
                setcookie('ecms-comment:ishuman', 1, time()+2592000, '/');
                return TRUE;
            }
            else
            {
                unset($this->_sdata->verified);
                unset($_COOKIE['ecms-comment:ishuman']);
                return FALSE;
            }
        }
    }

    private function _is_verified_human(  )
    {
        if( (int) $_POST['challenge']===1
                && (int) $this->_sdata->verified===1
                && isset($_COOKIE['ecms-comment:ishuman'])
                && (int) $_COOKIE['ecms-comment:ishuman']===1 )
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    private function _delete_comment( $comment_id )
    {

    }

    private function _get_comment_data(  )
    {
        $comment = new stdClass;

        // If mid-comment, the data will be temporarily stored in the session
        if( isset($_SESSION['comments'])
                && property_exists($this->_sdata, 'temp')
                && is_object($this->_sdata->temp) )
        {
            $comment->name = $this->_sdata->temp->name;
            $comment->email = $this->_sdata->temp->email;
            $comment->url = $this->_sdata->temp->url;
            $comment->comment = $this->_sdata->temp->comment;
        }
        else if( isset($_COOKIE['ecms-comment:name']) )
        {
            $comment->name = $_COOKIE['ecms-comment:name'];
            $comment->email = $_COOKIE['ecms-comment:email'];
            $comment->url = isset($_COOKIE['ecms-comment:url']) ? $_COOKIE['ecms-comment:url'] : NULL;
        }

        return $comment;
    }

    private function _comment_admin_options( $comment_id, $commenter_email )
    {
        if( AdminUtilities::check_clearance(1) )
        {
            $fmt = "\n" . str_repeat(' ', 12);
            return $fmt . '<p class="comment-admin-options">' . $fmt . '    '
                    . '<a href="' . $_SERVER['REQUEST_URI']
                    . '?thread_id=' . $comment_id . '#add-comment" '
                    . 'class="reply-to-comment">reply</a>' . $fmt . '    '
                    . '<a href="' . FORM_ACTION
                    . '?page=comments&action=delete-comment&comment_id='
                    . $comment_id . '" class="delete-comment">delete</a>'
                    . $fmt . '    <a href="mailto:' . $commenter_email
                    . '">email commenter</a>' . $fmt . '    <a href="'
                    . FORM_ACTION
                    . '?page=comments&action=ban-commenter&comment_id='
                    . $comment_id . '" class="ban-commenter">ban commenter</a>'
                    . $fmt . '</p><!-- end .comment-admin-options -->';
        }
        else
        {
            return '';
        }
    }

    private function _display_comment_form(  )
    {
        $form = new Form;

        $form->page = 'comments';
        $form->legend = 'Add a Comment';
        $form->action = 'comment-write';
        $form->entry_id = $this->_entry_id;
        $form->form_id = 'add-comment';

        if( isset($this->_sdata->error)
                && $this->_sdata->error!=='0000' )
        {
            $form->notice = '<p class="comment-error">'
                    . $this->_get_comment_error_message() . '</p>';
        }

        // Make the entry values available to the form if they exist
        $form->entry = $this->_get_comment_data();

        // If the admin is trying to reply to a comment, add the thread ID
        if( AdminUtilities::check_clearance(1) && isset($_GET['thread_id']) )
        {
            $form->entry->thread_id = (int) $_GET['thread_id'];
        }

        // If the commenter is new and no cookies exist, do a spam challenge
        if( isset($_COOKIE['ecms-comment:ishuman'])
                && $_COOKIE['ecms-comment:ishuman']===1 )
        {
            $challenge = array(
                'name' => 'challenge',
                'type' => 'hidden',
                'value' => 1
            );
        }
        else
        {
            $challenge = array(
                'name'=>'challenge',
                'class'=>'input-text',
                'label'=>$this->_generate_spam_challenge()
            );
        }

        // Set up input information
        $form->input_arr = array(
            array(
                'name'=>'name',
                'class'=>'input-text',
                'label'=>'Your Name (Not Your Business Name)'
            ),
            array(
                'type'=>'email',
                'name'=>'email',
                'class'=>'input-text',
                'label'=>'Your Email (Required, Never Shared)'
            ),
            array(
                'name'=>'url',
                'class'=>'input-text',
                'label'=>'Your Website (Optional)'
            ),
            array(
                'type' => 'textarea',
                'name'=>'comment',
                'class'=>'input-textarea',
                'label'=>'Your Comment'
            ),
            array(
                'name'=>'challenge',
                'class'=>'input-text',
                'label'=>$this->_generate_spam_challenge()
            ),
            array(
                'type' => 'checkbox',
                'name'=>'subscribe',
                'id'=>'subscribe',
                'label'=>'Receive an email when new comments are posted',
                'value' => 1
            ),
            array(
                'type' => 'submit',
                'name' => 'comment-submit',
                'class'=>'input-submit',
                'value' => 'Post a Comment'
            ),
            array(
                'type' => 'hidden',
                'name' => 'comment_id'
            ),
            array(
                'type' => 'hidden',
                'name' => 'thread_id'
            ),
            array(
                'type' => 'hidden',
                'name' => 'return-url',
                'value' => $_SERVER['REQUEST_URI']
            )
        );

        return $form;
    }

    public function save_comment(  )
    {
        $comment = $this->_validate_comment_data();

        if( $comment===FALSE )
        {
            $loc = $_SERVER['HTTP_REFERER'];
            if( !strpos($loc, '#add-comment') )
            {
                $loc .= '#add-comment';
            }
            header('Location: ' . $loc);
            exit;
        }

        // Create a new comment entry, or update one if a comment_id is supplied
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."comments`
                (
                    `comment_id`, `entry_id`, `name`, `email`, `url`,
                    `remote_address`, `comment`, `thread_id`, `created`
                )
                VALUES
                (
                    :comment_id, :entry_id, :name, :email, :url,
                    :remote_address, :comment, :thread_id, :created
                )
                ON DUPLICATE KEY UPDATE
                    `name`=:name, `email`=:email, `url`=:url,
                    `comment`=:comment;";

        try
        {
            // Create a prepared statement
            $stmt = $this->db->prepare($sql);

            // Bind the query parameters
            $stmt->bindParam(":comment_id", $comment->comment_id, PDO::PARAM_INT);
            $stmt->bindParam(":entry_id", $comment->entry_id, PDO::PARAM_INT);
            $stmt->bindParam(":name", $comment->name, PDO::PARAM_STR);
            $stmt->bindParam(":email", $comment->email, PDO::PARAM_STR);
            $stmt->bindParam(":url", $comment->url, PDO::PARAM_STR);
            $stmt->bindParam(":remote_address", $comment->remote_address, PDO::PARAM_STR);
            $stmt->bindParam(":comment", $comment->comment, PDO::PARAM_STR);
            $stmt->bindParam(":thread_id", $comment->thread_id, PDO::PARAM_INT);
            $stmt->bindParam(":created", $comment->created, PDO::PARAM_INT);

            // Execute the statement and free the resources
            $stmt->execute();

            if( $stmt->errorCode()!=='00000' )
            {
                $err = $stmt->errorInfo();

                ECMS_Error::log_exception( new Exception($err[2]) );
            }
            else
            {
                $stmt->closeCursor();

                $comment_id = $this->db->lastInsertId();

                unset($this->_sdata->temp);

                $this->_suspend_commenter(15);
                $this->_sdata->verified = 1;

                $loc = explode('#', $_SERVER['HTTP_REFERER']);
                $loc[0] .= '#comment-' . $comment_id;
                header('Location: ' . $loc[0]);
                exit;
            }
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    private function _get_comment_error_message(  )
    {
        $error_codes = array(
                '0000' => NULL, // No errors
                '0001' => 'The "name" field is required in order to post '
                        . 'comments.',
                '0002' => 'The name you entered is not valid. Only letters and '
                        . 'numbers are allowed.',
                '0003' => 'The "email" field is required in order to post '
                        . 'comments.',
                '0004' => 'The email you entered is not valid.',
                '0005' => 'Please enter a comment before posting!',
                '0006' => 'Please answer the anti-spam question correctly '
                        . 'before posting.',
                '0007' => 'The URL you provided is not valid.',
                '0008' => 'Due to too many spam comments from this location, '
                        . 'you can no longer post comments.',
                '0009' => 'Too many failed attempts to answer the anti-spam '
                        . 'question. Try again in 2 minutes.',
                '0010' => 'You have posted 3 comments in two minutes. Slow '
                        . 'down, Turbo. Come back in 5 minutes.',
                '0011' => 'You can\'t post a comment for 2 minutes. Repeated '
                        . 'attempts will reset the timer. Please be patient.',
                '0012' => 'You can only post one comment every 15 seconds. '
                        . 'Repeated  attempts will reset the timer. Please be '
                        . 'patient.'
            );

        if( array_key_exists($this->_sdata->error, $error_codes) )
        {
            return $error_codes[$this->_sdata->error];
        }
        else
        {
            ECMS_Error::log_exception(
                    new Exception(
                            'Unknown comment error occurred using error code "'
                            . $this->_error_code . '".'
                        ),
                    FALSE
                );

            return 'An unknown error occurred.';
        }
    }

    private function _validate_comment_data(  )
    {
        // Sanitize the user input
        $comment = $this->_store_comment_data();

        // Verify that all required fields were properly filled out
        //----------------------------------------------------------------------
        // Name was left blank
        if( empty($comment->name) )
        {
            $this->_sdata->error = '0001';
            return FALSE;
        }

        // Name has disallowed characters
        else if( !SIV::validate($comment->name, SIV::STRING) )
        {
            $this->_sdata->error = '0002';
            return FALSE;
        }

        // Email was left blank
        else if( empty($comment->email))
        {
            $this->_sdata->error = '0003';
            return FALSE;
        }

        // Email is improperly formatted
        else if( !SIV::validate($comment->email, SIV::EMAIL) )
        {
            $this->_sdata->error = '0004';
            return FALSE;
        }

        // Comment area was left blank
        else if( empty($comment->comment) )
        {
            $this->_sdata->error = '0005';
            return FALSE;
        }

        // Verify that the anti-spam challenge was met
        else if( !$this->_verify_spam_challenge() )
        {
            if( $this->_track_post_attempts()<5 )
            {
                $this->_sdata->error = '0006';
            }
            else
            {
                $this->_suspend_commenter();
                $this->_sdata->attempts = 0;
                $this->_sdata->error = '0009';
            }
            return FALSE;
        }

        else if( $this->_sdata->suspend_until>time()
                && ( $this->_sdata->error==='0009'
                || $this->_sdata->error==='0011' ) )
        {
            $this->_suspend_commenter();
            $this->_sdata->error = '0011';
            return FALSE;
        }
        else if( $this->_sdata->suspend_until>time()
                && $this->_sdata->error==='0000'
                || $this->_sdata->error==='0012' )
        {
            $this->_suspend_commenter(15);
            $this->_sdata->error = '0012';
            return FALSE;
        }

        else
        {
            // Send the success code
            $this->_sdata->error = '0000';

            // Reset the comment attempts
            $this->_sdata->attempts = 0;

            return $comment;
        }
    }

    private function _store_comment_data(  )
    {
        $comment = new stdClass();

        // Create an object containing sanitized comment data
        $comment->comment_id = (int) $_POST['comment_id'];
        $comment->entry_id = (int) $_POST['entry_id'];
        $comment->name = SIV::clean_output($_POST['name'], FALSE, FALSE);
        $comment->email = SIV::clean_output($_POST['email'], FALSE, FALSE);
        $comment->url = SIV::clean_output($_POST['url'], FALSE, FALSE);
        $comment->comment = SIV::clean_output($_POST['comment'], FALSE);
        $comment->thread_id = (int) $_POST['thread_id'];
        $comment->remote_address = $_SERVER['REMOTE_ADDR'];
        $comment->created = time();

        $this->_sdata->temp = $comment;

        // Store user info in cookies to make posting easier
        $expires = time()+2592000; // Cookies to expire in 30 days
        setcookie('ecms-comment:name', $comment->name, $expires, '/');
        setcookie('ecms-comment:email', $comment->email, $expires, '/');
        setcookie('ecms-comment:url', $comment->url, $expires, '/');

        return $comment;
    }

    private function _track_post_attempts(  )
    {
        if( isset($this->_sdata->attempts) )
        {
            return ++$this->_sdata->attempts;
        }
        else
        {
            return $this->_sdata->attempts = 1;
        }
    }

    private function _suspend_commenter( $time_in_seconds=300 )
    {
        $this->_sdata->suspend_until = time()+$time_in_seconds;
    }

    public function confirm_flag_comment(  )
    {
        $comment_id = (int) $_GET['comment_id'];

        if( empty($comment_id) )
        {
            ECMS_Error::log_exception(new Exception("No comment ID supplied."));
        }

        else
        {
            $form = new Form;

            $form->legend = 'Flag This Comment';
            $form->form_id = 'modal-form';

            $form->page = 'comments';
            $form->action = 'comment-flag-confirmed';
            $form->entry_id = $this->_entry_id;

            $form->notice = '<p>Are you sure you want to flag this comment? '
                    . 'Please only flag comments that are abusive, spam, or '
                    . 'against the the comment guidelines for this site.</p>';

            // Set up input information
            $form->input_arr = array(
                array(
                    'type'=>'submit',
                    'name'=>'confirm-flag',
                    'class'=>'input-submit inline',
                    'value'=>'Flag This Comment'
                ),
                array(
                    'type'=>'submit',
                    'name'=>'cancel',
                    'class'=>'input-submit inline',
                    'value'=>'Cancel'
                ),
                array(
                    'type'=>'hidden',
                    'name'=>'comment_id',
                    'value'=>$comment_id
                )
            );

            return $form;
        }
    }

    public function flag_comment(  )
    {
        $comment_id = (int) $_POST['comment_id'];

        if( $comment_id===0 || empty($comment_id) )
        {
            ECMS_Error::log_exception(
                        new Exception("No comment ID supplied!")
                    );
        }

        // Flag
        $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."comments`
                SET `flagged`=1
                WHERE `comment_id`=:comment_id;";

        try
        {
            // Create a prepared statement
            $stmt = $this->db->prepare($sql);

            // Bind the query parameters
            $stmt->bindParam(":comment_id", $comment_id, PDO::PARAM_INT);

            // Execute the statement and free the resources
            $stmt->execute();

            // If the query fails, log the error message
            if( $stmt->errorCode()!=='00000' )
            {
                $err = $stmt->errorInfo();

                ECMS_Error::log_exception( new Exception($err[2]) );
            }

            $stmt->closeCursor();

            $loc = explode('#', $_SERVER['HTTP_REFERER']);
            $loc[0] .= '#comments';
            header('Location: ' . $loc[0]);
            exit;
        }
        catch( Exception $e )
        {
            ECMS_Error::log_exception($e);
        }
    }

    private function _track_comment_frequency(  )
    {
        // Log the comment in the session to try and stop spammers
        $_SESSION['ecms']['comments']['spam-check'] = NULL;
    }

    private function _clear_comment_data(  )
    {
        unset($this->_sdata->temp);
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
}
