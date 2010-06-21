<?php

class AdminUtilities extends DB_Actions
{

    /**
     * Stores the length of the salt to be used in password hashing
     *
     * @var int
     */
    const SALT_LENGTH = 14;

    /**
     * Creates the opening and closing pieces of a form
     *
     * @param string $action
     * @param int $id
     * @param string $caption
     * @return array    The beginning and end of the form HTML in format:
     *                         'start' => Beginning of the form,
     *                         'end' => End of the form HTML + hidden elements
     */
    protected function createForm($action, $id=NULL, $caption=NULL, $showcap=TRUE)
    {
        /*
         * If an entry ID is supplied, change the heading to notify the user
         * that they are updating an entry. Otherwise, tell them that they're
         * creating a new entry.
         */
        $default_caption = empty($id) ? "Create a New Entry" : "Update This Entry";

        /*
         * If a custom caption is supplied, update the variable
         */
        $form_cap = (is_null($caption)) ? $default_caption : $caption;

        /*
         * If the $showcap variable is set to true, display the form within
         * header tags.
         */
        if($showcap!==false) {
            $form_header = $form_cap;
        } else {
            $form_header = NULL;
        }

        /*
         * Instantiate the $form variable and load two array elements
         * consisting of the HTML to open the form and the HTML to close it
         */
        $form_action = FORM_ACTION;
        $form = array();
        $form['start'] = <<<FORM_START

<!-- BEGIN FORM DISPLAY -->
<form action="$form_action"
        method="post"
        enctype="multipart/form-data">
    <fieldset class="ennui_form">
        <legend>$form_header</legend>
FORM_START;

        $form['end'] = <<<FORM_END

        <input type="hidden" name="page" value="$this->url0" />
        <input type="hidden" name="action" value="$action" />
        <input type="hidden" name="id" value="$id" />
        <input type="hidden" name="token"
               value="$_SESSION[token]" />
        <input type="submit" name="confirm"
               value="$form_cap" />
    </fieldset>
</form>
<!-- END FORM DISPLAY -->
FORM_END;

        return $form;
    }

    /**
     * Creates a form input
     *
     * @param string $name
     * @param string $label
     * @param int $id
     * @param bool $nocap
     * @return string    The HTML for the field input
     */
    protected function createFormInput($name, $label=NULL, $id=NULL, $nocap=FALSE)
    {
        /*
         * If an entry ID is supplied, load the entry and grab the element
         * needed to populate the input
         */
        if ( !empty($id) )
        {
            $entry = Page::getEntryById($id);
            $data = htmlentities($entry[0][$name], ENT_QUOTES, 'UTF-8');
        }
        else
        {
            $entry = NULL;
            $data = NULL;
        }

        /*
         * Based on the value of $name, create the corresponding input type
         */
        switch($name) {
            case 'body':
                $input = <<<INPUT


          <textarea name="$name" id="$name">$data</textarea>
INPUT;
                break;

            case 'img':
                if($nocap===FALSE) {
                    $img_caption = $entry[0]['imgcap'];
                    $imgcap = <<<IMG_CAP


          <label for="imgcap">Image Caption:</label>
          <input type="text"
            name="imgcap"
            maxlength="75"
            value="$img_caption" />
IMG_CAP;
                } else {
                    $imgcap = NULL;
                }
                $input = <<<INPUT


          <label for="$name">$label:</label>
          <input type="file"
            name="$name" />
          <input type="hidden"
            name="stored_img"
            value="$data" />{$imgcap}
INPUT;
                break;

            case 'admin_v':
                $input = <<<INPUT


          <input type="hidden"
            name="$name"
            value="$label" />
INPUT;
                break;

            case 'admin_p':
            case 'check_p':
                $maxlength = 75;
                $input = <<<INPUT


          <label for="$name">$label:</label>
          <input type="password"
            name="$name"
            maxlength="$maxlength" />
INPUT;
                break;

            case 'title':
            case 'subhead':
            case 'data1':
            case 'data2':
            case 'data3':
            case 'data4':
            case 'data5':
            case 'data6':
            case 'data7':
            case 'data8':
            case 'admin_u':
            case 'admin_e':
                $maxlength = $name == 'title' ? 60 : $name == 'subhead' ? 75 : 150;

                $input = <<<INPUT


          <label for="$name">$label:</label>
          <input type="text"
            name="$name"
            maxlength="$maxlength"
            value="$data" />
INPUT;
                break;

            case 'data8_pdf':
                $input = <<<INPUT


          <label for="data8">$label:</label>
          <input type="file"
            name="data8" />
INPUT;
                break;

            default:
                $input = <<<INPUT


          <p>Invalid data type!</p>
INPUT;
                break;
        }

        return $input;
    }

    /**
     * Creates a form input
     *
     * @param string $name
     * @param string $label
     * @param int $id
     * @param bool $nocap
     * @return string    The HTML for the field input
     */
    protected function createFormCheckbox($name, $label=NULL, $id=NULL, $val=NULL)
    {
        /*
         * If an entry ID is supplied, load the entry and grab the element
         * needed to populate the input
         */
        $entry = !empty($id) ? Page::getEntryById($id) : NULL;
        $data = !empty($id) ? htmlentities($entry[0][$name], ENT_QUOTES) : NULL;

        $checked = $data==$val ? ' checked="checked"' : NULL;

        return '
            <label><input type="checkbox" name="' . $name . '" id="' . $name
            . '" value="' . $val . '"' . $checked . '/> ' . $label . '</label>';
    }

    protected function admin_general_options($page)
    {
        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            $form_action = FORM_ACTION;
            return <<<ADMIN_OPTIONS

<!--// BEGIN ADMIN OPTIONS //-->
<div class="admintopopts">
    <p>
        You are logged in as $_SESSION[admin_u].<br />
        [ <a href="/$page/admin" class="ecms-edit">create a new entry</a> |
        <a href="$form_action?action=logout">logout</a> ]
    </p>
</div>
<!--// END ADMIN OPTIONS //-->

ADMIN_OPTIONS;
        }
        else { return NULL; }
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
    You are logged in as {$_SESSION['admin_u']}.<br />
    [ <a href="/$page/admin/$id" class="ecms-edit">edit this entry</a>
    |$extra_options
    <a href="$form_action?action=logout">logout</a> ]
</div>
<!--// END ADMIN OPTIONS //-->

ADMIN_OPTIONS;
        }
        else
        {
            return NULL;
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
			return NULL;
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
        if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            return "
            [ <a href=\"mailto:$email\">email</a> |
            <a href=\"$form_action?action=cmnt_delete&bid=$bid&cmntid=$cid\"
                onclick=\"return confirm('Are you sure you want to delete this entry?')\">
                delete</a> ]";
        }
        else { return NULL; }
    }

    static function createSaltedHash($string, $salt=NULL)
    {
        /*
         * Generate a salt if no salt is passed
         */
        if ( $salt==NULL )
        {
            $salt = substr(md5(time()), 0, self::SALT_LENGTH);
        }

        /*
         * Extract the salt from the string if one is passed
         */
        else
        {
            $salt = substr($salt, 0, self::SALT_LENGTH);
        }

        /*
         * Add the salt to the hash and return it
         */
        return $salt . sha1($salt . $string);
    }

    static function buildDB($menuPages) {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if($mysqli->connect_errno) {
            exit("Couldn't connect to the database.".$mysqli->connect_error());
        }

        $admin_u = DEV_NAME;
        $admin_e = DEV_EMAIL;
        $admin_p = AdminUtilities::createSaltedHash(DEV_PASS);

        $sql = "CREATE DATABASE IF NOT EXISTS `".DB_NAME."`
                DEFAULT CHARACTER SET ".DEFAULT_CHARACTER_SET." COLLATE ".DEFAULT_COLLATION.";
                CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                (
                    `id`        INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
                    `page`        VARCHAR(30) NOT NULL,
                    `title`        VARCHAR(75) DEFAULT NULL,
                    `subhead`    VARCHAR(75) DEFAULT NULL,
                    `body`        TEXT DEFAULT NULL,
                    `img`        VARCHAR(75) DEFAULT NULL,
                    `imgcap`    VARCHAR(75) DEFAULT NULL,
                    `data1`        VARCHAR(150) DEFAULT NULL,
                    `data2`        VARCHAR(150) DEFAULT NULL,
                    `data3`        VARCHAR(150) DEFAULT NULL,
                    `data4`        VARCHAR(150) DEFAULT NULL,
                    `data5`        VARCHAR(150) DEFAULT NULL,
                    `data6`        VARCHAR(150) DEFAULT NULL,
                    `data7`        VARCHAR(150) DEFAULT NULL,
                    `data8`        VARCHAR(150) DEFAULT NULL,
                    `author`    VARCHAR(40) DEFAULT '".SITE_CONTACT_NAME."',
                    `created`    INT(12),
                    INDEX(page),
                    INDEX(created),
                    INDEX(title)
                ) ENGINE=MYISAM CHARACTER SET ".DEFAULT_CHARACTER_SET." COLLATE ".DEFAULT_COLLATION.";
                CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`".DB_PREFIX."imgCap`
                (
                    `photo_id`    VARCHAR(20) UNIQUE NOT NULL,
                    `album_id`    INT NOT NULL,
                    `photo_cap`    VARCHAR(150) DEFAULT NULL,
                    INDEX(album_id)
                ) ENGINE=MYISAM CHARACTER SET ".DEFAULT_CHARACTER_SET." COLLATE ".DEFAULT_COLLATION.";
                CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`".DB_PREFIX."adminMgr`
                (
                    `id`        INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
                    `admin_u`    VARCHAR(60) UNIQUE,
                    `admin_e`    VARCHAR(100) UNIQUE,
                    `admin_p`    VARCHAR(150) DEFAULT NULL,
                    `admin_v`    VARCHAR(150) NOT NULL,
                    `is_admin`    TINYINT(1) DEFAULT '0',
                    INDEX(admin_v)
                ) ENGINE=MYISAM CHARACTER SET ".DEFAULT_CHARACTER_SET." COLLATE ".DEFAULT_COLLATION.";
                INSERT INTO `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                    (`id`, `page`, `title`, `subhead`, `body`, `img`, `imgcap`,
                        `data1`, `data2`, `data3`, `data4`, `data5`, `data6`,
                        `data7`, `data8`, `author`, `created`)
                VALUES
                    (1, '".DEFAULT_PAGE."', 'Welcome to the Ennui CMS!', NULL,
                        '<p>You have successfully installed the Ennui CMS.</p>"
                        . "\r\n<p>To get started:</p>\r\n<ul>\r\n<li>"
                        . "<a href=\"/admin\">Log in</a> using the username "
                        . "and password you set up in the config files</li>\r\n"
                        . "<li>Edit this entry to contain the content for your "
                        . "site''s home page</li>\r\n<li>Add content to the "
                        . "rest of the pages on your site</li>\r\n</ul>\r\n"
                        . "<h2>HTML Element Style Test (h2)</h2>\r\n"
                        . "<blockquote>\r\n<p>This is a blockquote. Putamus "
                        . "lectores litterarum dynamicus facilisi dolore. "
                        . "Facilisi qui zzril legunt nibh in. Nostrud nonummy "
                        . "sequitur autem consequat ut. Assum tincidunt "
                        . "vulputate gothica molestie veniam.</p>\r\n"
                        . "</blockquote>\r\n<h3>H3 Element</h3>\r\n<p>Sed "
                        . "consequat tempor ex formas dignissim. Lobortis "
                        . "anteposuerit consectetuer consequat ullamcorper "
                        . "dolore. Dolore imperdiet amet iis sed iriure. "
                        . "Luptatum adipiscing lorem augue diam te. Cum autem "
                        . "claritas tempor sed augue.</p>\r\n<h4>H4 Element"
                        . "</h4>\r\n<ol>\r\n<li>This is an ordered list</li>"
                        . "\r\n<li>Typi at doming usus lectores parum.</li>"
                        . "\r\n<li>Parum quod legentis qui nonummy mirum. Nunc "
                        . "quis consequat in seacula consectetuer.</li>\r\n"
                        . "</ol>\r\n<h5>H5 Element</h5>\r\n<p>Parum quod "
                        . "legentis qui nonummy mirum. Nunc quis consequat in "
                        . "seacula consectetuer. Est humanitatis eros duis qui "
                        . "quarta. Enim quod in aliquip placerat insitam. "
                        . "Putamus consequat hendrerit demonstraverunt "
                        . "eleifend claram. Videntur molestie typi hendrerit "
                        . "duis qui.</p>\r\n<h6>H6 Element</h6>\r\n<p>Mazim ut "
                        . "euismod formas amet in. Ex blandit nulla tincidunt "
                        . "wisi consequat. Typi illum ad luptatum "
                        . "Investigationes legentis.</p>', NULL, NULL, NULL,
                        NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                        'Jason Lengstorf', 1261511658)
                ON DUPLICATE KEY UPDATE `created`=".time().";";

        if ( DEV_PASS!='' )
        {
            $sql .= "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."adminMgr`
                    (`admin_u`, `admin_e`, `admin_p`, `admin_v`, `is_admin`)
                VALUES
                    ('$admin_u', '$admin_e', '$admin_p', '".sha1(time())."', '1')
                ON DUPLICATE KEY UPDATE `is_admin`=1;";
        }

        if(array_key_exists('blog', $menuPages))
        {
            $sql .= "
                CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`".DB_PREFIX."blogCmnt`
                (
                    `id`        INT(5) PRIMARY KEY auto_increment,
                    `bid`        INT(5),
                    `user`        VARCHAR(60),
                    `email`        VARCHAR(100),
                    `link`        VARCHAR(100),
                    `comment`    TEXT,
                    `timestamp`    INT(12),
                    `subscribe`    TINYINT(1) DEFAULT '0',
                    INDEX(bid),
                    INDEX(timestamp),
                    INDEX(subscribe)
                ) ENGINE=MYISAM CHARACTER SET ".DEFAULT_CHARACTER_SET." COLLATE ".DEFAULT_COLLATION.";";
        }

        if($mysqli->multi_query($sql))
        {
            do {
                if($result=$mysqli->store_result())
                {
                    echo "Table created.<br />\n";
                    $result->close();
                }
            } while($mysqli->next_result());
        }
        else
        {
            exit('Database tables could not be created. '.$mysqli->error());
        }

        $mysqli->close();
        return true;
    }

}

?>