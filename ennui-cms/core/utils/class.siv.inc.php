<?php
/**
 * Methods to validate various types of data
 *
 * SIV stands for Simple Input Validation. It aims to add an
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
class SIV
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

    public static function clean_output( $str, $preserve_tags=TRUE, $preserve_newlines=TRUE )
    {
        // Standardize newlines for easier handling
        $str = str_replace(array("\r\n", "\r"), "\n", trim($str));

        if ( $preserve_tags===FALSE )
        {
            $str = strip_tags($str);
        }

        // Convert HTML entities
        $str = htmlentities($str, ENT_QUOTES, NULL, FALSE);

        // Fix MS Word weird characters and non-English characters
        $tr_tbl = array(
                chr(128) => "&#x80;", // €
                chr(133) => "&#x85;", // …
                chr(145) => "&#x91;", // ‘
                chr(146) => "&#x92;", // ’
                chr(147) => "&#x93;", // “
                chr(148) => "&#x94;", // ”
                chr(149) => "&#x95;", // •
                chr(150) => "&#x96;", // –
                chr(151) => "&#x97;", // —
                chr(153) => "&#x99;", // ™
                chr(169) => "&#xa9;", // ©
                chr(174) => "&#xae;" // ®
            );
        $str = strtr($str, $tr_tbl);

        // Remove newlines if the flag is set
        $pat = "/\n+/";
        $rep = $preserve_newlines===TRUE ? "/\n\n/" : '';
        $str = preg_replace($pat, $rep, trim($str));

        // Clean up post
        $pat = array(
                "/&lt;/",
                "/&gt;/",
                "/&quot;/",
                "/&nbsp;/",
                "/<(?:span|div)(?:.*?)>/",
                "/<\/(?:span|div)>/",
                "/<h1(?:.*?)>/",
                "/<\/h1>/",
                "/[^\w\s<>&'\"\/?#:;,\.=\-\[\]\(\)!%\$@\+\*\\\^~`|{}]/"
            );
        $rep = array(
                '<',
                '>',
                '"',
                ' ',
                '',
                '',
                '<h2>',
                '</h2>',
                ""
            );

        if ( $preserve_tags===TRUE )
        {
            $ptags_pat = array(
                    "/(?<!(?:[pe1-6]>))\n+(?!<[phb])/", // No closing or opening
                    "/(?<!(?:[pe1-6]>))\n+(?=<[phb])/is", // No closing, opening
                    "/(?<=(?:[pe1-6]>))\n+(?!<[phb])/is", // Closing, no opening
                    "/<blockquote.*?>\n+(?!<p)/", // Starting blockquote
                    "/(?<!p>)\n+<\/blockquote>/", // Closing blockquote
                    "/^(?!<[phb])/is", // Beginning of the string
                    "/(?<!(?:[pe1-6]>))$/is" // End of string
                );
            $ptags_rep = array(
                    "</p>\n\n<p>",
                    "</p>\n\n",
                    "\n\n<p>",
                    "<blockquote>\n<p>",
                    "</p>\n<blockquote>",
                    "<p>",
                    "$1</p>"
                );
            $pat = array_merge($pat, $ptags_pat);
            $rep = array_merge($rep, $ptags_rep);
        }

        $pat[] = "/\s+/";
        $rep[] = " ";
        return preg_replace($pat, $rep, $str);
    }

}
