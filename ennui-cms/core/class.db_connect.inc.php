<?php

/**
 * Creates and stores a database object
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2009 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
class DB_Connect
{
    /**
	 * Stores a database object
	 *
	 * @var object a database object
	 */
	protected $db;

	/**
	 * Accepts a database object and stores it for use or creates a new one
	 *
	 * @param object $dbo a database object
	 * @return void
	 */
	public function __construct($dbo=NULL)
	{
        /*
         * Checks for a DB object, and creates one if none are found
         */
        if ( is_object($db) )
        {
            $this->db = $db;
        }
        else
        {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
            $this->db = new PDO($dsn, DB_USER, DB_PASS);
        }
	}
}

?>
