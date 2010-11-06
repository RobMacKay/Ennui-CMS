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
class Sampleclass extends Single
{

    protected function generate_template_tags()
    {
        parent::generate_template_tags();

        foreach( $this->entries as $entry )
        {
            $entry->sample = "Testing";
        }
    }

}
