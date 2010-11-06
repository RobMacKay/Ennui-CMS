<?php

/**
 * Methods to display and edit pages with a single entry
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
class Single extends Page implements Page_Template
{

    /**
     * Loads the page entry and outputs HTML markup to display it
     *
     * @return string the formatted entry
     */
    public function display_public()
    {
        // Check if the user is logged in and attempting to edit an entry
        if ( isset($this->url1) && $this->url1==='admin'
                && AdminUtilities::check_clearance(1) )
        {
            // Load the entry ID if one was passed
            $id = isset($this->url2) ? (int) $this->url2 : NULL;

            // Output the admin controls
            return $this->display_admin($id);
        }

        // Load the entries
        $this->get_all_entries();

        // Set the template file
        $this->template = $this->url0 . '.inc';

        // Organize the data
        $this->generate_template_tags();

        // Return the entry as formatted by the template
        return $this->generate_markup();
    }

    /**
     * Outputs the editing controls for a given entry
     *
     * @param int $id the ID of the entry to be edited
     * @return string HTML markup to display the editing form
     */
    public function display_admin(  )
    {
        try
        {
            // Load form values
            $this->get_entry_by_id((int) $_POST['entry_id']);

            // Create a new form object and set submission properties
            $form = new Form();

            // Set up hidden form values
            $form->page = $this->url0;
            $form->action = 'entry-write';
            $form->entry_id = (int) $_POST['entry_id'];

            // Make the entry values available to the form if they exist
            $form->entry = isset($this->entries[0]) ? $this->entries[0] : array();

            // Set up input information
            $form->input_arr = array(
                array(
                    'name'=>'title',
                    'label'=>'Entry Title'
                ),
                array(
                    'type' => 'textarea',
                    'name'=>'entry',
                    'label'=>'Entry Body'
                ),
                array(
                    'type' => 'textarea',
                    'name'=>'excerpt',
                    'label'=>'Excerpt (Meta Description)'
                ),
                array(
                    'type' => 'submit',
                    'name' => 'form-submit',
                    'value' => 'Save Entry'
                )
            );

            return $form;
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }
    }

    /**
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function generate_template_tags()
    {
        parent::generate_template_tags();
    }

}
