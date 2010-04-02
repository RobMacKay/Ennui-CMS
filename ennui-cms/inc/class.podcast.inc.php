<?php

class Podcast extends Page
{

	private $_categories = array();

	public function displayPublic()
	{
		$limit = MAX_ENTRIES_PER_PAGE; // Number of entries per page
		if(isset($this->url1))
		{
			$offset = (isset($this->url2)) ? $limit*($this->url2-1) : 0;
		}
		else
		{
			$offset = 0;
		}
		$entries = $this->getAllEntries($limit, $offset);
		return $this->displayPreview($entries);
	}

	public function displayAdmin($id)
	{
		$form = $this->createForm('write', $id);

		$markup = $form['start'];
		$markup .= $this->createFormInput('title', 'Headline', $id);
		$markup .= $this->createFormInput('data2', 'Author(s)', $id);
		$markup .= $this->createFormInput('data3', 'Location of MP3', $id);
		$markup .= $this->createFormInput('data4', 'Length (h:mm:ss)', $id);
		$markup .= $this->createFormInput('data5', 'Keywords', $id);
		$markup .= $this->createFormInput('body','Description',$id);
		$markup .= $form['end'];

		return $markup;
	}

	private function displayPreview($entries)
	{
		$id = (isset($entries[0]['id'])) ? $entries[0]['id'] : NULL;
		$entry = $this->admin_general_options($this->url0, $id, false);

		if(isset($entries[0]['data6'])) {
			// Entry options for the admin, if logged in
			$f = $entries[0];
			$admin_entry = $this->admin_simple_options($this->url0, $f['id']);
			$f['url'] = strpos($f['data3'], 'http://')!==FALSE ? $f['data3'] : SITE_URL.$f['data3'];
			$f['date'] = date("m.d.y", $f['created']);
			$entry .= "\n\t\t\t<h3>Latest Episode</h3>\n\t\t\t<ul "
				. "class=\"podcast-latest\">\n\t\t\t\t<li> <a href=\"$f[url]\">"
				. "$f[title]</a> ($f[date])$admin_entry</li>\n\t\t\t</ul>"
				. "<!-- end .podcast-latest -->\n\n\t\t\t<h3>Show Archive</h3>"
				. "\n\t\t\t<ul class=\"podcast-archive\">";
			foreach($entries as $e) {
				$admin_entry = $this->admin_simple_options($this->url0, $e['id']);

				$e['url'] = strpos($e['data3'], 'http://')!==FALSE ? $e['data3'] : SITE_URL.$e['data3'];
				$e['date'] = date("m.d.y", $e['created']);
				$e['desc'] = strip_tags($e['body'], "<strong><em><span><a>");

				$entry .= "\n\t\t\t\t<li><a href=\"$e[url]\">$e[date]"
					. "</a>: <a href=\"$e[url]\"><em>$e[title]</em></a> "
					. "$e[desc]$admin_entry</li>";
			}
			$entry .= "\n\t\t\t</ul><!-- end .podcast-archive -->";
		} else {
			$entry .= "\n\t\t\t<h2> No Entry Found </h2>\n\t\t\t<p>\n\t\t\t\t"
				. "Log in to create this entry.\n\t\t\t</p>";
		}

		return $entry;
	}

	public function createFeedChannel()
	{
		require_once CMS_PATH . 'config/podcast.inc.php';
		foreach ( $_P as $const => $val )
		{
			define($const, $val);
		}
		$this->_categories = $podcastCategories;

		$items = $this->_displayItems();
		$title = PODCAST_TITLE;
		$link = SITE_URL;
		$copyright = SITE_CREATED_YEAR . ' ' . SITE_NAME;
		$subtitle = PODCAST_SUBTITLE;
		$author = PODCAST_AUTHOR;
		$email = SITE_CONTACT_EMAIL;
		$summary = PODCAST_SUMMARY;
		$image = PODCAST_IMAGE;
		$categories = $this->_generatePodcastCategories();

		$feed_channel = <<<FEED_MARKUP
<?xml version="1.0" encoding="UTF-8"?>

<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">

	<channel>
		<title>$title</title>
		<link>$link</link>
		<language>en-us</language>
		<copyright>&#x2117; &amp; &#xA9; $copyright</copyright>
		<itunes:subtitle>$subtitle</itunes:subtitle>
		<itunes:author>$author</itunes:author>
		<itunes:summary>$summary</itunes:summary>
		<description>$summary</description>
		<itunes:owner>
			<itunes:name>$author</itunes:name>
			<itunes:email>$email</itunes:email>
		</itunes:owner>
		<image>
			<url>$image</url>
			<title>$title</title>
			<link>$link</link>
		</image>
		<itunes:image href="$image" />$categories
$items

	</channel>
</rss>
FEED_MARKUP;
		return $feed_channel;
	}

	private function _displayItems()
	{
		$html = NULL;
		$items = $this->getAllEntries();
		foreach($items as $item) {
			$file = str_replace(SITE_URL.'/', '', $item['data3']);
			if( substr($file, 0, 1)=='/' ) $file = substr($file, 1);
			$file_size = filesize($file);
			$title = htmlentities($item['title']);
			$subtitle = htmlentities($item['data1']);
			$author = htmlentities($item['data2']);
			$file_loc = htmlentities(SITE_URL.'/'.$file);
			$length = htmlentities($item['data4']);
			$keywords = htmlentities($item['data5']);
			$summary = htmlentities(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $item['body']));
			$pubDate = date(DATE_RSS, $item['created']);
			$html .= <<<HTML_MARKUP

		<item>
			<title>$title</title>
			<itunes:author>$author</itunes:author>
			<itunes:subtitle>$subtitle</itunes:subtitle>
			<itunes:summary>$summary</itunes:summary>
			<description>$summary</description>
			<enclosure url="$file_loc" length="$file_size" type="audio/mpeg" />
			<guid>$file_loc</guid>
			<pubDate>$pubDate</pubDate>
			<itunes:duration>$length</itunes:duration>
			<itunes:keywords>$keywords</itunes:keywords>
		</item>
HTML_MARKUP;
		}
		return $html;
	}

	private function _generatePodcastCategories()
	{
		$cat_start = '<itunes:category text="';
		$cat_end = '</itunes:category>';
		$categories = NULL;
		foreach ( $this->_categories as $cat )
		{
			$categories .= "\n\t\t$cat_start$cat[category]";
			if ( isset($cat['subcategory']) )
			{
				$categories .= "\">\n\t\t\t$cat_start$cat[subcategory]\" />"
					. "\n\t\t$cat_end";
			}
			else
			{
				$categories .= '" />';
			}
		}
		return $categories;
	}
}

?>