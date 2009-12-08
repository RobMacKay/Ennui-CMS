<?php

class missing extends Page
{

	public function displayPublic()
	{
		return "\n<h2>That Page Doesn't Exist</h2>\n<p>If you feel you've "
			."reached this page in error, please <a href=\"mailto:"
			. SITE_CONTACT_EMAIL . "\">contact the site administrator</a> and "
			. "let us know.</p>\n<p>Sorry for the inconvenience!</p>\n";
	}

}

?>