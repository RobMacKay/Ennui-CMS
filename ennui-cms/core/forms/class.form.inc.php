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

    public $page = DEFAULT_PAGE,
           $action = "",
           $entry_id = "";

    protected $form_action = FORM_ACTION,
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
        // Check if default inputs should be added
        $this->_generateDefaultInputs();

        // Load inputs for the form
        $inputs = $this->_generateInputs();

        return <<<FORM

<!-- Begin .$this->class -->
<form action="$this->form_action"
      method="$this->method"
      enctype="$this->enctype"
      class="$this->class"
      name="$this->name">
    <fieldset>
        <legend>$this->legend</legend>
        $inputs
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
                    'value' => $_SESSION['token']
                );
            $this->_inputs[] = new Input($token_config);
        }
    }
}
