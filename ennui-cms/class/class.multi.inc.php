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
        // Lookup array for reserved values in $this->url1
        $reserved = array( 'more', 'admin', 'category' );

        // If an entry URL is passed, load that entry only and output it
        if ( isset($this->url1) && !in_array($this->url1, $reserved) )
        {
            // Load the entry by its URL
            $entries = $this->getEntryByUrl($this->url1);

            // Return the full display markup
            return $this->displayFull($entries);
        }

        // If logged in, show the admin options (if JavaScript is disabled)
        else if ( isset($this->url1) && $this->url1=='admin'
            && isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 )
        {
            // Extract the entry ID if one was passed
            $id = isset($this->url2) ? (int) $this->url2 : NULL;

            // Return the admin form markup
            return $this->displayAdmin($id);
        }

        // Displays the entries for the page
        else
        {
            // If the entries are paginated, this determines what page to show
            if ( isset($this->url1) && $this->url1=='more' )
            {
                $offset = isset($this->url2) ? $limit*($this->url2-1) : 0;
            }
            else
            {
                $offset = 0;
            }

            // If loading by category, get the proper number of entries
            if ( isset($this->url1) && $this->url1==='category'
                    && isset($this->url2) )
            {
                $offset = isset($this->url3) ? $limit*($this->url3-1) : 0;
                $cat = htmlentities($this->url2, ENT_QUOTES);

                // If no category was passed, go back to the main page
                if ( empty($cat) )
                {
                    header("Location: /$this->url0");
                    exit;
                }

                // Load entries by category
                $this->getEntriesByCategory($cat, $offset);
            }

            // Load most recent entries for a preview if no entry was selected
            else
            {
                $this->getAllEntries($offset);
            }

            // Return markup for entry previews
            return $this->displayPreview();
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
        try
        {
            // Create a new form object and set submission properties
            $form = new Form(array('legend'=>'Create a New Entry'));
            $form->page = 'test-page';
            $form->action = 'entry_write';
            $form->entry_id = $id;

            // Load form values
            $values = array_shift($this->getEntryById($id));

            // Set up input information
            $input_arr = array(
                array(
                    'name'=>'title',
                    'label'=>'Entry Title',
                    'value' => $values['title']
                ),
                array(
                    'type' => 'textarea',
                    'name'=>'entry',
                    'label'=>'Entry Body',
                    'value' => $values['entry']
                ),
                array(
                    'type' => 'textarea',
                    'name'=>'excerpt',
                    'label'=>'Excerpt (Meta Description)',
                    'value' => $values['excerpt']
                ),
                array(
                    'name'=>'slug',
                    'label'=>'URL',
                    'value' => $values['extra-field']
                ),
                array(
                    'type' => 'submit',
                    'name' => 'form-submit',
                    'value' => 'Save Entry'
                )
            );

            // Build the inputs
            foreach ( $input_arr as $input )
            {
                $form->input($input);
            }
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }

        return $form;
    }

    /**
     * Displays entries for the page as previews
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry previews
     */
    protected function displayPreview( )
    {
        /*
         * Initialize the $entry variable by loading admin options if the user
         * is logged in
         */
        $entry = $this->admin_general_options($this->url0);

        /*
         * If at least one entry exists, loop through entries and format them
         */
        if ( isset($this->entries[0]->title) )
        {
            $entry_array = array(); // Initialize the variable to avoid a notice

            /*
             * Loop through entries and create special pieces of information in
             * the entry array for the template
             */
            foreach ( $this->entries as &$e )
            {
                // Entry options for the admin, if logged in
                $e->admin = $this->admin_simple_options($this->url0, $e->entry_id);

                // Rename the URL for use in the template
                if ( empty($e->slug) )
                {
                    $e->slug = Utilities::makeUrl($e->title);
                }

                // Format the image if one exists
                $e->image = isset($e->img) ? Utilities::imageOptions($e) : NULL;
                $e->preview = Utilities::textPreview($e->entry, 20);

//                $this->entries[] = $e;
            }

            $template_file = $this->url0.'-preview.inc';
        }
        else
        {
            $entry = NULL;
            $admin = $this->admin_general_options($this->url0);
            $template_file = $this->setDefaultEntry($admin);
        }

        // Load the template into a variable
        $template = UTILITIES::loadTemplate($template_file);

        $entry .= UTILITIES::parseTemplate($this->entries, $template);

        return $entry;
    }

    protected function displayFull($entries)
    {
        $id = (isset($entries[0]['id'])) ? $entries[0]['id'] : NULL;
        $entry = $this->admin_entry_options($this->url0, $id, false);

        $entry_array = array();
        foreach($entries as $e)
        {
            // Entry options for the admin, if logged in
            $e->admin = $this->admin_simple_options($this->url0, $e->entry_id);

            // Format the image if one exists
            $e->image = isset($e->img) ? Utilities::imageOptions($e) : NULL;

            $entry_array[] = $e;
        }

        // Load the template into a variable
        $template = UTILITIES::loadTemplate($this->url0.'-full.inc');

        $entry .= UTILITIES::parseTemplate($entry_array, $template);

        return $entry;
    }

}
