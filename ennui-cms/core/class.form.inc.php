<?php

/**
 * Methods to create forms
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Form
{

    public $form=NULL;

    public function __construct()
    {

    }

    public function displayForm()
    {
        return self::form;
    }

    public function __toString()
    {
        return self::displayForm();
    }

}
