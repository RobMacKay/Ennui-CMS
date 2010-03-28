<?php

class Utilities
{
	public static function textPreview($body, $limit='45')
	{
		$preview = NULL;

		/*
 		* Get rid of tags to avoid an unclosed tag in the preview
 		*/
		$body = preg_replace('/<h2(.*?)>/','<strong>',str_replace('</h2>','</strong><br /><br />', $body));
		$body = str_replace('<p>','',str_replace('</p>','<br /><br />',$body));
		$text = strip_tags($body,'<strong><br><a>');

		/*
 		* Pull the $body variable apart at the spaces
 		*/
		$words = explode(' ', $text);

		if($limit<count($words)) {
			/*
 			* Run a loop and build a preview with the specified number of words
 			*/
			for ($i=0; $i<$limit-1; ++$i) {
				$preview .= $words[$i] . ' ';
			}
			$preview .= str_replace('.','',str_replace(',','',$words[$i])) . '...';
		} else {
			$preview = $text;
		}

		return wordwrap($preview);
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

		$menu = "\n\t\t\t<ul$attr>\n";

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
				$menu .= "\t\t\t\t<li$sel$extra><a href=\"$url\">$display</a>$sub</li>\n";
			}

			/*
			 * Destroy the variables to ensure they're reset 
			 * on each iteration
			 */
			unset($url, $display, $sub, $class, $hide, $showFull, $inline);
		}

		return $menu . "\t\t\t</ul><!-- end menu -->\n";
	}

	public static function getPageType($m, $u)
	{
		foreach($m as $p => $attr)
		{
			if(strtolower($p)===$u) return $m[$u]['type'];
			elseif(strtolower(isset($m[$p]['sub'][$u]))) return $m[$p]['sub'][$u]['type'];
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

	public static function parseTemplate($replace, $template)
	{
		$params = preg_replace('/.*\{loop\s\[(.*?)\]\}.*/is', "$1", $template);
		$entry_template = preg_replace('/.*\{loop.*?\}(.*?)\{\/loop\}.*/is', "$1", $template);

		/*
		 * Define the template tag matching regex and curry the function that
		 * will replace the tags with entry data
		 */
		$pattern = "/\{(\w+?)\}/i"; // Matches any template tag
		$callback = Utilities::curry('Utilities::replaceTags', 2);

		/*
		 * Extract the header and footer from the template if they exist
		 */
		$header = preg_replace('/^(.*)?\{loop.*/is', "$1", $template);
		$footer = preg_replace('/^.*?\{\/loop\}(.*)/is', "$1", $template);
		if ( $header==$template )
		{
			$header = NULL;
		}

		if ( $footer==$template )
		{
			$footer = NULL;
		}

		/*
		 * Loop through each passed entry and insert its values into the
		 * layout defined in the looped section of the template
		 */
		$markup = NULL;
		foreach ( $replace as $e )
		{
			$markup .= preg_replace_callback($pattern, $callback($e), $entry_template);
		}

		/*
		 * Return the formatted data and append the footer if a match is made
		 */
		return $header . $markup . $footer;
	}

	public static function curry($func, $arity) {
		return create_function('', "
			\$args = func_get_args();
			if(count(\$args) >= $arity)
				return call_user_func_array('$func', \$args);
			\$args = var_export(\$args, 1);
			return create_function('','
				\$a = func_get_args();
				\$z = ' . \$args . ';
				\$a = array_merge(\$z,\$a);
				return call_user_func_array(\'$func\', \$a);
			');
		");
	}
	
	static function replaceTags($transformations, $matches)
	{
		/**Catches undefined indexs on root pages**/
		if(!isset($tranformations['url']))
		{
			$transformations['url'] = null;
		}
		return $transformations[strtolower($matches[1])];
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

	static function readUrl()
	{
		// TODO: Make sure this works in all situations before pushing to master
		$root = dirname($_SERVER['SCRIPT_FILENAME']);
		$uri = $_SERVER['REQUEST_URI'];
		$uri = explode('?',$uri);
		$request = $uri[0];
		$script = $_SERVER['SCRIPT_NAME'];

		if(file_exists($root.$request)
				&& ($script != $root.$request)
				&& ($request!="/")) {
			$url = $request;
			include($root.$url);
			exit();
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

	public static function generatePageTitle($page, $title=NULL)
	{
		$sep = SITE_TITLE_SEPARATOR;
		$title = SITE_TITLE;
	}
	
	/*
	 * Takes a starting time and end time and returns stats about page rendering time. 
	 * Call this method at the bottom of your page.
	 *
	 * @author Drew Douglass, refactored original code by Jason Lengstorf
	 * @param float $start_time - The start time, use microtime 
	 * @param float $end_time - The end time, use microtime 
	 * @return str - returns page load in milliseconds via an HTML comment
	 */
	 public static function getTimerResults($start_time,$end_time)
	 {
	 	return ((float)$start_time && (float)$end_time) ? "<!-- Page rendered by Ennui CMS in ".round(($end_time-$start_time)*1000)." milliseconds -->" : false;
	 }
}

?>