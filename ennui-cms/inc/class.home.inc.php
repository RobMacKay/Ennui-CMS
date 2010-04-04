<?php

/**
 * Custom class to handle the Mowi Fit homepage sidebar
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
class Home extends Single
{

public function displayPublic()
{
    // Create an object for the sidebar
    $sidebar = new Single(NULL, array('sidebar'));

    // Load the home page and append the sidebar
    return parent::displayPublic() . $sidebar->displayPublic();
}

}
