<?php
/**
 * Methods to validate various types of data.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
 class Validate extends Utilities
 {
 	public function __construct()
 	{
 		parent::__construct();
 	}
 	//------------------------------------------------------------------------------
	// PUBLIC METHODS
	//------------------------------------------------------------------------------
	
	/**
	  * Validates an email string, does NOT check mx records.
	  *
	  * @param str $email - The email adrress to validate.
	  * @return bool
	  *
	  */
	public static function isValidEmail($email)
    {
        // Define a regex pattern to validate the email address
        $pattern = "/^[\w-]+(\.[\w-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i";
        return preg_match($pattern, $email) === 1 ? TRUE : FALSE;
    }
    
    /**
      * Check if integer is even, also see isOdd()
      * Uses bit shifting instead of modulus for speed (and funsies).
      *
      * @param int integer to check 
      * @return bool 
      */
      public static function isEven($int)
      {
      	return !((int)$int&1) ? TRUE : FALSE;
      }
      
    /**
      * Check if integer is even, also see isEven()
      * Uses bit shifting instead of modulus for speed (and funsies).
      *
      * @param int integer to check 
      * @return bool 
      */
      public static function isOdd($int)
      {
      	return ((int)$int&1) ? TRUE : FALSE;
      }
	//------------------------------------------------------------------------------
	// PROTECTED METHODS
	//------------------------------------------------------------------------------
	
	//------------------------------------------------------------------------------
	// PRIVATE METHODS
	//------------------------------------------------------------------------------
 }