<?php

/**
 * An error-handling class
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
class Error
{

    /**
     * Logs an exception
     *
     * @param object $exception_object  The Exception object
     * @param bool $is_fatal            Whether or not to stop execution
     * @return void
     */
    public static function logException( $exception_object, $is_fatal=TRUE )
    {
        if ( class_exists('FB') )
        {
            FB::log($exception_object);
        }

        // Generates an error message
        $trace = array_pop($exception_object->getTrace());
        $arg_str = implode(',', $trace[args]);
        $err = "[" . date("Y-m-d h:i:s") . "] " . $exception_object->getFile()
                . ":" . $exception_object->getLine()
                . " - Error with message \""
                . $exception_object->getMessage() . "\" was thrown from "
                . "$trace[class]::$trace[function] ($trace[file]:$trace[line])"
                . " with arguments: ('" . implode("', '", $trace[args])
                . "')\n\n";

        // Logs the error message
        self::writeLog($err);

        // Stop script execution if the error was fatal
        if ( $is_fatal===TRUE )
        {
            die( $exception_object->getMessage() );
        }
    }

    /**
     * Writes an error message to the log (ennui-cms/log/exception.log)
     *
     * @param string $message   The error message
     * @return void
     */
    private static function writeLog( $message )
    {
        // Creates a pointer to the log
        $log = fopen(CMS_PATH . 'log/exception.log', 'a');

        // Appends the message to the log
        fwrite($log, $message);

        // Frees the resource
        fclose($log);
    }
}
