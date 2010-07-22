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
class Test extends Single
{

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
            FB::log($values);

            $form->input(array(
                    'name'=>'title',
                    'label'=>'Entry Title',
                    'value' => $values['title']
                ));
            $form->input(array(
                    'name'=>'extra-field',
                    'label'=>'Extra Information',
                    'value' => $values['extra-field']
                ));
            $form->input(array(
                    'type' => 'textarea',
                    'name'=>'entry',
                    'label'=>'Entry Body',
                    'value' => $values['entry']
                ));
            $form->input(array(
                    'type' => 'textarea',
                    'name'=>'excerpt',
                    'label'=>'Excerpt (Meta Description)',
                    'value' => $values['excerpt']
                ));
            $form->input(array(
                    'type' => 'submit',
                    'name' => 'form-submit',
                    'value' => 'Save Entry'
                ));
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }

        return $form;
    }

}
