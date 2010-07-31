<?php
/**
 * Methods to validate various types of data
 *
 * SIEVE stands for Simple Input Encoding & Validation Effort. It aims to add an
 * easy-to-use sanitization and validation utility to any project.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class SIEVE
{
    /**
     * A regex for alphanumeric strings with spaces and hyphens
     *
     * @var string
     */
    const STRING = '/^[\w\s-]*$/';

    /**
     * A regex to validate email addresses
     * 
     * @var string
     */
    const EMAIL = '/^[\w-]+(\.[\w-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i';

    /**
     * A regular expression for alphanumeric strings with spaces and hyphens
     * 
     * @var string
     */
    const URL = '';

    /**
     * A regex to validate a 8-20 character alphanumeric w/hyphens username
     * 
     * @var string
     */
    const USERNAME = '/[\w-]{8,20}/';

    /**
     * A generic validation function
     *
     * @param string $string    String to be validated
     * @param string $pattern   Regular expression with which to validate input
     * @return bool             Whether or not the string validates
     */
    public static function validate( $string, $pattern=self::STRING)
    {
        return preg_match($pattern, $string)===1 ? TRUE : FALSE;
    }

    /**
     * Validates an email string, does NOT check mx records.
     *
     * @param str $email - The email adrress to validate.
     * @return bool
     *
     */
    public static function isValidEmail($email)
    {
        return self::validate($email, self::EMAIL);
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

}
