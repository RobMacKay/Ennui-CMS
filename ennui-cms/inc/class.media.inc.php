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
}

?>