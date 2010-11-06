<?php

/**
 * A set of utility functions
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
class Utilities
{

    /**
     * Generates an excerpt of a given number of words
     *
     * @param string $body  The text to excerpt
     * @param int $limit    The number of words to include in the excerpt
     * @return string       The excerpt
     */
    public static function text_preview( $body, $limit=45, $wrap_ptags=TRUE )
    {
        /*
         * Remove newlines, replace heading tags with strong tags, and swap out
         * paragraph tags for line breaks
         */
        $pat = array(
                "/\n++/is",
                "/<h(?:2|3)(.*?)>/i",
                "/<\/h(?:2|3)>/i",
                "/<p>/i",
                "/<\/p>(?:\n*)/is"
            );
        $rep = array(
                "",
                "<strong>",
                "</strong><br /><br />",
                "",
                "<br /><br />"
            );
        $body = preg_replace($pat, $rep, $body);

        // Get rid of other tags to avoid an unclosed tag in the preview
        $text = strip_tags($body,'<strong><br><a>');

        /*
         * Check for empty tags, leading line breaks, and any instances of more
         * than two line breaks to avoid broken layouts
         */
        $pat2 = array(
                "/<([A-Z][A-Z0-9]*)\b[^>]*>\s*?<\/\\1>/is",
                "/<([A-Z][A-Z0-9]*)\b[^>]*>\s*?<\\1>/is",
                "/^(?:<br ?\/>)*\s*/is",
                "/(?:<br ?\/>(?:\n|&nbsp;)*){3,}+/is"
            );
        $rep2 = array(
                "",
                "",
                "",
                "<br /><br />");
        $text = preg_replace($pat2, $rep2, $text);


        // Pull the text apart at the spaces
        $words = explode(' ', $text);

        // Make sure the text has enough words to warrant a preview
        if ( $limit<count($words) )
        {
            // Run a loop and build a preview with the specified number of words
            for ($i=0, $w=array(); $i<$limit-1; ++$i)
            {
                array_push($w, $words[$i]);
            }

            // Remove trailing punctuation and add the last word
            array_push($w, strtr($words[$i], array('.'=>'', ','=>'')));

            // Create the string and add an ellipsis
            $preview = implode(' ', $w) . '...';
        }

        // Otherwise set the full text as the preview
        else
        {
            $preview = $text;
        }

        // Check if any unclosed <strong> tags exist in the preview
        $strong_open = preg_match_all('/<strong\b[^>]*>/is', $preview, $out[]);
        $strong_close = preg_match_all('/<\/strong>/is', $preview, $out[]);
        if ( $strong_open>$strong_close )
        {
            $preview .= "</strong>";
        }

        // Check if any unclosed anchor tags exist in the preview
        $a_open = preg_match_all('/<a\b[^>]*>/is', $preview, $out[]);
        $a_close = preg_match_all('/<\/a>/is', $preview, $out[]);
        if ( $a_open>$a_close )
        {
            $preview .= "</a>";
        }

        // If the flag is set, wrap the output in a paragraph tag
        return $wrap_ptags===TRUE ? "<p>".wordwrap($preview)."</p>" : $preview;
    }

    static function copyright_year($created)
    {
        $current = date('Y', time());
        return ($current>$created) ? $created.'-'.$current : $current;
    }

    public static function read_url()
    {
        $root = dirname($_SERVER['SCRIPT_FILENAME']);
        $uri = $_SERVER['REQUEST_URI'];
        $uri = explode('?', $uri);
        $request = $uri[0];
        $script = $_SERVER['SCRIPT_NAME'];

        if( file_exists($root.$request)
                && $script!==$root.$request
                && $request!=="/" )
        {
            require_once $root.$request;
            exit;
        }
        else
        {
            $url = strip_tags($request);
            $url_array=explode("/",$url);
            array_shift($url_array);
        }

        if( empty($url_array) )
        {
            header('Location:/');
            exit;
        }
        else
        {
            if( strlen($url_array[0])<1 )
            {
                $url_array[0] = DB_Actions::get_default_page();
            }
        }

        return $url_array;
    }

    public static function make_url($string)
    {
        if ( !empty($string) )
        {
            $pattern = array('/[^\w\s]+/', '/\s+/');
            $replace = array('', '-');
            return preg_replace($pattern, $replace, trim(strtolower($string)));
        }
        else { return NULL; }
    }

    /**
     * Checks for the existence of a cached file with the ID passed
     *
     * @param string $cache_id  A string by which the cache is identified
     * @return mixed            The cached data if saved, else boolean FALSE
     */
    public static function check_cache($cache_id)
    {
        $cachefile = CACHE_DIR . md5($cache_id) . '.cache';

        if( AdminUtilities::check_clearance(1) )
        {
            return FALSE;
        }

		/*
		 * If the cached file exists and is within the time limit defined in
         * CACHE_EXPIRES, load the cached data. Does not apply if the user is
         * logged in
		 */
		if( file_exists($cachefile)
                && time()-filemtime($cachefile)<=CACHE_EXPIRES )
		{
			$cache = file_get_contents($cachefile);

            FB::log("Data loaded from cache at $cachefile");

            return unserialize($cache);
		}
        else { return FALSE; }
    }

    /**
     * Caches data for future reuse
     * 
     * @param string $handle    The ID with which to identify the cached data
     * @param mixed $data       The cached data (usually an array)
     * @return string           The name of the cache file
     */
    public static function save_cache($handle, $data)
    {
        // Create a unique file handle for the data
        $cachefile = CACHE_DIR . md5($handle) . '.cache';

        // Cache the images for vast speed improvements
        $fp = fopen($cachefile, "w");
        fwrite($fp, serialize($data));
        fclose($fp);

        FB::log("Cache saved at $cachefile");

        return $cachefile;
    }

    /**
     * Cleans a file name and stores an uploaded file
     * @param array $file_info  The info array for the uploaded file
     */
    public static function store_uploaded_file( $file_info )
    {
        $dir = FILE_SAVE_DIR;

        // Make sure all spaces are replaced with underscores
        $name = Utilities::make_url($name).'.pdf';

        // If the directory doesn't exist, create it
        if( !is_dir($dir) )
        {
            mkdir($dir, 0777, TRUE)
                    or ECMS_Error::log_exception(
                                new Exception("Could not create the directory '$dir'.")
                            );
        }

        // Place the uploaded file into the directory
        move_uploaded_file($files['tmp_name'],$dir.$name);

        return $dir.$name;
    }

    /**
     * Loads a file or an array of files into memory after parsing PHP inside
     *
     * @param mixed $filepath   A file path or array of file paths
     * @param array $var_arr    An array of variables to be passed to files
     * @return string
     */
    public static function load_file( $filepath, $var_arr=array() )
    {
        // Start an output buffer
        ob_start();

        // Check if an array of file paths was supplied
        if( is_array($filepath) )
        {
            // Loop through each path
            foreach( $filepath as $file )
            {
                // If variables for the file exist, extract and define them
                if( array_key_exists($file, $var_arr) )
                {
                    foreach( $var_arr[$file] as $key=>$val )
                    {
                        $$key = $val;
                    }
                }

                // Make sure the file exists, then load it
                if( file_exists($file) )
                {
                    require_once $file;
                }
                else
                {
                    ECMS_Error::log_exception(
                            new Exception("Failed to load $file")
                        );
                }
            }
        }

        // If only one file path was supplied
        else
        {
            // Check if variables were supplied for the file
            if( count($var_arr>=1) )
            {
                foreach( $var_arr as $key=>$val )
                {
                    $$key = $val;
                }
            }

            // Make sure the file exists, then load it
            if( file_exists($filepath) )
            {
                require_once $filepath;
            }
            else
            {
                ECMS_Error::log_exception(
                        new Exception("Failed to load $filepath")
                    );
            }
        }

        // Return the buffer contents
        return ob_get_clean();
    }

    /**
     * Deletes a file from the filesystem
     * 
     * @param string $file_name The file to be deleted
     * @return bool             TRUE on success, FALSE on failure
     */
    public function delete_file( $file_name )
    {
        // Make sure the passed value is actually a file
        if( is_file($file_name) )
        {
            return unlink($file_name);
        }
        else
        {
            return FALSE;
        }
    }

}
