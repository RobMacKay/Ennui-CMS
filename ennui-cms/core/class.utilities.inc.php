<?php

class Utilities
{

    public static function textPreview($body, $limit='45')
    {
        $preview = NULL;

        // Get rid of most tags to avoid an unclosed tag in the preview
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

        $text = strip_tags($body,'<strong><br><a>');

        $pat2 = array(
                "/<([A-Z][A-Z0-9]*)\b[^>]*>\s*?<\/\\1>/is",
                "/<([A-Z][A-Z0-9]*)\b[^>]*>\s*?<\\1>/is",
                "/^(?:<br ?\/>)*\s*/is",
                "/(?:<br ?\/>(?:\n|&nbsp;)*){2,}+/is"
            );
        $rep2 = array(
                "",
                "",
                "",
                "<br /><br />");
        $text = preg_replace($pat2, $rep2, $text);


        // Pull the text apart at the spaces
        $words = explode(' ', $text);

        if($limit<count($words))
        {
            // Run a loop and build a preview with the specified number of words
            for ($i=0; $i<$limit-1; ++$i)
            {
                $preview .= $words[$i] . ' ';
            }
            $preview .= str_replace('.','',str_replace(',','',$words[$i])) . '...';
        }
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

        return "<p>" . wordwrap($preview) . "</p>";
    }

    public static function formatImageThumb($e)
    {
        if ( isset($e['img']) && strlen($e['img'])>4 )
        {
            $thumbURL = str_replace('userPics/', 'userPics/thumbs/', $e['img']);
            $cap = isset($e['imgcap']) ? $e['imgcap'] : $e['title'];
            return "<img src=\"$thumbURL\" alt=\"$cap\" class=\"thumb\" />";
        }
        else return NULL;
    }

    public static function formatImageSimple($e)
    {
        if ( isset($e['img']) && strlen($e['img'])>4 )
        {
            $cap = isset($e['imgcap']) ? $e['imgcap'] : $e['title'];
            return "<img src=\"$e[img]\" alt=\"$cap\" class=\"simple\" />";
        }
        else return NULL;
    }

    public static function formatImage($e)
    {
        if ( isset($e['img']) && strlen($e['img'])>4 )
        {
            $cap = isset($e['imgcap']) ? $e['imgcap'] : $e['title'];
            return "\n\t\t\t\t<div id=\"main_image\">\n\t\t\t\t\t<img "
                . "src=\"$e[img]\" alt=\"$cap\" title=\"$cap\" />\n\t\t\t\t\t"
                . "<p class=\"cap\">$cap</p>\n\t\t\t\t</div>\n";
        }
        else return NULL;
    }

    static function buildMenu($url_array, $menu_array, $is_sub=FALSE, $subid=NULL)
    {
        $attr = !$is_sub ? ' id="menu"' : ' class="submenu ' . $subid . '"';

        $menu = "            <ul$attr>";

        /*
         * Loop through the array to extract element values
         */
        foreach($menu_array as $id => $properties) {

            /*
             * Because each page element is another array, we
             * need to loop again. This time, we save individual
             * array elements as variables, using the array key
             * as the variable name.
             */
            foreach($properties as $key => $val) {

                /*
                 * If the array element contains another array,
                 * call the buildMenu() function recursively to
                 * build the sub-menu and store it in $sub
                 */
                if(is_array($val))
                {
                    $sub = self::buildMenu($url_array, $val, TRUE, $id);
                    if(array_key_exists($url_array[0], $val))
                    {
                        $class = isset($class) ? $class . ' parent' : 'parent';
                    }
                }

                /*
                 * Otherwise, set $sub to NULL and store the
                 * element's value in a variable
                 */
                else
                {
                    $sub = NULL;
                    $$key = $val;
                }
            }

            /*
             * If no array element had the key 'url', set the
             * $url variable equal to the containing element's ID
             */
            if(!isset($url)) {
                $url = $id;
            }

            /*
             * If a class element is set, add it to the <li> tag
             */
            if(!isset($class))
            {
                $class = NULL;
            }

            /*
             * Determine if the element matches the current page
             */
            $sel = ($url == $url_array[0]) ? ' class="selected '.$class.'"' : ' class="'.$class.'"';

            if ( !isset($showFull) || $showFull===TRUE )
            {
                /*
                 * If the item is hidden, don't build markup for it
                 */
                if ( isset($hide) && $hide===TRUE ) { continue; }

                /*
                 * Check if additional attributes are present
                 */
                $extra = isset($inline) ? ' '.trim($inline) : NULL;

                /*
                 * Check if the URL is external
                 */
                $url = stripos($url, 'http://', 0)!==FALSE ? $url : "/$url";

                /*
                 * Use the created variables to output HTML
                 */
                $menu .= "
                <li$sel$extra>
                    <a href=\"$url\">$display</a>$sub
                </li>";
            }

            /*
             * Destroy the variables to ensure they're reset
             * on each iteration
             */
            unset($url, $display, $sub, $class, $hide, $showFull, $inline);
        }

        return $menu . "\n            </ul><!-- end #menu -->";
    }

    public static function getPageType($m, $u)
    {
        foreach($m as $p => $attr)
        {
            if ( strtolower($p)===$u )
            {
                return $m[$u]['type'];
            }
            else if ( strtolower(isset($m[$p]['sub'][$u])) )
            {
                return $m[$p]['sub'][$u]['type'];
            }
        }
        return DEFAULT_PAGE;
    }

    public static function getPageAttributes($menu, $url)
    {
        foreach ( $menu as $key => $value )
        {
            if ( $key===$url )
            {
                return $menu[$url];
            }
            elseif ( isset($menu[$key]['sub']) )
            {
                if( self::getPageAttributes($menu[$key]['sub'], $url) )
                {
                    return self::getPageAttributes($menu[$key]['sub'], $url);
                }
            }
        }
        return FALSE;
    }

    /**
     * Loads a template with which markup should be formatted
     *
     * @param string $template The name of the template file to use
     * @return string The contents of the template file
     */
    public static function loadTemplate($template)
    {
        // Check for a custom template
        if ( file_exists('assets/templates/' . $template) )
        {
            $path = 'assets/templates/' . $template;
        }

        // Look for a system template
        elseif ( file_exists(CMS_PATH . 'template/' . $template) )
        {
            $path = CMS_PATH . 'template/' . $template;
        }

        // Load the default template as last resort
        elseif ( file_exists(CMS_PATH . 'template/default.inc') )
        {
            $path = CMS_PATH . 'template/default.inc';
        }

        // If the default template is missing, throw an error
        else
        {
            throw new Exception ( "No default template found at $default" );
        }

        // For debugging, log the template file location
        FB::log($path, "Template File");

        // Load the contents of the file into a variable
        $file = fopen($path, 'r');
        $contents = fread($file, filesize($path));
        fclose($file);

        return $contents;
    }

    /**
     *
     * @param array $entries The entries to be marked up
     * @param string $template The template to use for marking up entries
     * @param array $extra Additional content for marking up the header/footer
     * @return sring The marked up content
     */
    public static function parseTemplate($entries, $template, $extra=array())
    {
        FB::log($entries, "Entry Array");

        // Remove any comments from the template
        $comment_pattern = array('/\/\*(.*)?\*\//s', '/(?<!:)(?:\/\/).*/');
        $template = preg_replace($comment_pattern, array('', ''), $template);

        // Extract the loop parameters if they exist
        $params = preg_replace('/.*\{loop\s\[(.*?)\]\}.*/is', "$1", $template);
        if ( $params===$template )
        {
            $params = NULL;
        }

        // Define default parameters
        $p = array(
            "max_entries" => MAX_ENTRIES_PER_PAGE,
            "htmlentities" => FALSE,
            "strip_tags" => FALSE,
            "strip_tags_whitelist" => STRIP_TAGS_WHITELIST,
            "text_preview" => FALSE,
            "text_preview_length" => 25
        );

        // If parameters were passed, decode them here
        if ( !empty($params) )
        {
            $param_array = json_decode('{'.$params.'}', TRUE);
            foreach ( $param_array as $key => $val )
            {
                // Make sure the parameter is valid before saving
                if ( array_key_exists($key, $p) )
                {
                    // Overwrite the default parameter
                    $p[$key] = $val;
                }
            }
        }

        // Extract the main entry template from the file
        $pattern = '/.*\{loop.*?\}(.*?)\{\/loop\}.*/is';
        $entry_template = preg_replace($pattern, "$1", $template);

        /*
         * Define the template tag matching regex and curry the function that
         * will replace the tags with entry data
         */
        $pattern = "/\{([\w-]+?)\}/i"; // Matches any template tag
        $callback = Utilities::curry('Utilities::replaceTags', 3);

        // Extract the header from the template if one exists
        $header = preg_replace('/^(.*)?\{loop.*/is', "$1", $template);
        if ( $header===$template )
        {
            $header = NULL;
        }

        // If extra data was passed to fill in the header, parse it here
        if ( isset($header) && array_key_exists('header', $extra) )
        {
            $header = preg_replace_callback(
                            $pattern,
                            $callback($extra['header'], $p),
                            $header
                        );
        }

        // Extract the footer from the template if one exists
        $footer = preg_replace('/^.*?\{\/loop\}(.*)/is', "$1", $template);
        if ( $footer===$template )
        {
            $footer = NULL;
        }

        // If extra data was passed to fill in the footer, parse it here
        if ( isset($footer) && array_key_exists('footer', $extra) )
        {
            $footer = preg_replace_callback(
                            $pattern,
                            $callback($extra['footer'], $p),
                            $footer
                        );
        }

        /*
         * Loop through each passed entry and insert its values into the
         * layout defined in the looped section of the template
         */
        $markup = NULL;
        for ( $i=0, $c=min($p['max_entries'],count($entries)); $i<$c; ++$i )
        {
            $markup .= preg_replace_callback(
                            $pattern,
                            $callback($entries[$i], $p),
                            $entry_template
                        );
        }

        // Return the formatted data and append the footer if a match is made
        return $header . $markup . $footer;
    }

    /**
     * A currying function
     *
     * Currying allows a function to be called in increments. This means that if
     * a function accepts two arguments, it can be curried with only one
     * argument supplied, which returns a new function that will accept the
     * remaining argument and return the output of the original curried function
     * using the two supplied parameters.
     *
     * Example:
     *
     * function add($a, $b)
     * {
     *     return $a + $b;
     * }
     *
     * $func = Utilities::curry('add', 1);
     *
     * $func2 = $func(1); // Stores 1 as the first argument of add()
     *
     * echo $func2(2); // Executes add() with 2 as the second arg and outputs 3
     *
     * @param string $func The name of the function to curry
     * @param int $arity The number of arguments the function accepts
     * @return mixed A curried function or the return of the original function
     */
    public static function curry($func, $arity) {
        return create_function('', "
            // Store the passed arguments in an array
            \$args = func_get_args();

            /*
             * If the number of arguments passed is equal to or greater than the
             * number of arguments defined in \$arity, execute the function in
             * \$func using the provided arguments
             */
            if(count(\$args) >= $arity)
            {
                return call_user_func_array('$func', \$args);
            }

            // Export the function arguments as executable PHP code
            \$args = var_export(\$args, 1);

            /*
             * If the number of arguments does not meet or exceed the number of
             * arguments defined in \$arity, a new function is returned with the
             * passed arguments stored as an array
             */
            return create_function('','
                \$a = func_get_args();
                \$z = ' . \$args . ';
                \$a = array_merge(\$z,\$a);
                return call_user_func_array(\'$func\', \$a);
            ');
        ");
    }

    static function replaceTags($entries, $params, $matches)
    {
        /*
         * Make sure the template tag has a matching array element
         */
        if ( array_key_exists(strtolower($matches[1]), $entries) )
        {
            $val = $entries[strtolower($matches[1])];

            // Run htmlentities() is the parameter is set to TRUE
            if ( $params['htmlentities']===TRUE )
            {
                $val = htmlentities($val, ENT_QUOTES);
            }

            // Run strip_tags() if the parameter is set to TRUE
            if ( $params['strip_tags']===TRUE )
            {
                $whitelist = NULL;
                if ( isset($params['strip_tags_whitelist']) )
                {
                    $whitelist = $params['strip_tags_whitelist'];
                }
                $val = strip_tags($val, $whitelist);
                //TODO: Finish the attribute stripping function
//                $val = Utilities::strip_tags_attr($val, $whitelist);
            }

            // Create a text preview if one the parameter is set to TRUE
            if ( $params['text_preview']===TRUE && $matches[1]=='body' )
            {
                $val = Utilities::textPreview($val, $params['text_preview_length']);
            }

            return $val;
        }

        /*
         * Otherwise, simply return the tag as is
         */
        else { return "{".$matches[1]."}"; }
    }

    static function copyrightYear($created)
    {
        $current = date('Y', time());
        return ($current>$created) ? $created.'-'.$current : $current;
    }

    static function isValidEmail($email)
    {
        // Define a regex pattern to validate the email address
        $p = "/^[\w-]+(\.[\w-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i";
        return preg_match($p, $email) == 1 ? TRUE : FALSE;
    }

    static function strip_tags_attr($str, $allowable_tags=NULL)
    {
        if ( isset($allowable_tags) )
        {
            $arg_check = '/^(?:<([a-z])+(?:\[([a-z|])*\])?>)+$/i';
            if ( preg_match($arg_check, $allowable_tags) )
            {
                $tag_match = '/<([a-z]+)(?:\[([a-z|]*)\])?>/i';
                preg_match_all($tag_match, $allowable_tags, $tags);

                for ( $i=0, $c=count($tags[0]); $i<=$c; ++$i )
                {
                    $whitelist .= "<{$tags[1][$i]}>";
                    $attrs = !empty($tags[2][$i]) ? explode("|", $tags[2][$i]) : NULL;
                    FB::log($whitelist, "Whitelist");
                    FB::log($attrs, "Attributes");
                }
            }
        }
    }

    static function readUrl()
    {
        $root = dirname($_SERVER['SCRIPT_FILENAME']);
        $uri = $_SERVER['REQUEST_URI'];
        $uri = explode('?',$uri);
        $request = $uri[0];
        $script = $_SERVER['SCRIPT_NAME'];

        if(file_exists($root.$request)
                && ($script != $root.$request)
                && ($request!="/")) {
            $url = $request;
            return $root.$url;
        } else {
            $url = strip_tags($request);
            $url_array=explode("/",$url);
            array_shift($url_array);
        }

        if(empty($url_array)) {
            header('Location:/');
            exit;
        } else {
            if(strlen($url_array[0])<1) {
                $url_array[0] = str_replace(' ', '', strtolower(DEFAULT_PAGE));
            }
        }

        return $url_array;
    }

    public static function makeUrl($string)
    {
        if ( !empty($string) )
        {
            $pattern = array('/[^\w\s]+/', '/\s+/');
            $replace = array('', '-');
            return preg_replace($pattern, $replace, trim(strtolower($string)));
        }
        else { return NULL; }
    }

	public static function displaySearchBox($page='blog')
	{
		$page = htmlentities($page, ENT_QUOTES);
        $form_action = FORM_ACTION;
		return "
                <form method=\"post\"
                      action=\"$form_action\">
                    <fieldset>
                        <input type=\"text\" name=\"search_string\" />
                        <input type=\"hidden\" name=\"page\"
                               value=\"$page\" />
                        <input type=\"hidden\" name=\"action\"
                               value=\"entry_search\" />
                        <input type=\"submit\" value=\"Search\" />
                    </fieldset>
                </form>";
	}

}
