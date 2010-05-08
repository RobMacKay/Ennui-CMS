<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of classinputinc
 *
 * @author ennuidesign
 */
class Input
{

    public $type = "text",
           $name = NULL,
           $id = NULL,
           $attr= NULL,
           $val = NULL;

    public function __construct()
    {

    }

    private function _makeLabel( $label, $for=NULL )
    {
        if ( isset($for) )
        {
            $for_attr = ' for="' . preg_replace('/^\w-/', '', $for) . '"';
        }
        return "<label$for_attr>$label</label>";
    }

    private function _makeInput()
    {
        return '<input type="' . self::$type . '" name="' . self::$name . '" '
            . 'id="' . self::$id . '" val="' . self::$val . '" ' . self::$attr
            . '/>';
    }

}
