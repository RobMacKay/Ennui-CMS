<?php

/**
 * Generic functions for page interactions.
 * 
 * This class handles database interaction and file uploads for most publicly
 * displayed pages built on the EnnuiCMS platform.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 */
class Page extends AdminUtilities
{
    /**
     * Image dimensions
     * @var array 
     */
    public $img_dims = array(
            'w' => IMG_MAX_WIDTH,
            'h' => IMG_MAX_HEIGHT,
            't' => IMG_THUMB_SIZE
        );

    /**
     * First level URL index
     * @var string
     */
    public $url0;

    /**
     * Second level URL index
     * @var string
     */
    public $url1;

    /**
     * Third level URL index
     * @var string
     */
    public $url2;

    /**
     * Fourth level URL index
     * @var string
     */
    public $url3;

    /**
     * Page template file name
     * @var string
     */
    public $template = 'default.inc';

    /**
     * Loads the mysqli object and organizes the URL into variables
     *
     * @param object $mysqli
     * @param array $url_array
     */
    public function __construct( $url_array=NULL )
    {
        // Creates a database object
        parent::__construct();
		
        for( $i=0, $c=count($url_array); $i<$c; ++$i )
        {
            if ( !empty($url_array[$i]) )
            {
                $prop = "url$i";
                $this->$prop = $url_array[$i];
            }
        }
    }

    public function get_page_title( $menuPage )
    {
        $page = $menuPage->page_name;
        $title = SITE_TITLE;
        $sep = SITE_TITLE_SEPARATOR;

        $lookup = array(
                'tag', 'more'
            );

        if( in_array($this->url1, $lookup) && isset($this->url2) )
        {
            $entry_title = ucwords(str_replace('-', ' ', $this->url2));
            $entry = ucwords(str_replace('-', ' ', $this->url1))
                    . ': ' . $entry_title . ' ' .  $sep;
        }
        else if( isset($this->url1) && !empty($this->entries[0]->title) )
        {
            $entry = $this->entries[0]->title . ' ' . $sep;
        }
        else
        {
            $entry = NULL;
        }

        return "$entry $page $sep $title";
    }

    public function get_page_description()
    {
        if( isset($this->entries[0]->excerpt) )
        {
            return strip_tags($this->entries[0]->excerpt);
        }
        else if( isset($this->entries[0]->entry) )
        {
            $preview = Utilities::textPreview($this->entries[0]->entry, 25);
            return strip_tags($preview);
        }
        else
        {
            return SITE_DESCRIPTION;
        }
    }

    /**
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function generate_template_tags()
    {
        // If an entry exists, load the template and insert the data into it
        if( isset($this->entries[0]->title) )
        {
            // Generate the default template tags
            foreach( $this->entries as $entry )
            {
                $this->generate_default_template_tags($entry);
            }
        }

        // If no entry exists, output some default text to avoid a broken layout
        else
        {
            // Set default values if no entries are found and load a template
            $this->generate_default_entry();
        }
    }

    protected function generate_default_template_tags( &$e )
    {
        // Permalink for the entry
        $e->permalink = SITE_URL . $this->url0 . '/' . $e->slug;

        // Relative link
        $e->relative_link = '/' . $this->url0 . '/' . $e->slug;

        // Encoded URL and title values for sharing tools
        $e->encoded_permalink = urlencode($e->permalink);
        $e->encoded_title = urlencode($e->title);

        // Get or generate an excerpt
        if(!empty($e->excerpt) )
        {
            $e->excerpt = '<p>' . nl2br($e->excerpt) . '</p>';
        }
        else
        {
            $e->excerpt = Utilities::text_preview($e->entry, 25);
        }

        // Admin options
        $e->admin = $this->admin_simple_options($this->url0, $e->entry_id);

        // Human-readable date
        $e->date = !empty($e->created) ? date(DATE_FORMAT, $e->created) : NULL;

        // Image options
        $this->_generate_image_template_tags($e);
    }

    /**
     * Checks for an image and returns thumbnail, preview, and full-size URLs
     *
     * The entry object is passed by reference, so no return value is necessary
     *
     * @param object &$e
     * @return void
     */
    private function _generate_image_template_tags( &$e )
    {
        // Get the file path
        $filepath = dirname($_SERVER['SCRIPT_FILENAME']).$e->image;

        // If the image exists, set up image URLs
        if ( !empty($e->image) && file_exists($filepath) && is_file($filepath) )
        {
            // Extract the file basename
            $bn = basename($e->image);

            // Display the latest two galleries
            $e->image = $e->image;
            $e->preview = str_replace($bn, 'preview/'.$bn, $e->image);
            $e->thumb = str_replace($bn, 'thumbs/'.$bn, $e->image);
            $e->caption = isset($e->caption) ? $e->caption : $e->title;
        }

        // Otherwise, return default image URLs
        else
        {
            $e->image = '/assets/images/no-image.jpg';
            $e->preview = '/assets/images/no-image.jpg';
            $e->thumb = '/assets/images/no-image-thumb.jpg';
            $e->caption = 'No image';
        }
    }

    protected function generate_default_entry( $admin=NULL )
    {
        // Set default values if no entries are found
        $default = new Entry();
        $default->admin = $this->admin_general_options($this->url0);
        $default->title = "No Entry Found";
        $default->entry = "<p>That entry doesn't appear to exist.</p>";
        $this->_generate_image_template_tags($default);
        $this->entries[] = $default;

        // Load the default template
        $this->template = 'default.inc';
    }

    protected function generate_markup( $extra=array() )
    {
        $template = $this->_load_template();
        return $this->_parse_template($template, $extra);
    }

    /**
     * Loads a template with which markup should be formatted
     *
     * @return string The contents of the template file
     */
    private function _load_template(  )
    {
        // Check for a custom template
        if( file_exists('assets/templates/' . $this->template) )
        {
            $path = 'assets/templates/' . $this->template;
        }

        // Look for a system template
        else if( file_exists(CMS_PATH . 'template/' . $this->template) )
        {
            $path = CMS_PATH . 'template/' . $this->template;
        }

        // Look for a system template
        else if( file_exists('assets/template/default.inc') )
        {
            $path = 'assets/template/default.inc';
        }

        // Look for a system template
        else if( file_exists(CMS_PATH . 'template/default.inc') )
        {
            $path = CMS_PATH . 'template/default.inc';
        }

        // If the default template is missing, throw an error
        else
        {
            throw new Exception( "No default template found" );
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
     * @param string $template  The template to use for marking up entries
     * @param array $extra      Additional content for the header/footer
     * @return string           The entry markup
     */
    private function _parse_template( $template, $extra=array() )
    {
        // Remove any comments from the template
        $comment_pattern = array('/\/\*(.*)?\*\//s', '/(?<!:)(?:\/\/).*/');
        $template = preg_replace($comment_pattern, array('', ''), $template);

        // Extract the loop parameters if they exist
        $params = preg_replace('/.*\{loop\s\[(.*?)\]\}.*/is', "$1", $template);
        if( $params===$template )
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
            "text_preview_length" => 25,
            "add_first_entry_class" => FALSE
        );

        // If parameters were passed, decode them here
        if( !empty($params) )
        {
            $param_array = json_decode('{'.$params.'}', TRUE);
            foreach( $param_array as $key => $val )
            {
                // Make sure the parameter is valid before saving
                if( array_key_exists($key, $p) )
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
        $callback = $this->_curry('Page::replace_tags', 3);

        // Extract the header from the template if one exists
        $header = trim(preg_replace('/^(.*)?\{loop.*/is', "$1", $template));
        if( $header===$template )
        {
            $header = NULL;
        }

        // If extra data was passed to fill in the header, parse it here
        if( isset($header) && array_key_exists('header', $extra) )
        {
            $header = preg_replace_callback(
                            $pattern,
                            $callback(serialize($extra['header']), $p),
                            $header
                        );
        }

        // Extract the footer from the template if one exists
        $footer = trim(preg_replace('/^.*?\{\/loop\}(.*)/is', "$1", $template));
        if( $footer===$template )
        {
            $footer = NULL;
        }

        // If extra data was passed to fill in the footer, parse it here
        if( isset($footer) && array_key_exists('footer', $extra) )
        {
            $footer = preg_replace_callback(
                            $pattern,
                            $callback(serialize($extra['footer']), $p),
                            $footer
                        );
        }

        /*
         * Loop through each passed entry and insert its values into the
         * layout defined in the looped section of the template
         */
        $markup = NULL;
        for( $i=0, $c=min($p['max_entries'], count($this->entries)); $i<$c; ++$i )
        {
            $this->entries[$i]->first = $i===0 ? 'first' : NULL;
            $this->entries[$i]->last = $i===$c-1 ? 'last' : NULL;
            $markup .= preg_replace_callback(
                            $pattern,
                            $callback(serialize($this->entries[$i]), $p),
//                            $callback($this->entries[$i], $p),
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
    private function _curry($func, $arity)
    {
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

    /**
     *
     *
     * @param object $entry     The entry object
     * @param array $params     Parameters for replacement
     * @param array $matches    The matches from preg_replace_callback()
     *
     * @return string           The replaced template value
     */
    public static function replace_tags($entry, $params, $matches)
    {
        $entry = unserialize($entry);

        // Make sure the template tag has a matching array element
        $prop = strtolower($matches[1]);
        if( isset($entry->$prop) )
        {
            // Grab the value from the Entry object
            $val = $entry->$prop;

            // Run htmlentities() is the parameter is set to TRUE
            if ( $params['htmlentities']===TRUE )
            {
                $val = htmlentities($val, ENT_QUOTES);
            }

            // Run strip_tags() if the parameter is set to TRUE
            if ( $params['strip_tags']===TRUE )
            {
                $whitelist = STRIP_TAGS_WHITELIST;
                if ( isset($params['strip_tags_whitelist']) )
                {
                    $whitelist = $params['strip_tags_whitelist'];
                }

                $val = Utilities::strip_tags_attr($val, $whitelist);
            }

            // Create a text preview if one the parameter is set to TRUE
            if ( $params['text_preview']===TRUE && $matches[1]=='entry' )
            {
                $val = Utilities::textPreview($val, $params['text_preview_length']);
            }

            return $val;
        }

        // Otherwise, simply return the tag as is
        else { return "{".$matches[1]."}"; }
    }

    protected function paginate_entries($preview=BLOG_PREVIEW_NUM)
    {
        if ( $this->url0=="search" )
        {
            $page = 'search';
            $link = $this->url1 . '/' . $this->url2;
            $num = empty($this->url3) ? 1 : $this->url3;
            $c = $this->get_entry_count_by_search($this->url2, $this->url1);
        }
        else
        {
            $param1 = $page = empty($this->url0) ? 'blog' : $this->url0;
            $url1 = empty($this->url1) ? 'tag' : $this->url1;
            $url2 = empty($this->url2) ? 'recent' : $this->url2;
            $link = "$url1/$url2";
            $num = empty($this->url3) ? 1 : $this->url3;
            $c = $this->get_entry_count_by_tag();
        }

        $span = 6; // How many pages shown adjacent to current page

        $pagination = "\n<ul id=\"pagination\">";

        /*
         * Determine minimum and maximum page numbers
         */
        $pages = ceil($c/$preview);

        $prev_page = $num-1;
        $f = '    ';
        if($num==1)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "$f<li>\n$f$f<a href=\"/$page/$link/1/\">&#171;</a>"
                    . "\n$f</li>\n$f<li>"
                    . "\n$f$f<a href=\"/$page/$link/$prev_page/\">&#139;</a>"
                    . "\n$f</li>";
        }

        $mod = ($span>$num) ? $span-$num : 0;
        $max_mod = ($num+$span>$pages) ? $span-($pages-$num) : 0;
        $max = ($num+$span<=$pages) ? $num+$span+$mod : $pages;
        $max_num = ($max>$pages) ? $pages : $max;
        $min = ($max_num>$span*2) ? $num-$span-$max_mod : 1;
        $min_num = ($min<1) ? 1 : $min;

        for($i=$min_num; $i<=$max_num; ++$i)
        {
            $sel = ($i==$num) ? ' class="selected"' : NULL;
            $pagination .= "\n$f<li$sel>"
                    . "\n$f$f<a href=\"/$page/$link/$i/\">$i</a>\n$f</li>";
        }

        $next_page = $num+1;
        if($next_page>$pages)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "\n$f<li>"
                    . "\n$f$f<a href=\"/$page/$link/$next_page/\">&#155;</a>"
                    . "\n$f</li>\n$f<li>"
                    . "\n$f$f<a href=\"/$page/$link/$pages/\">&#187;</a>"
                    . "\n$f</li>";
        }

        return $pagination . "\n</ul><!-- end #pagination -->\n";
    }

    public function reorderEntries( $id, $pos, $direction )
    {
        $newpos = ($direction=="up") ? $pos-1 : $pos+1;
        $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                SET data7=?
                WHERE page=?
                AND id=?
                LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("isi", $newpos, $_POST['page'], $id);
        $stmt->execute();
        $stmt->close();

        $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                SET data7=?
                WHERE page=?
                AND data7=?
                AND id!=?
                LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("isii", $pos, $_POST['page'], $newpos, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function addPhotoCaption()
    {
        $albumID = $_POST['album_id'];
        $imageID = $_POST['image_id'];
        $imageCap = htmlentities(trim($_POST['image_cap']), ENT_QUOTES);

        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."imgCap`
                    (album_id, photo_id, photo_cap)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE photo_cap=?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("isss", $albumID, $imageID, $imageCap, $imageCap);
        $stmt->execute();
        $stmt->close();
        return TRUE;
    }

    public function __toString()
    {
        return $this->display_public();
    }

}
