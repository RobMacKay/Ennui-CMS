<?php

class Error
{
    public static function logException($exception_object)
    {
        FB::log($exception_object);

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

        die( $exception_object->getMessage() );
    }

    private static function writeLog($message)
    {
        // Creates a pointer to the log
        $log = fopen('../ennui-cms/log/exception.log', 'a');

        // Appends the message to the log
        fwrite($log, $message);

        // Frees the resource
        fclose($log);
    }
}