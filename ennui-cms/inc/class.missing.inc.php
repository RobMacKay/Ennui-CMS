<?php

class missing extends Page
{

	public function displayPublic()
	{
        $entry_array[] = array(
                'admin' => NULL,
                'title' => "That Page Doesn't Exist",
                'body' => "<p>If you feel you've reached this page in error, "
                        . "please <a href=\"mailto:" . SITE_CONTACT_EMAIL
                        . "\">contact the site administrator</a> and let "
                        . "us know.</p>\n<p>Sorry for the inconvenience!</p>"
            );
        $template_file = 'default.inc';

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        $entry = UTILITIES::parseTemplate($entry_array, $template);

        return $entry;
	}

}

?>