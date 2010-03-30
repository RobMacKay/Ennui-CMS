<?php

/**
 * Methods to display and edit pages with multiple simple entries
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
class Multi extends Page
{

    /**
     * Loads the page entries and outputs HTML markup to display them
     *
     * @return string the formatted entries
     */
    public function displayPublic()
    {
        /*
         * If an entry URL is passed, load that entry only and output it
         */
        if ( isset($this->url1) && $this->url1!='more' && $this->url1!='admin' )
        {
            $entries = $this->getEntryByUrl($this->url1);
            return $this->displayFull($entries);
        }

        elseif ( isset($this->url1) && $this->url1=='admin'
            && isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            $id = isset($this->url2) ? (int) $this->url2 : NULL;
            return $this->displayAdmin($id);
        }

        /*
         * Displays the entries for the page
         */
        else
        {
            $limit = MAX_ENTRIES_PER_PAGE; // Number of entries per page

            /*
             * If the entries are paginated, this determines what page to show
             */
            if(isset($this->url1) && $this->url1=='more')
            {
                $offset = (isset($this->url2)) ? $limit*($this->url2-1) : 0;
            }
            else
            {
                $offset = 0;
            }

            /*
             * Load entries and pass them to be formatted
             */
            $entries = $this->getAllEntries($limit, $offset);
            return $this->displayPreview($entries);
        }
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
        $markup .= $this->createFormInput('title', 'Headline', $id);
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

                // Format the image if one exists
                $e['image'] = isset($e['img']) ? Utilities::formatImageSimple($e) : NULL;

                // Create a text preview for the entry
                $e['preview'] = UTILITIES::textPreview($e['body'], 45);

                $entry_array[] = $e;
            }

            /*
             * Load the template into a variable
             */
            $template = UTILITIES::loadTemplate($this->url0.'-preview.inc');

            $entry .= UTILITIES::parseTemplate($entry_array, $template);
        } else {
            $entry .= "
                    <h2> No Entry Found </h2>
                    <p>
                        Log in to create this entry.
                    </p>";
        }

        return $entry;
    }

    protected function displayFull($entries)
    {
        $id = (isset($entries[0]['id'])) ? $entries[0]['id'] : NULL;
        $entry = $this->admin_entry_options($this->url0, $id, false);

        $entry_array = array();
        foreach($entries as $e) {
            // Entry options for the admin, if logged in
            $e['admin'] = $this->admin_simple_options($this->url0, $e['id']);

            $e['image'] = isset($e['img']) ? Utilities::formatImageSimple($e) : NULL;

            $entry_array[] = $e;
        }

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($this->url0.'-full.inc');

        $entry .= UTILITIES::parseTemplate($entry_array, $template);

        return $entry;
    }

}
