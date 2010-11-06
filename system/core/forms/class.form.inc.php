<?php

/**
 * A class to build HTML forms
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
class Form
{

    public $legend = NULL,
           $notice = NULL,
           $page,
           $action = "",
           $form_id = NULL,
           $entry_id = "",
           $input_arr = array(),
           $entry;

    protected $form_action = FORM_ACTION,
              $class = "ecms-form",
              $name = "ecms-form",
              $enctype = "multipart/form-data",
              $include_default_inputs = TRUE,
              $method = "post";

    private $_inputs = array();

    public function __construct( $config=array() )
    {
        // Loops through each element of the configuration array and sets props
        foreach( $config as $key=>$val )
        {
            if ( isset($this->$key) )
            {
                $this->$key = htmlentities($val, ENT_QUOTES);

                // Check if a value exists for the entry
                if( $key==='name' && array_key_exists($val, $this->entry) )
                {
                    $this->value = $this->entry[$val];
                }
            }
        }

        // Make sure a page was set
        if( empty($this->page) )
        {
            $this->page = DB_Actions::get_default_page();
        }
    }

    public function input()
    {
        // Add the new input to the input array after sanitizing
        try
        {
            // Get the arguments passed to the method
            $args = func_get_args();

            // If the arguments were passed in an array, execute accordingly
            if ( is_array($args[0]) )
            {
                $this->_inputs[] = new Input($args[0]);
                return;
            }

            // Otherwise call the method with individual arguments
            else
            {
                list($typ, $lab, $val, $nam, $id, $cls) = $args;
                $this->_inputs[] = new Input($typ, $lab, $val, $nam, $id, $cls);
            }
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }
    }

    public function output()
    {
        // If no custom legend was supplied, set one here
        if( !isset($this->legend) )
        {
            if( isset($this->entry) )
            {
                $this->legend = "Edit This Entry";
            }
            else
            {
                $this->legend = "Create a New Entry";
            }
        }

        // If an ID was set for the form, apply it
        $form_id = isset($this->form_id) ? " id=\"$this->form_id\"" : NULL;

        // Check if default inputs should be added
        $this->_generateDefaultInputs();

        // Load inputs for the form
        $inputs = $this->_generateInputs();

        return <<<FORM

<form action="$this->form_action"
      method="$this->method"
      enctype="$this->enctype"
      class="$this->class"
      name="$this->name">
    <fieldset$form_id>
        <legend>$this->legend</legend>
        $this->notice
        $inputs
    </fieldset>
</form><!-- end .$this->class -->

FORM;
    }

    public function __toString()
    {
        return $this->output();
    }

    private function _generateInputs()
    {
        if( count($this->input_arr)>0 )
        {
            foreach( $this->input_arr as $input )
            {
                $this->input($input);
            }
        }

        $inputs = NULL;
        foreach( $this->_inputs as $input )
        {
            if( empty($input->id) )
            {
                $input->id = $input->name;
            }

            if( !empty($input->label) )
            {
                $label = "
            <label for=\"$input->id\">$input->label</label>";
            }
            else
            {
                $label = NULL;
            }

            // Get the value of the input if one exists
            if( is_object($this->entry)
                    && isset($this->entry->{$input->name}) )
            {
                $input->value = $this->entry->{$input->name};
            }

            // Formatting string to be used for additional attributes
            $fmt = "\n" . str_repeat(' ', 19);

            // Add non-empty attributes to the input
            $class = !empty($input->class) ? $fmt.'class="'.$input->class.'"' : NULL;
            $id = !empty($input->id) ? $fmt.'id="'.$input->id.'"' : NULL;
            $val = !empty($input->value) ? $fmt.'value="'.$input->value.'"' : NULL;

            // If it's a textarea
            if( $input->type==='textarea' )
            {
                $inputs .= "
            $label
            <textarea name=\"$input->name\"$id$class>$input->value</textarea>";
            }

            // If it's a select
            else if( $input->type==='select' )
            {
                // Make sure there are options for it
                if( property_exists($input, 'options') )
                {
                    $options = array();
                    foreach( $input->options as $option )
                    {
                        // If editing, start with the current page type
                        if( property_exists($this->entry, $input->name)
                                && $this->entry->{$input->name}===$option )
                        {
                            $sel = ' selected="selected"';
                        }
                        else
                        {
                            $sel = NULL;
                        }

                        $options[] = '<option' . $sel . '>' . $option . '</option>';
                    }

                    // Format the options
                    $opts = implode($fmt, $options);

                    $inputs .= "$label
            <select name=\"$input->name\"$id$class>$opts
            </select>";
                }
                else
                {
                    throw new Exception("Select type inputs need an 'options' value!");
                }
            }

            // If it's a file input
            else if( $input->type==='file' )
            {
                // Build the HTML for the input and the stored value
                $inputs .= "$label
            <input type=\"$input->type\"
                   name=\"$input->name\"$id$class />
            <input type=\"hidden\"
                   name=\"$input->name-value\"$val />\n";
            }

            // Otherwise use a standard input tag
            else
            {
                // Build the HTML for the input
                $inputs .= "$label
            <input type=\"$input->type\"
                   name=\"$input->name\"$id$class$val />\n";
            }
        }

        return $inputs;
    }

    private function _generateDefaultInputs()
    {
        // If $include_default_inputs is set to TRUE, adds them to the array
        if ( $this->include_default_inputs===TRUE )
        {
            // Hidden page identifier input
            $page_config = array(
                    'type' => 'hidden',
                    'name' => 'page',
                    'value' => $this->page
                );
            $this->_inputs[] = new Input($page_config);

            // Hidden action identifier input
            $action_config = array(
                    'type' => 'hidden',
                    'name' => 'action',
                    'value' => $this->action
                );
            $this->_inputs[] = new Input($action_config);

            // Hidden ID identifier input
            $id_config = array(
                    'type' => 'hidden',
                    'name' => 'entry_id',
                    'value' => $this->entry_id
                );
            $this->_inputs[] = new Input($id_config);

            // Hidden token identifier input
            $token_config = array(
                    'type' => 'hidden',
                    'name' => 'token',
                    'value' => $_SESSION['ecms']['token']
                );
            $this->_inputs[] = new Input($token_config);
        }
    }
}
