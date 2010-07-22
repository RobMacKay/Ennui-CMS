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
     * An array to store any loaded entries
     *
     * @var array   The entries
     */
    public $entries = array();

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
                    `entry_id`,`page_id`,`title`,`entry`,`excerpt`,`slug`,
                    `tags`,`extra`,`author`,`created`";

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
        $entry_id = '';
        $page_id = '';
        $title = NULL;
        $entry = NULL;
        $excerpt = NULL;
        $slug = "";
        $tags = NULL;
        $extra = array();

        $var_names = array('entry_id', 'page_id', 'title', 'entry', 'excerpt',
                'slug', 'tags', 'author', 'created');

        // Loop through the POST array and define all variables
        foreach ( $_POST as $key => $val )
        {
            if ( !in_array($key, $var_names) )
            {
                $extra[$key] = $val;
            }
            else if ( $key==="entry" || $key==="excerpt" )
            {
                $$key = $val;
            }
            else
            {
                // If it's not the body of the entry, escape all entities
                $$key = htmlentities($val, ENT_QUOTES);
            }
        }

        // If a slug wasn't set, save a URL version of the title
        $slug = empty($slug) ? Utilities::makeUrl($title) : $slug;

        // Store the author's name and a timestamp
        $author = $_SESSION['user']['name'];
        $created = time();

        // Set up the query to insert or update the entry
        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."entries`
                (" . self::ENTRY_FIELDS . "
                )
                VALUES
                (
                    :entry_id, :page_id, :title, :entry, :excerpt, :slug, :tags, 
                    :extra, :author, :created
                )
                ON DUPLICATE KEY UPDATE
                    `title`=:title,
                    `entry`=:entry,
                    `excerpt`=:excerpt,
                    `slug`=:slug,
                    `tags`=:tags,
                    `extra`=:extra;";

        try
        {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":entry_id", $entry_id, PDO::PARAM_INT);
            $stmt->bindParam(":page_id", $page, PDO::PARAM_INT);
            $stmt->bindParam(":title", $title, PDO::PARAM_STR);
            $stmt->bindParam(":entry", $entry, PDO::PARAM_STR);
            $stmt->bindParam(":excerpt", $excerpt, PDO::PARAM_STR);
            $stmt->bindParam(":slug", $slub, PDO::PARAM_STR);
            $stmt->bindParam(":tags", $tags, PDO::PARAM_STR);
            $stmt->bindParam(":extra", serialize($extra), PDO::PARAM_STR);
            $stmt->bindParam(":author", $author, PDO::PARAM_STR);
            $stmt->bindParam(":created", $created, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();

            return TRUE;
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }
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
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `entry_id`=:id
                LIMIT 1";

        // Check for a cached file
        $cache = Utilities::checkCache($sql.$id);
        if ( $cache!==FALSE && strlen($cache)>0 )
        {
            return $cache;
        }

        try
        {
            FB::log($sql);
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
                FROM `".DB_NAME."`.`".DB_PREFIX."entries`
                WHERE `page_id`= (
                        SELECT `page_id`
                        FROM `".DB_NAME."`.`".DB_PREFIX."pages`
                        WHERE `page_slug`=:page_slug
                    )
                ORDER BY $ord
                LIMIT $offset, $lim";

        // Check for a cached file
        $cache = Utilities::checkCache($sql.$this->url0);
        if ( $cache!==FALSE )
        {
            $this->entries = $cache;
            return;
        }

        try
        {
            // Execute the query and store the result
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":page_slug", $this->url0, PDO::PARAM_STR);
            $this->loadEntryArray($stmt);
        }
        catch ( Exception $e )
        {
            $this->_logException($e);
        }

        // Cache the data
        $file = Utilities::saveCache($sql.$this->url0, $this->entries);
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
        foreach ( $result as $entry )
        {
            $this->entries[] = new Entry(array_map('stripslashes', $entry));
        }
    }

    /**
     *
     * @param <type> $e
     */
    private function _logException($e)
    {
        Error::logException($e);
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
                    COLLATE ".DEFAULT_COLLATION.";";
    }

}
