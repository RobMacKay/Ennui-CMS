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
class SiteAdmin extends DB_Actions
{

    public function __construct(  )
    {

    }

    //TODO Add a menu
    public function display_site_options(  )
    {
        $options = new stdClass;

        if( AdminUtilities::check_clearance(1) )
        {
            return <<<SITE_OPTIONS

<ul id="admin-site-options">
    <li><a href="#">Edit Site Pages</a></li>
    <li><a href="#">Edit Entry Categories</a></li>
    <li><a href="#">Manage Site Administrators</a></li>
</ul><!-- end #admin-site-options -->
SITE_OPTIONS;
        }
        else
        {
            return NULL;
        }
    }

    //TODO

}
