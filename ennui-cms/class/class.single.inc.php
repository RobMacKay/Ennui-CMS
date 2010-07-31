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

        // Load the entries
        $this->getAllEntries(1);

        // Organize the data and pass to a template for markup
        return $this->displayEntry();
    }

    /**
     * Outputs the editing controls for a given entry
     *
     * @param int $id the ID of the entry to be edited
     * @return string HTML markup to display the editing form
     */
    public function displayAdmin( $id )
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
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function displayEntry()
    {
        // Extracts the ID of the entry if one was supplied
        $id = isset($this->entries[0]->entry_id) ? $this->entries[0]->entry_id : NULL;

        // If logged in, loads the admin options for the entry
        $admin = $this->admin_entry_options($this->url0, $id, false);

        // If an entry exists, load the template and insert the data into it
        if( isset($this->entries[0]->title) )
        {
            // Store the entries in the entry array for templating purposes
            $this->entries[0]->admin = $admin;

            // Set the template file
            $template_file = $this->url0 . '.inc';
        }

        // If no entry exists, output some default text to avoid a broken layout
        else
        {
            // Set default values if no entries are found and load a template
            $template_file = $this->setDefaultEntry($admin);
        }

        // Load the template into a variable
        $template = UTILITIES::loadTemplate($template_file);

        // Return the entry as formatted by the template
        return UTILITIES::parseTemplate($this->entries, $template);
    }

}
