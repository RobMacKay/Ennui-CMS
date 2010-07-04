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

    public $action = FORM_ACTION,
           $legend = "Create a New Entry",
           $class = "ecms-form",
           $name = "ecms-form",
           $enctype = "multipart/form-data",
           $include_default_inputs = TRUE,
           $method = "post";

    private $_inputs = array();

    public function __construct( $config=array() )
    {
        if ( !is_array($config) )
        {
            throw new Exception('Var $config must be an array.');
        }

        // Loops through each element of the configuration array and sets props
        foreach( $config as $key=>$val )
        {
            if ( isset($this->$key) )
            {
                $this->$key = htmlentities($val, ENT_QUOTES);
            }
        }

        // If $include_default_inputs is set to TRUE, adds them to the array
        $this->_inputs[] = new Input(array("type"=>"hidden"));
    }

    public function input($config=array())
    {
        if ( !is_array($config) )
        {
            throw new Exception('Var $config must be an array.');
        }

        // Add the new input to the input array after sanitizing
        try
        {
            $this->_inputs[] = new Input($config);
        }
        catch ( Exception $e )
        {
            Error::logException($e);
        }
    }

    public function output()
    {
        // Load inputs for the form
        $inputs = $this->_generateInputs($input);

        return <<<FORM

<!-- Begin .$this->class -->
<form action="$this->action"
      method="$this->method"
      enctype="$this->enctype"
      class="$this->class"
      name="$this->name">
    <fieldset>
        <legend>$this->legend</legend>
        $inputs$defaults
    </fieldset>
</form><!-- End .$this->class -->

FORM;
    }

    public function __toString()
    {
        return $this->output();
    }

    private function _generateInputs()
    {
        $inputs = NULL;
        foreach ( $this->_inputs as $input )
        {
            if ( !empty($input->label) )
            {
                $label = "<label for=\"$input->id\">$input->label</label>";
            }
            else
            {
                $label = NULL;
            }

            // If it's a textarea
            if ( $input->type==='textarea' )
            {
                $inputs .= "
            $label
            <textarea name=\"$input->name\"
                   id=\"$input->id\"
                   class=\"$input->class\">$input->value</textarea>";
            }

            // Otherwise use a standard input tag
            else
            {
                $inputs .= "
            $label
            <input type=\"$input->type\"
                   name=\"$input->name\"
                   id=\"$input->id\"
                   class=\"$input->class\"
                   value=\"$input->value\" />";
            }
        }
        return $inputs;
    }
}
