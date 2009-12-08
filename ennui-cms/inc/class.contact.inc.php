<?php

class Contact extends Page
{
	public function displayPublic()
	{
		$siteName = SITE_NAME;
		return <<<DISPLAY

		<form action="/inc/update.inc.php" method="post" id="contact">
			<fieldset id="cf">
				<h2> Contact $siteName </h2>
				<label for="cf_n">Name</label>
				<input type="text" name="cf_n" id="cf_n" />
				<label for="cf_e">Email</label>
				<input type="text" name="cf_e" id="cf_e" />
				<label for="cf_p">Phone Number (optional)</label>
				<input type="text" name="cf_p" id="cf_p" />
				<label for="cf_m">Enter Your Message Here</label>
				<textarea name="cf_m" id="cf_m" rows="18" cols="35"></textarea>
				<input type="hidden" name="page" value="$this->url0" />
				<input type="hidden" name="action" value="contact_form" />
				<input type="submit" name="cf_s" id="cf_s" value="Send This Message" />
			</fieldset>
		</form>
DISPLAY;
	}

	public function sendMessage($p)
	{
		$msg_to = SITE_CONTACT_NAME." <".SITE_CONTACT_EMAIL.">";
		$msg_sub = "[".SITE_NAME."] New Message from the Contact Form";
		$siteUrl = SITE_URL;

		// Sanitize the form data and load into variables
		list($name, $email, $website, $phone, $message) = $this->checkMessage($p);

		$headers = <<<MESSAGE_HEADER
From: $name <$email>
Content-Type: text/plain
MESSAGE_HEADER;
		$msg_body = <<<MESSAGE_BODY
Name:  $name
Email: $email
URL:   $website
Phone: $phone

Message:

$message

--
This message was sent via the contact form on $siteUrl
MESSAGE_BODY;
		if(!mail($msg_to,$msg_sub,$msg_body,$headers))
		{
			return false;
		}
		else
		{
			return $this->sendConfirmation($p);
		}
	}

	private function sendConfirmation($p)
	{
		// Sanitize the form data and load into variables
		list($name, $email, $website, $phone, $message) = $this->checkMessage($p);

		// Set site-specific variables from constants
		$siteName = SITE_NAME;
		$siteUrl = SITE_URL;
		$confMsg = SITE_CONFIRMATION_MESSAGE;
		$siteEmail = SITE_CONTACT_EMAIL;

		$conf_to  = "$name <$email>";
		$conf_sub = "Thank You for Contacting Us!";
		$conf_headers = <<<MESSAGE_HEADER
From: $siteName <donotreply@$siteUrl>
Content-Type: text/plain
MESSAGE_HEADER;
		$conf_message = <<<MESSAGE_BODY
$confMsg

$siteEmail
www.$siteUrl
MESSAGE_BODY;

		return mail($conf_to,$conf_sub,$conf_message,$conf_headers);
	}

	private function checkMessage($p)
	{
		$msg[] = (isset($_POST['cf_n'])) ? htmlentities($_POST['cf_n']) : NULL;
		$msg[] = (isset($_POST['cf_e'])) ? htmlentities($_POST['cf_e']) : NULL;
		$msg[] = (isset($_POST['cf_w']) && $_POST['cf_w'] != 'Website (optional)') ? htmlentities($_POST['cf_w']) : NULL;
		$msg[] = (isset($_POST['cf_p']) && $_POST['cf_p'] != 'Phone Number (optional)') ? htmlentities($_POST['cf_p']) : NULL;
		$msg[] = (isset($_POST['cf_m'])) ? htmlentities($_POST['cf_m']) : NULL;

		return $msg;
	}
}

?>