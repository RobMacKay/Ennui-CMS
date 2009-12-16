<?php

include_once 'class.comments.inc.php';

class blog extends Page
{
	public function displayPublic()
	{
		if(isset($this->url1)&&$this->url1!='category') {
			$entries = $this->getEntryByUrl($this->url1);
			return $this->displayEntry($entries);
		} else if($this->url1=='category'&&isset($this->url2)) {
			$url3 = isset($this->url3) ? $this->url3 : 1;
			$start_num = BLOG_PREVIEW_NUM*$url3-BLOG_PREVIEW_NUM;
			if($start_num< 0)
			{
				$start_num = 0;
			}
	
			if($this->url2!='recent')
			{
				$entries = $this->getEntriesByCategory($this->url2, BLOG_PREVIEW_NUM, $start_num);
			}
			else
			{
				$entries = $this->getAllEntries(BLOG_PREVIEW_NUM, $start_num);
			}
			return $this->displayEntriesSmall($entries, '-preview');
		} else {
			$entries = $this->getAllEntries(4);
			return $this->displayEntriesSmall($entries, '-preview');
		}
	}

	public function displayAdmin($id)
	{
		$form = $this->createForm('write', $id);

		$markup = $form['start'];
		$markup .= $this->createFormInput('title', 'Blog Title', $id);
		$markup .= $this->createFormInput('img', 'Main Image', $id);
		$markup .= $this->createFormInput('body','Blog Entry',$id);
		$markup .= $this->createFormInput('data2','Tags',$id);
		$markup .= $form['end'];

		return $markup;
	}

	public function displayEntriesSmall($entries)
	{
		$markup = $_SESSION['loggedIn']==1 ? $this->admin_general_options($this->url0) : NULL;

		foreach($entries as $e) {
			$e['image'] = Utilities::formatImageThumb($e);
			$e['preview'] = Utilities::textPreview($e['body'], 50);
			$e['link'] = "/$this->url0/" . urlencode($e['title']) . "/";
			$e['comments'] = comments::getCommentCount($e['id']);
			$e['comments_text'] = $e['comments']==1 ? "comment" : "comments";

			$e['commentcount'] = '<p class="comment-count"><a href="'
				. $e['link'] . '#comments">' . $e['comments'] . '</a> '
				. $e['comments_text'] . '</p>';

			$markup .= "\n\t\t\t\t\t<div class=\"entry-preview\">
						<h2> <a href=\"$e[link]\">$e[title]</a> </h2>$e[commentcount]
						<p class=\"blog-preview\">
							$e[image]$e[preview]
							<a href=\"$e[link]\" class=\"readmore\">read more</a>
						</p>
					</div><!-- end .entry-preview -->\n";
		}

		$pagination = $this->paginateEntries();

		return $markup.$pagination;
	}

	public function displayEntriesPreview($entries)
	{
		if($_SESSION['loggedIn']==1)
		{
			$admin = $this->admin_general_options($this->url0);
		}
		else
		{
			$admin = NULL;
		}

		if(!isset($entries[0]))
		{
			return "
				$admin<h2> No Entries Yet! </h2>
				<p>
					Log in to create an entry.
				</p>\n";
		}

		$entry = array_shift($entries);

		if($_SESSION['loggedIn']==1)
		{
			$admin = $this->admin_entry_options($this->url0, $entry['id']);
		}
		else
		{
			$admin = NULL;
		}

		$entry['link'] = "/{$this->url0}/" . urlencode($entry['title']) . "/";
		$entry['image'] = Utilities::formatImage($entry, TRUE);
		$entry['preview'] = Utilities::textPreview($entry['body'], 120);
		$entry['subhead'] = isset($entry['data1']) ? "<h2>$entry[data1]</h2>" : NULL;

		$entry['authorinfo'] = $this->formatAuthorInfo($entry);
		$entry['categories'] = $this->formatCategories($entry['data2']);
		$latest = "
				<h1> <a href=\"$entry[link]\">$entry[title]</a> </h1>
				<p class=\"meta\">
					$entry[image]$entry[authorinfo] $entry[categories]
				</p>
				$entry[image]$entry[subhead]
				<p>
					$entry[preview]
				</p>
				<p class=\"readmore\">
					<a href=\"$entry[link]\">read more</a>
				</p>
				";

		$previews = NULL;
		foreach($entries as $e) {
			/*
 			* Only extract the elements we need for display
 			*/
			$e['created'] = date('F d, Y', $e['created']);
			$e['thumb'] = Utilities::formatImageThumb($e);
			$e['link'] = "/{$this->url0}/" . urlencode(str_replace('"', '', $e['title'])) . "/";
			$e['categories'] = $this->formatCategories($e['data2']);
			$e['preview'] = Utilities::textPreview($e['body']);
			if($_SESSION['loggedIn']==1) {
				$simpleadmin = $this->admin_simple_options($this->url0, $e['id']);
			} else {
				$admin = NULL;
				$simpleadmin = NULL;
			}

			$previews .= "
				<div class=\"preview\">
					<a href=\"$e[link]\">$e[thumb]</a>
					<h3> <a href=\"$e[link]\">$e[title]</a> </h3>$simpleadmin
					<p>
						$e[preview]
					</p>
					<p class=\"readmore\">
						<a href=\"$e[link]\">read more</a>
					</p>
				</div><!-- end preview -->";
		}

		return "
			$admin$latest<div id=\"blog_preview\">
				<h1> Recent Entries </h1>
				<p class=\"blog_older\">
					<a href=\"/blog/category/recent/2/\">Older Entries &#187;</a>
				</p>$previews
			</div><!-- end blog_preview -->";
	}

	public function displayEntry($entries)
	{
		$entry = NULL;
		foreach($entries as $e) {
			/*
 			* Only extract the elements we need for display
 			*/
			$e['image'] = Utilities::formatImage($e);
			$e['link'] = "/{$this->url0}/" . urlencode($e['title']) . "/";
			$e['subhead'] = !empty($e['data1']) ? "<h2> $e[data1] </h2>" : NULL;
			$e['authorinfo'] = $this->formatAuthorInfo($e);
			$e['categories'] = $this->formatCategories($e['data2']);

			$entry = $this->admin_entry_options($this->url0, $e['id']);

			/*
			 * Adjust width of embedded video to fit the max width
			 */
			$pattern[0] = "/<(object|embed)(.*?)(width|height)=\"[\d]+\"(.*?)(width|height)=\"[\d]+\"/i";
			$replacement[0] = '<$1$2width="' . PAGE_OBJ_WIDTH . '"$4height="' . PAGE_OBJ_HEIGHT . '"';
			$e['body'] = preg_replace($pattern, $replacement, $e['body']);

			/*
			 * Load comments for the blog
			 */
			$cmnt = new comments();
			$comments = $cmnt->showEntryComments($e['id']);

			$entry .= "\n\t\t\t\t<div class=\"entry-title\">\n\t\t\t\t\t<h2> "
				. $e['title'] . "</h2>" . "\n\t\t\t\t</div>". $e['image'] 
				. $e['body'] . "<p class=\"meta\">\n\t\t\t\t\t" . $e['authorinfo']
				. "<br />\n\t\t\t\t\t" . $e['categories'] . ".<br />\n\t\t\t\t\t"
				. "<a class=\"a2a_dd\" href=\"http://www.addtoany.com/share_save\">"
				. "\n\t\t\t\t\t\t<img src=\"http://static.addtoany.com/buttons"
				. "/share_save_171_16.png\" width=\"171\" height=\"16\" "
				. "border=\"0\" alt=\"Share/Bookmark\"/>\n\t\t\t\t\t</a>"
				. "\n\t\t\t\t\t<script type=\"text/javascript\">a2a_linkname="
				. "document.title;a2a_linkurl=location.href;</script>"
				. "\n\t\t\t\t\t<script type=\"text/javascript\" src=\"http://"
				. "static.addtoany.com/menu/page.js\"></script>\n\t\t\t\t</p>"
				. $this->generateGetResponseSubscribe() . $comments;
		}

		return $entry;
	}

	private function formatAuthorInfo($e)
	{
		$date = date('M d, Y', $e['created']);
		return "Posted $date by $e[author].";
	}

	private function formatCategories($categories)
	{
		$markup = ($categories) ? 'This entry is filed under ' : NULL;

		$c = array_map('trim', explode(',', $categories));
		
		for($i=0, $count=count($c); $i<$count; ++$i) {
			$category = str_replace(' ', '-', $c[$i]);
			$markup .= "<a href=\"/{$this->url0}/category/$category/\">{$c[$i]}</a>";
			$comma = ($count > 2) ? ',' : NULL;
			if ( $i < $count-2 )
				$markup .= $comma.' ';
			if ( $i == $count-2 )
				$markup .= $comma.' and ';
		}

		return $markup;
	}

	static function displayPopularCategories($n=10)
	{
		$cat = self::getPopularCategories();

		echo "<ul class=\"cat-list\">\n";

		$i = 0;
		foreach($cat as $category => $number)
		{
			if(++$i > 16)
			{
				break;
			}
			elseif($i%9==0)
			{
				echo "\t\t\t\t\t</ul>\n\t\t\t\t\t<ul class=\"cat-list\">\n";
			}

			echo "\t\t\t\t\t\t<li> <a href=\"/blog/category/", $category, "/\">\n", 
				str_replace('-', ' ', $category), "</a></li>";
		}

		echo "\t\t\t\t\t</ul>\n";
	}

	private function generateGetResponseSubscribe()
	{
		/*
		 * If the site doesn't use GetResponse, return NULL
		 */
		if ( GETRESPONSE_CAMPAIGN_NAME=='' ) return NULL;

		/*
		 * Load custom form info
		 */
		$url = SITE_URL;
		$campaign_name = GETRESPONSE_CAMPAIGN_NAME;
		$teaser = GETRESPONSE_TEASER;
		$submit = GETRESPONSE_SUBMIT;

		return "\n\t\t\t\t<div id=\"subscribe\">
					<form action=\"http://www.getresponse.com/cgi-bin/add.cgi\"
							method=\"post\" accept-charset=\"UTF-8\">
						<fieldset id=\"blog-footer-capture\">
							<p>$teaser</p>
							<label for=\"subscriber_name\">Name</label>
							<input id=\"subscriber_name\" name=\"subscriber_name\" 
								type=\"text\" value=\"\" />
							<label for=\"subscriber_email\">Email</label>
							<input id=\"subscriber_email\" name=\"subscriber_email\" 
								type=\"text\" value=\"\" />
							<input type=\"submit\" value=\"$submit\" />
							<input type=\"hidden\" name=\"error_url\" id=\"error_url\" 
								value=\"\" />
							<input type=\"hidden\" name=\"confirmation_url\" 
								id=\"confirmation_url\" value=\"$url#signedup\" />
							<input type=\"hidden\" name=\"campaign_name\" 
								id=\"campaign_name\" value=\"$campaign_name\" />
							<input type=\"hidden\" name=\"custom_ref\" id=\"custom_ref\" 
								value=\"\" />
							<span class=\"no-spam\"><strong>Spam Free Zone</strong> Your 
							email address will not be shared or solicited</span>
						</fieldset>
					</form>
				</div>";
	}

	private function getPopularCategories()
	{
		$category_array = array();
		$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

		$sql = "SELECT data2
				FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
				WHERE page='blog'";
		if($stmt = $db->prepare($sql))
		{
			$stmt->execute();
			$stmt->bind_result($categories);
			while($stmt->fetch())
			{
				$temp_array = explode(',', strtolower($categories));
				foreach($temp_array as $category)
				{
					$c = str_replace(' ', '-', trim($category));
					if(array_key_exists($c, $category_array))
					{
						$category_array[$c] += 1;
					}
					else
					{
						$category_array[$c] = 1;
					}
				}
			}
			$stmt->close();
		}

		arsort($category_array);
		return $category_array;
	}

	static function displayRecentPosts($num=8, $page='blog')
	{
		$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$sql = "SELECT title
				FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
				WHERE page='$page'
				ORDER BY created DESC
				LIMIT $num";
		if($stmt = $db->prepare($sql))
		{
			$list = NULL;
			$stmt->execute();
			$stmt->bind_result($title);
			while($stmt->fetch())
			{
				$url = SITE_URL . "/" . $page . "/" . urlencode($title);
				$list .= "\n\t\t\t\t\t\t<li><a href=\"$url\">$title</a></li>";
			}
			$stmt->close();
		}
		return "\t\t\t\t\t<ul id=\"latest-blogs\">" . $list . "\t\t\t\t\t</ul>\n";
	}
}

?>