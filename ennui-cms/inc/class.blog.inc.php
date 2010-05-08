<?php

class Blog extends Page
{
    public function displayPublic()
    {
        // If an entry URL is passed, load the corresponding data
        //TODO: Rewrite this for consistency. Choose either "tag" or "category"
        if ( isset($this->url1) && $this->url1!='category' )
        {
            $entries = $this->getEntryByUrl($this->url1);
            return $this->displayFull($entries);
        }

        // If viewing by category, load the corresponding entries
        else if ( $this->url1=='category' && isset($this->url2) )
        {
            // The page of entries to display
            $url3 = isset($this->url3) ? $this->url3 : 1;

            // What entry to use as the starting point
            $start_num = BLOG_PREVIEW_NUM*$url3-BLOG_PREVIEW_NUM;
            if($start_num< 0)
            {
                $start_num = 0;
            }

            // If this is an actual category, load corresponding entries
            if($this->url2!='recent')
            {
                $entries = $this->getEntriesByCategory($this->url2, BLOG_PREVIEW_NUM, $start_num);
            }

            // If recent entries are being displayed, load them here
            else
            {
                $entries = $this->getAllEntries(BLOG_PREVIEW_NUM, $start_num);
            }

            return $this->displayPreview($entries);
        }

        // If no parameters were passed, get the latest entries
        else
        {
            $entries = $this->getAllEntries(BLOG_PREVIEW_NUM);
            return $this->displayPreview($entries);
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

    protected function displayPreview($entries)
    {
        $entry = $this->admin_general_options($this->url0);

        $entry_array = array();
        if ( isset($entries[0]['title']) )
        {
            foreach ( $entries as $e )
            {
                $e['site-url'] = SITE_URL;

                // Format the date from the timestamp
                $e['date'] = date('F d, Y', $e['created']);

                // Image options
                if ( !empty($e['img']) )
                {
                    // Display the latest two galleries
                    $e['image'] = $e['img'];
                    $e['preview'] = str_replace(IMG_SAVE_DIR, IMG_SAVE_DIR.'preview/', $e['img']);
                    $e['thumb'] = str_replace(IMG_SAVE_DIR, IMG_SAVE_DIR.'thumbs/', $e['img']);
                }
                else
                {
                    $e['image'] = '/assets/images/no-image.jpg';
                    $e['preview'] = '/assets/images/no-image.jpg';
                    $e['thumb'] = '/assets/images/no-image-thumb.jpg';
                }

                $e['comment-count'] = comments::getCommentCount($e['id']);
                $e['comment-text'] = $e['comment-count']==1 ? "comment" : "comments";

                $e['url'] = !empty($e['data6']) ? $e['data6'] : urlencode($e['title']);
                $e['tags'] = $this->_formatTags($e['data2']);
                $e['admin'] = $this->admin_simple_options($this->url0, $e['id']);

                $entry_array[] = $e;
            }

            $template_file = $this->url0 . '-preview.inc';
        }

        else
        {
            $entry_array[] = array(
                    'admin' => NULL,
                    'title' => 'No Entry Found',
                    'body' => "<p>That entry doesn't appear to exist.</p>"
                );
            $template_file = 'blog-preview.inc';
        }

        if ( $this->url1=="category" )
        {
            $extra['header']['title'] = "Entries Tagged with "
                . ucwords(str_replace('-', ' ', $this->url2));
        }
        else
        {
            $extra['header']['title'] = "Recent Entries";
        }

        $extra['footer']['pagination'] = $this->paginateEntries();

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        $entry .= UTILITIES::parseTemplate($entry_array, $template, $extra);

        return $entry;
    }

    protected function displayFull($entries)
    {
        $entry = NULL;
        if ( isset($entries[0]['title']) )
        {
            foreach($entries as $e)
            {
                $e['admin'] = $this->admin_entry_options($this->url0, $e['id']);

                $e['site-url'] = SITE_URL;

                // Format the date from the timestamp
                $e['date'] = date('F d, Y', $e['created']);

                // Image options
                if ( !empty($e['img']) )
                {
                    // Display the latest two galleries
                    $e['image-url'] = $e['img'];
                    $e['preview-url'] = str_replace(IMG_SAVE_DIR, IMG_SAVE_DIR.'preview/', $e['img']);
                    $e['thumb-url'] = str_replace(IMG_SAVE_DIR, IMG_SAVE_DIR.'thumbs/', $e['img']);
                    $e['image-caption'] = isset($e['imgcap']) ? $e['imgcap'] : $e['title'];
                }
                else
                {
                    $e['image-url'] = '/assets/images/no-image.jpg';
                    $e['preview-url'] = '/assets/images/no-image.jpg';
                    $e['thumb-url'] = '/assets/images/no-image-thumb.jpg';
                    $e['image-caption'] = "No image supplied for this entry!";
                }

                $e['url'] = !empty($e['data6']) ? $e['data6'] : urlencode($e['title']);
                $e['permalink'] = SITE_URL . $this->url0 . "/" . $e['url'];

                $e['tags'] = $this->_formatTags($e['data2']);

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
                $e['comments'] = $cmnt->showEntryComments($e['id']);

                $entry_array[] = $e;

                $template_file = $this->url0 . '-full.inc';
            }
        }

        /*
         * Logically, there should be no way for this method to be called
         * without a valid entry to display. Better safe than sorry, though...
         */
        else
        {
            $entry_array[] = array(
                    'admin' => NULL,
                    'title' => 'No Entry Found',
                    'body' => "<p>That entry doesn't appear to exist.</p>"
                );
            $template_file = 'blog-full.inc';
        }

        if ( isset($_SERVER['HTTP_REFERER'])
                && strpos($_SERVER['HTTP_REFERER'], SITE_URL) )
        {
            $extra['footer']['backlink'] = $_SERVER['HTTP_REFERER'];
        }
        $extra['footer']['backlink'] = "/blog";

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        $entry .= UTILITIES::parseTemplate($entry_array, $template, $extra);

        return $entry;
    }

    private function _formatTags($tags)
    {
        $markup = NULL;

        $c = array_map('trim', explode(',', $tags));

        for($i=0, $count=count($c); $i<$count; ++$i) {
            $tag = str_replace(' ', '-', $c[$i]);
            $markup .= "<a href=\"/{$this->url0}/category/$tag/\">{$c[$i]}</a>";
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
        foreach ( $cat as $category => $number )
        {
            if ( ++$i>$n )
            {
                break;
            }
            else if ( $i%($n/2+1)==0 )
            {
                echo "\t\t\t\t\t</ul>\n\t\t\t\t\t<ul class=\"cat-list\">\n";
            }

            echo "\t\t\t\t\t\t<li> <a href=\"/blog/category/", $category, "/\">\n",
                str_replace('-', ' ', $category), "</a></li>";
        }

        echo "\t\t\t\t\t</ul>\n";
    }

    private function getPopularCategories()
    {
        //TODO: Convert to PDO
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

    static function displayPosts($num=8, $page='blog', $filter="recent")
    {
        // Determine which posts to retreive
        if ( $filter=="recent" )
        {
            $filter_sql = "WHERE page='$page'";
        }
        elseif ( $filter=="featured" )
        {
            $filter_sql = "WHERE page='$page' AND data5='1'";
        }

        //TODO: Convert to PDO
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $sql = "SELECT title,data6
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                $filter_sql
                ORDER BY created DESC
                LIMIT $num";
        try
        {
            $stmt = $db->prepare($sql);
            $list = NULL;
            $stmt->execute();
            $stmt->bind_result($title, $data6);
            while($stmt->fetch())
            {
                $url = isset($data6) ? $data6 : urlencode($title);
                $link = SITE_URL . $page . "/" . $url;
                $list .= "
                        <li><a href=\"$link\">$title</a></li>";
            }
            $stmt->close();
        }
        catch ( Exception $e )
        {
            FB::error($e);
            throw new Exception ( "Could not load entries." );
        }
        return "
                    <ul id=\"latest-blogs\">$list
                    </ul>";
    }

	static function displayMostCommented($db=NULL, $num=8, $page='blog')
	{
		if ( !isset($db) )
		{
			$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		}

		/*
		 * Load comment counts and titles for the
		 */
		$sql = "SELECT COUNT(blogCmnt.id) AS num_comments, title, data6
				FROM blogCmnt
				LEFT JOIN entryMgr
					ON (blogCmnt.bid=entryMgr.id)
				GROUP BY blogCmnt.bid
				ORDER BY num_comments DESC
				LIMIT 8";
		try
		{
			$stmt = $db->query($sql);
			$list = NULL;
            if ( !is_object($stmt) )
            {
                throw new Exception ( "No entries found." );
            }
			while ( $entry = $stmt->fetch_object() )
			{
				$text = $entry->title;
                $url = isset($entry->data6) ? $entry->data6 : urlencode($entry->title);
				$link = "/$page/" . $url;
				$list .= "
                        <li><a href=\"$url\">$text</a></li>";
			}
			return "
                    <ul id=\"most-commented\">$list
                    </ul>";
		}
		catch ( Exception $e )
		{
			FB::log($e);
            throw new Exception ( "Couldn't load popular entries." );
		}
	}
}
