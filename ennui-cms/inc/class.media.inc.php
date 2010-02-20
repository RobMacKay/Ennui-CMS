<?php

class Media
{
	static function latestVimeo($username)
	{
		// Load the RSS feed
		$xml = simplexml_load_file('http://vimeo.com/api/v2/'.$username.'/videos.xml');

		// Get the first video's ID
		$id = $xml->video[0]->id;

		// Output valid XHTML
		return "
				<object width=\"600\" height=\"338\" 
					type=\"application/x-shockwave-flash\" 
					data=\"http://vimeo.com/moogaloop.swf?clip_id=$id\">
					<param name=\"allowfullscreen\" value=\"true\" />
					<param name=\"allowscriptaccess\" value=\"always\" />
					<param name=\"movie\" 
						value=\"http://vimeo.com/moogaloop.swf?clip_id=$id\" />
				</object>\n";
	}

	static function latestYouTube($username)
	{
		// Load the RSS feed
		$xml = simplexml_load_file("http://www.youtube.com/rss/user/$username/videos.rss");

		// Get the first video's ID
		$movie = (string) $xml->channel->item[0]->link[0];
		$id = str_replace('http://www.youtube.com/watch?v=', '', $movie);

		// Set embedded player options
		$o = "?rel=0&hd=1&color1=0x000000&color2=0x0000FF&border=0&showsearch=0&showinfo=0&fs=1";

		// Output valid XHTML
		return "
				<object width=\"600\" height=\"338\" 
					type=\"application/x-shockwave-flash\" 
					data=\"http://www.youtube.com/v/$id$o\">
					<param name=\"allowfullscreen\" value=\"true\" />
					<param name=\"allowscriptaccess\" value=\"always\" />
					<param name=\"movie\" 
						value=\"http://www.youtube.com/v/$id$o\" />
				</object>\n";
	}

	static function loadFlickr($username, $class=NULL)
	{
		$feed = "http://api.flickr.com/services/feeds/photos_public.gne?id="
			. $username . "&lang=en-us&format=rss_200";
		$link = "http://flickr.com/$username";
		$rss = simplexml_load_file($feed);
		$photodisp = "\n\t<ul class=\"$class\">\n";
		foreach ($rss->channel->item as $item) {
		    $title = $item->title;
		    $media  = $item->children('http://search.yahoo.com/mrss/');
		    $image  = $media->thumbnail->attributes();
		    $url    = str_replace('_s', '', $image['url']);

		    $photodisp .= <<<________________EOD

	    <li>
			<img src="$url" 
				title="$title"
				alt="$title"
				style="border:0;" />
		</li>
________________EOD;
			}
			
			return $photodisp . "\n\t</ul>\n<a href=\"$link\">View on Flickr</a>";
	}
	
	/**
	  * Returns a shortened bit.ly link given a "long" anchor. 
	  * Attempts to use cURL before file_get_contents for performance reasons, see results here:
	  * http://stackoverflow.com/questions/555523/file-get-contents-vs-curl-what-has-better-performance
	  *
	  * @author - Drew Douglass
	  * @param str $username - The bitly username to use.
	  * @param str $key - The api key attached to the username. 
	  * @param str $link - The link to shorten, no HTML (i.e. http://google.com)
	  * @param [optional] int $timeout - The time in seconds until timeout, if performance is a major concern stick with 2 or less. 
	  * 
	  * Example usage: <?php echo Media::bitlyShorten("yourBitlyUsername","YOURAPIKEY","http://google.com"); ?>
	  */
	  public static function bitlyShorten($username, $key, $link, $timeout = 2)
	  {
	  		//Attempt to use cURL first as it is very fast. 
	  		if ( in_array("curl", get_loaded_extensions()) )
	  		{
	  			$ch = curl_init("http://api.bit.ly/shorten?version=2.0.1&login=$username&apiKey=$key&longUrl=$link");
	  			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	  			$short_link = json_decode(curl_exec($ch),true);
	  
	  			if ($short_link["errorCode"] === 0) {
	  				return $short_link["results"][$link]["shortUrl"];
	  			}
	  			//Else an error was present when using the API 
	  			return false;
	  		}
	  		
	  		//cURL not available, try file_get_contents with a slight performance hit.
	  		elseif ( function_exists("file_get_contents") )
	  		{
	  			$short_link = file_get_contents("http://api.bit.ly/shorten?version=2.0.1&login=$username&apiKey=$key&longUrl=$link");
	  			$short_link = json_decode($short_link,true);
	  			if ($short_link["errorCode"] === 0) {
	  				return $short_link["results"][$link]["shortUrl"];
	  			}
	  			//Else an error was present when using the API 
	  			return false;
	  		}
	  		
	  		else 
	  		{
	  			return false;
	  		}
	  }
}

?>