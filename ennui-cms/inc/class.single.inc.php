<?php

/**
 * Methods to display and edit pages with only one entry
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Single extends Page
{

    /**
     * Loads the page entry and outputs HTML markup to display it
     *
     * @return string the formatted entry
     */
    public function displayPublic()
    {
        /*
         * Check if the user is logged in and attempting to edit an entry
         */
        if ( isset($this->url1) && $this->url1=='admin'
                && isset($_SESSION['user'])
                && $_SESSION['user']['clearance']>=1 )
        {
            /*
             * Load the entry ID if one was passed
             */
            $id = isset($this->url2) ? (int) $this->url2 : NULL;

            /*
             * Output the admin controls
             */
            return $this->displayAdmin($id);
        }

        /*
         * Load the entries
         */
        $entries = $this->getAllEntries(1);

        /*
         * Output the markup
         */
        return $this->displayEntry($entries);
    }

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
        $markup .= $this->createFormInput('title', 'Page Title', $id);
        $markup .= $this->createFormInput('body', 'Body Text', $id);
        $markup .= $form['end'];

        return $markup;
    }

    /**
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function displayEntry($entries)
    {
        /*
         * Extracts the ID of the entry if one was supplied
         */
        $id = isset($entries[0]['id']) ? $entries[0]['id'] : NULL;

        /*
         * If logged in, loads the admin options for the entry
         */
        $admin = $this->admin_entry_options($this->url0, $id, false);

        /*
         * If an entry exists, load the template and insert the data into it
         */
        if( isset($entries[0]['title']) )
        {
            /*
             * Store the entries in the entry array for templating purposes
             */
            $entries[0]['admin'] = $admin;

            /*
             * Set the template file
             */
            $template_file = $this->url0 . '.inc';
        }

        /*
         * If no entry exists, output some default text to avoid a broken layout
         */
        else
        {
            /*
             * Set default values if no entries are found
             */
            $entries[0] = array(
                    'admin' => $admin,
                    'title' => "No Entry Found",
                    'body' => "<p>That entry doesn't appear to exist.</p>"
                );

            /*
             * Load the default template
             */
            $template_file = $this->url0 . '.inc';
        }

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        /*
         * Return the entry as formatted by the template
         */
        return UTILITIES::parseTemplate($entries, $template);
    }

}
