<?php

/**
 * Makes the default preview gallery view a categorized list
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
class CategorizedGallery extends Gallery
{

protected function displayPreview($entries)
{
    if ( isset($this->url1) && $this->url1==='category' )
    {
        $categories = NULL;
    }
    else
    {
        $categories = $this->getEntryCategories($entries);
        $entries = array_slice($entries, 0, 2);
    }

    /*
     * Show the latest two entries
     */
    return parent::displayPreview($entries) . $categories;
}

private function getEntryCategories($entries)
{
    $cat_arr = array();
    if ( isset($entries[0]['data2']) )
    {
        foreach ( $entries as $e )
        {
            $cat_url = strtolower(Utilities::makeUrl($e['data2']));
            if ( !isset($cat_arr[$cat_url]) )
            {
                $cat_arr[$cat_url] = array(
                        'category-url' => $this->url0 . '/category/' . $cat_url,
                        'category-name' => $e['data2'],
                        'count' => 1
                    );
            }
            else
            {
                $cat_arr[$cat_url]['count'] += 1;
            }
        }

        /*
         * Sort the array
         */
        usort($cat_arr, "CategorizedGallery::cmp");

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($this->url0.'-category.inc');

        return UTILITIES::parseTemplate(array_values($cat_arr), $template);
    }

    /*
     * If no categories exist, there's no reason to display this view
     */
    else
    {
        return NULL;
    }
}

static function cmp($a, $b)
{
    if ( $a['count']===$b['count'] )
    {
        return 0;
    }
    return $a['count']<$b['count'] ? 1 : -1;
}

}
