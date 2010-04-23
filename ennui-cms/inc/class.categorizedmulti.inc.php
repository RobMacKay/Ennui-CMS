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
class CategorizedMulti extends Multi
{

    /**
     * Outputs the editing controls for a given entry
     *
     * @param int $id the ID of the entry to be edited
     * @return string HTML markup to display the editing form
     */
    public function displayAdmin($id)
    {
        $form = $this->createForm('write', $id);

        $markup = $form['start'];
        $markup .= $this->createFormInput('title', 'Headline', $id);
        $markup .= $this->createFormInput('data2','Category',$id);
        $markup .= $this->createFormInput('body','Description',$id);
        $markup .= $form['end'];

        return $markup;
    }

    /**
     * Displays entries for the page as previews
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry previews
     */
    protected function displayPreview($entries)
    {
        /*
         * Initialize the $entry variable by loading admin options if the user
         * is logged in
         */
        $entry = $this->admin_general_options($this->url0);

        /*
         * If at least one entry exists, loop through entries and format them
         */
        if ( isset($entries[0]['title']) )
        {
            $entry_array = array(); // Initialize the variable to avoid a notice

            /*
             * Loop through entries and create special pieces of information in
             * the entry array for the template
             */
            foreach ( $entries as $e )
            {
                // Entry options for the admin, if logged in
                $e['admin'] = $this->admin_simple_options($this->url0, $e['id']);

                // Rename the URL for use in the template
                $e['url'] = empty($e['data6']) ? urlencode($e['title']) : $e['data6'];

                // Create a text preview for the entry
                $e['preview'] = UTILITIES::textPreview($e['body'], 45);

                /*
                 * Store the category name and URL
                 */
                $e['category-url'] = "/$this->url0/category/"
                        . Utilities::makeUrl($e['data2']);
                $e['category-name'] = $e['data2'];

                $entry_array[] = $e;
            }

            $template_file = $this->url0.'-preview.inc';
        }
        else
        {
            $entry_array[] = array(
                    'title' => "No Entry Found",
                    'body' => "<p>That entry doesn't appear to exist.</p>"
                );

            $template_file = 'default.inc';
        }

        /*
         * Set up header and footer information
         */
        if ( $this->url1=='category' )
        {
            $name = $entry_array[0]['category-name'];
            $count = count($entry_array);
            $gal = $count==1 ? 'video' : 'videos';
            $extra['header'] = array(
                    'title' => "Viewing Category: $name ($count $gal)"
                );
        }
        else
        {
            $extra['header'] = array(
                    'title' => 'Latest Videos'
                );
        }

        $extra['footer'] = array(
                'page-url' => $this->url0,
                'page-name' => 'Videos'
            );

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        $entry .= UTILITIES::parseTemplate($entry_array, $template, $extra);

        return $entry;
    }

}
