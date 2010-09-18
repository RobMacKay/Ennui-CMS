<?php

/**
 * Class description
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
class Search extends Page
{
    public function displayPublic()
    {
        if ( !empty($this->url1) && !empty($this->url2) )
        {
            // The page of entries to display
            $url3 = isset($this->url3) ? $this->url3 : 1;

            // What entry to use as the starting point
            $start_num = BLOG_PREVIEW_NUM*$url3-BLOG_PREVIEW_NUM;
            if( $start_num<0 )
            {
                $start_num = 0;
            }

            // Sanitize the search string
            $page = htmlentities(urldecode(trim($this->url1)), ENT_QUOTES);
            $search = htmlentities(urldecode(trim($this->url2)), ENT_QUOTES);

            // Load the entries that match the search
            $entries = $this->getEntriesBySearch($search, $page, BLOG_PREVIEW_NUM, $start_num);

            return $this->displayResults($entries);
        }
        else
        {
            header("Location: /");
            exit;
        }
    }

    protected function displayResults($entries)
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
                if ( !empty($e['img']) && strlen($e['img'])>1 )
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
                $e['admin'] = $this->admin_simple_options($this->url0, $e['id']);

                $entry_array[] = $e;
            }

            $template_file = $this->url0 . '.inc';
        }

        else
        {
            $entry_array[] = array(
                    'admin' => NULL,
                    'title' => 'No Entries Found That Match Your Search',
                    'body' => "<p>No entries match that query.</p>"
                );
            $template_file = 'default.inc';
        }

        $extra['header']['title'] = 'Search Results for "'
                . urldecode($this->url2) . '" ('
                . $this->getEntryCountBySearch($this->url2, $this->url1)
                . ' entries found)';

        $extra['footer']['pagination'] = $this->paginateEntries();

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        $entry .= UTILITIES::parseTemplate($entry_array, $template, $extra);

        return $entry;
    }

	public static function displaySearchBox($page="blog", $submit_text="Search")
	{
		$form_action = FORM_ACTION;
		return "
                <form method=\"post\" id=\"search-form\"
                      action=\"$form_action\">
                    <fieldset>
                        <input type=\"text\" name=\"search_string\"
                               id=\"search-string\" class=\"textfield\" />
                        <input type=\"submit\" value=\"$submit_text\"
                               id=\"search-submit\" class=\"button\" />
                        <input type=\"hidden\" name=\"action\"
                               value=\"entry_search\" />
                        <input type=\"hidden\" name=\"page\"
                               value=\"search\" />
                        <input type=\"hidden\" name=\"search-page\"
                               value=\"$page\" />
                    </fieldset>
                </form>";
	}

}
