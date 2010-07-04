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
class Input
{

    public $type="text",
           $label="",
           $value="",
           $name="",
           $id="",
           $class="";

    public function __construct($config=array(), $options=NULL)
    {
        if ( !is_array($config) )
        {
            throw new Exception('The value passed in $config must be an array.');
        }

        foreach( $config as $key=>$val )
        {
            if ( isset($this->$key) )
            {
                $this->$key = htmlentities($val, ENT_QUOTES);
            }
        }
    }
}
