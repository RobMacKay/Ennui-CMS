<?php

/**
 * A class to perform all common database actions
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
class DB_Actions extends DB_Connect
{

//------------------------------------------------------------------------------
// CLASS CONSTANTS
//------------------------------------------------------------------------------

    /**
     * A string containing all the fields available in the entry database
     *
     * NOTE: If this changes, the write function may need to be updated!
     *
     * @var string  The fields available in the database
     */
    const ENTRY_FIELDS = "
                    `id`,`page`,`title`,`subhead`,`body`,`img`,`imgcap`,`data1`,
                    `data2`,`data3`,`data4`,`data5`,`data6`,`data7`,`data8`,
                    `author`,`created`";

//------------------------------------------------------------------------------
// PUBLIC METHODS
//------------------------------------------------------------------------------



//------------------------------------------------------------------------------
// PROTECTED METHODS
//------------------------------------------------------------------------------

    /**
     * Calls the parent constructor to create a PDO object
     * 
     * @return void
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Writes data to the database; either updates or creates an entry
     *
     * @return bool        Returns true on success or false on error
     */
    public function write()
    {
        // Initialize all variables to prevent any notices
        $id = ''; $title = NULL; $subhead = NULL; $body = NULL; $imgcap = NULL;
        $data1 = NULL; $data2 = NULL; $data3 = NULL; $data4 = NULL;
        $data5 = NULL; $data6 = NULL; $data7 = NULL; $data8 = NULL;

        // Loop through the POST array and define all variables
        foreach ( $_POST as $key => $val )
        {
            if ( $key=="body" )
            {
                $$key = $val;
            }
            else
            {
                // If it's not the body of the entry, escape all entities
                $$key = htmlentities($val, ENT_QUOTES);
            }
        }

        // If a value wasn't passed for data6, save a URL version of the title
        $data6 = !empty($data6) ? UTILITIES::makeUrl($title) : $data6;

        // Checks for and processes the image and returns the file path
        $img = isset($_FILES['img']) ? $this->checkIMG($_FILES['img']) : NULL;
        if ( $img===FALSE )
        {
            $img = isset($stored_img) ? $stored_img : NULL;
        }

        /*
         * PDF uploads go through the data8 field. If the $_FILES superglobal
         * isn't set, handle the input as a string. Otherwise, process as a PDF
         *
         * TODO: Adjust this to accept more file types
         */
        if ( isset($_FILES['data8']) && $_FILES['data8']['size']>0 )
        {
            $data8check = $this->uploadPDF($_FILES['data8'],$title);
            $data8 = ($data8check===false) ? NULL : $data8;
        }

        // Store the author's name and a timestamp
        $author = $_SESSION['admin_u'];
        $created = time();

        // Set up the query to insert or update the entry
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                (" . self::ENTRY_FIELDS . "
                )
                VALUES
                (
                    `id`=:id, `page`=:page, `title`=:title, `subhead`=:subhead,
                    `body`=:body, `data1`=:data1, `data2`=:data2,
                    `data3`=:data3, `data4`=:data4, `data5`=:data5,
                    `data6`=:data6, `data7`=:data7, `data8`=:data8,
                    `author`=:author, `created`=:created
                )
                ON DUPLICATE KEY UPDATE
                    `id`=:id, `page`=:page, `title`=:title, `subhead`=:subhead,
                    `body`=:body, `data1`=:data1, `data2`=:data2,
                    `data3`=:data3, `data4`=:data4, `data5`=:data5,
                    `data6`=:data6, `data7`=:data7, `data8`=:data8
                LIMIT 1;";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":page", $page, PDO::PARAM_INT);
            $stmt->bindParam(":title", $title, PDO::PARAM_INT);
            $stmt->bindParam(":subhead", $subhead, PDO::PARAM_INT);
            $stmt->bindParam(":img", $img, PDO::PARAM_INT);
            $stmt->bindParam(":imgcap", $imgcap, PDO::PARAM_INT);
            $stmt->bindParam(":body", $body, PDO::PARAM_INT);
            $stmt->bindParam(":data1", $data1, PDO::PARAM_INT);
            $stmt->bindParam(":data2", $data2, PDO::PARAM_INT);
            $stmt->bindParam(":data3", $data3, PDO::PARAM_INT);
            $stmt->bindParam(":data4", $data4, PDO::PARAM_INT);
            $stmt->bindParam(":data5", $data5, PDO::PARAM_INT);
            $stmt->bindParam(":data6", $data6, PDO::PARAM_INT);
            $stmt->bindParam(":data7", $data7, PDO::PARAM_INT);
            $stmt->bindParam(":data8", $data8, PDO::PARAM_INT);
            $stmt->bindParam(":author", $author, PDO::PARAM_INT);
            $stmt->bindParam(":created", $created, PDO::PARAM_INT);
            return $this->loadEntryArray($stmt);
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }

        if ( $id )
        {
            $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                    SET
                        title=?, subhead=?, body=?, img=?, imgcap=?,
                        data1=?, data2=?, data3=?, data4=?,
                        data5=?, data6=?, data7=?, data8=?
                    WHERE id=?
                    LIMIT 1";
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param("sssssssssssssi",$title, $subhead, $body, $img,
                    $imgcap, $data1, $data2, $data3, $data4, $data5, $data6,
                    $data7, $data8, $id);
        }

        /*
         * Otherwise, save a new entry
         */
        else {
            $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                        (page, title, subhead, body, img, imgcap,
                        data1, data2, data3, data4, data5, data6, data7, data8,
                        author, created)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param("ssssssssssssssss", $page, $title, $subhead, $body,
                    $img, $imgcap, $data1, $data2, $data3, $data4, $data5, $data6,
                    $data7, $data8, $author, $created);
        }
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Removes an entry from the database
     *
     * @param int $id       The ID of the entry to delete
     * @return bool         Returns TRUE on success, FALSE on failure
     */
    public function delete($id)
    {
        //TODO: Add a confirmation step here
        $sql = "DELETE FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE id=:id
                LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    /**
     * Returns an entry by its ID
     *
     * @param int $id
     * @return array    The entry as an associative array
     */
    protected function getEntryById($id)
    {
        /*
         * Prepare the query and execute it
         */
        $sql = "SELECT " . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE id=:id
                LIMIT 1";

        // Check for a cached file
        $cache = Utilities::checkCache($sql.$id);
        if ( $cache!==FALSE && strlen($cache)>0 )
        {
            return $cache;
        }

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $this->loadEntryArray($stmt);
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }
    }

    /**
     * Retrieves an entry using its URL
     * @param string $url
     * @return array
     */
    protected function getEntryByUrl($url=NULL)
    {
        // Fails if no URL is supplied
        if ( !isset($url) )
        {
            throw new Exception("No URL supplied.");
        }

        // Prepare the query and execute it
        $sql = "SELECT" . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE `title` LIKE :title
                OR `data6`=:url
                LIMIT 1";

        // Check for a cached file
        $cache = Utilities::checkCache($sql.$url);
        if ( $cache!==FALSE && strlen($cache)>0 )
        {
            return $cache;
        }

        // Just in case the entry doesn't have a slug
        $title = '%' . urldecode($url) . '%';

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":title", $title, PDO::PARAM_STR);
            $stmt->bindParam(":url", $url, PDO::PARAM_STR);
            $data = $this->loadEntryArray($stmt);
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }

        // Cache the data
        $file = Utilities::saveCache($sql.$url, $data);

        return $data;
    }

    /**
     * Loads entries from the database by category
     *
     * @param string $category  The category by which to filter entries
     * @param int $limit        The query limit
     * @param int $offset       The query offset
     * @return array            An array of entries
     */
    protected function getEntriesByCategory($category, $limit=10, $offset=0)
    {
        /*
         * Prepare the query and execute it
         */
        $sql = "SELECT" . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                AND LOWER(data2) LIKE :category
                ORDER BY created DESC
                LIMIT $offset, $limit";

        // Check for a cached file
        $cache = Utilities::checkCache($sql.$url);
        if ( $cache!==FALSE && strlen($cache)>0 )
        {
            return $cache;
        }

        // Prepare the category for the query
        $cat = '%'.str_replace('-', ' ', $category).'%';

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":category", $cat, PDO::PARAM_STR);
            $data = $this->loadEntryArray($stmt);
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }

        // Cache the data
        $file = Utilities::saveCache($sql.$url, $data);

        return $data;
    }

    /**
     * Retrieves all values for the given page from the database
     *
     * @param int $offset
     * @param int $limit
     * @return array    A multi-dimensional array of entries
     */
    protected function getAllEntries($lim=10, $offset=0, $ord="`created` DESC")
    {
        // Prepare the statement and execute it
        $sql = "SELECT" . self::ENTRY_FIELDS . "
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE `page`=:page
                ORDER BY $ord
                LIMIT $offset, $lim";

        // Check for a cached file
        $cache = Utilities::checkCache($sql.$this->url0);
        if ( $cache!==FALSE )
        {
            return $cache;
        }

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":page", $this->url0, PDO::PARAM_STR);
            $data = $this->loadEntryArray($stmt);
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }

        // Cache the data
        $file = Utilities::saveCache($sql.$this->url0, $data);

        return $data;
    }

    protected function getEntryCountByCategory()
    {
        $param1 = $page = empty($this->url0) ? 'blog' : $this->url0;
        $url1 = empty($this->url1) ? 'category' : $this->url1;
        $param2 = empty($this->url2) ? 'recent' : $this->url2;
        $param2 = $param2!='recent' ? '%'.str_replace('-',' ',$param2).'%' : '%';
        $num = empty($this->url3) ? 1 : $this->url3;
        $sql = "SELECT
                    COUNT(`title`) AS theCount
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE `page`=:page
                AND `data2` LIKE :category";

        // Check for a cached file
        $cache = Utilities::checkCache($sql.$param1.$param2);
        if ( $cache!==FALSE )
        {
            return $cache;
        }

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":page", $param1, PDO::PARAM_STR);
            $stmt->bindParam(":category", $param2, PDO::PARAM_STR);
            $data = $this->loadEntryArray($stmt);
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }

        // Cache the data
        $file = Utilities::saveCache($sql.$param1.$param2, $data);

        return $data;
    }

    /**
     * Counts the number of entries matching a search term
     * 
     * @param string $search    The search term
     * @return int              The entry count 
     */
    protected function getEntryCountBySearch($search)
    {
        // Get rid of any non-word or space characters
        $clean = preg_replace('/[^\w\s]+/', '', $search);
        $query = htmlentities($clean, ENT_QUOTES);
        $keys = explode(' ', $query);
        $param2 = NULL;
        foreach ( $keys as $key )
        {
            $param2 .= empty($param2) ? "+$key" : " +$key";
        }
        $param1 = "%$query%";

        $sql = "SELECT
                    COUNT(`title`) AS theCount
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE `title` LIKE :title
                OR MATCH (`body`) AGAINST (:keys IN BOOLEAN MODE)";

        // Check for a cached file
        $cache = Utilities::checkCache($sql.$param1.$param2);
        if ( $cache!==FALSE )
        {
            return $cache;
        }

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":title", $param1, PDO::PARAM_STR);
            $stmt->bindParam(":keys", $param2, PDO::PARAM_STR);
            $data = $this->loadEntryArray($stmt);
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }

        // Cache the data
        $file = Utilities::saveCache($sql.$param1.$param2, $data);

        return $data;
    }

    /**
     * Creates pagination for pages with a lot of entries
     *
     * @param int $preview  The number of entries per page
     * @return string       The HTML markup for the pagination
     */
    protected function paginateEntries($preview=BLOG_PREVIEW_NUM)
    {
        if ( $this->url0=="search" )
        {
            $page = 'search';
            $link = $this->url1;
            $num = empty($this->url2) ? 1 : $this->url2;
            $c = array_shift(array_shift($this->getEntryCountByCategory()));
        }
        else
        {
            $param1 = $page = empty($this->url0) ? 'blog' : $this->url0;
            $url1 = empty($this->url1) ? 'category' : $this->url1;
            $url2 = empty($this->url2) ? 'recent' : $this->url2;
            $link = "$url1/$url2";
            $num = empty($this->url3) ? 1 : $this->url3;
            $c = array_shift(array_shift($this->getEntryCountByCategory()));
        }

        // How many pages shown adjacent to current page
        $span = ENTRY_PAGINATION_SPAN;

        $pagination = "<ul id=\"pagination\">";

        /*
         * Determine minimum and maximum page numbers
         */
        $pages = ceil($c/$preview);

        $prev_page = $num-1;
        if($num==1)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "
                <li>
                    <a href=\"/$page/$link/1/\">&#171;</a>
                </li>
                <li>
                    <a href=\"/$page/$link/$prev_page/\">&#139;</a>
                </li>";
        }

        // Determine the page boundaries
        $mod = ($span>$num) ? $span-$num : 0;
        $max_mod = ($num+$span>$pages) ? $span-($pages-$num) : 0;
        $max = ($num+$span<=$pages) ? $num+$span+$mod : $pages;
        $max_num = ($max>$pages) ? $pages : $max;
        $min = ($max_num>$span*2) ? $num-$span-$max_mod : 1;
        $min_num = ($min<1) ? 1 : $min;

        for($i=$min_num; $i<=$max_num; ++$i)
        {
            $sel = ($i==$num) ? ' class="selected"' : NULL;
            $pagination .= "
                <li$sel>
                    <a href=\"/$page/$link/$i/\">$i</a>
                </li>";
        }

        $next_page = $num+1;
        if($next_page>$pages)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "
                <li>
                    <a href=\"/$page/$link/$next_page/\">&#155;</a>
                </li>
                <li>
                    <a href=\"/$page/$link/$pages/\">&#187;</a>
                </li>";
        }

        return $pagination . "
            </ul>\n";
    }

//------------------------------------------------------------------------------
// PRIVATE METHODS
//------------------------------------------------------------------------------

    private function loadEntryArray($stmt)
    {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        // Load the entries into a usable array
        $entries = array();
        foreach ( $result as $entry )
        {
            $entries[] = array_map('stripslashes', $entry);
        }
        return $entries;
    }

    /**
     *
     * @param <type> $e
     */
    private function _logException($e)
    {
        FB::log($e);
        die ( "PDO Statement Error: " . $e->getMessage() );
    }

//------------------------------------------------------------------------------
// STATIC METHODS
//------------------------------------------------------------------------------

    /**
     * Creates the database tables necessary for the CMS to function
     *
     * @param array $menuPages  The menu configuration array
     * @return void
     */
    static function buildDB($menuPages)
    {
        //TODO: Port this method for PDO
        $sql = "CREATE DATABASE IF NOT EXISTS `".DB_NAME."`
                DEFAULT CHARACTER SET ".DEFAULT_CHARACTER_SET."
                    COLLATE ".DEFAULT_COLLATION.";
                CREATE TABLE IF NOT EXISTS `".DB_NAME."`.`".DB_PREFIX."entries`
                (
                    `id`       INT UNSIGNED NOT NULL PRIMARY KEY auto_increment,
                    `page`     VARCHAR(30) NOT NULL,
                    `title`    VARCHAR(128) DEFAULT NULL,
                    `entry`    TEXT DEFAULT NULL,
                    `excerpt`  TEXT DEFAULT NULL,
                    `slug`     VARCHAR(128) NOT NULL,
                    `tags`     VARCHAR(150) DEFAULT NULL,
                    `extra`    TEXT DEFAULT NULL,
                    `author`   VARCHAR(64) DEFAULT '".SITE_CONTACT_NAME."',
                    `created`  INT(12),
                    INDEX(`page`),
                    INDEX(`created`),
                    INDEX(`title`),
                    INDEX(`slug`),
                    FULLTEXT(`entry`,`excerpt`)
                ) ENGINE=MYISAM CHARACTER SET ".DEFAULT_CHARACTER_SET."
                    COLLATE ".DEFAULT_COLLATION.";
    }

}
