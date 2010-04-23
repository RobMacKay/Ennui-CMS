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

}
