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

//------------------------------------------------------------------------------
// PRIVATE METHODS
//------------------------------------------------------------------------------

    private function loadEntryArray($stmt)
    {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    }

}
