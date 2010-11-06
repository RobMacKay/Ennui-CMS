<?php

/**
 * Methods to display and edit a page with a contact form
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
class Contact extends Single
{
    /**
     * Loads the page entry and outputs HTML markup to display it
     *
     * @return string the formatted entry
     */
    public function display_public()
    {
        // Check if the user is logged in and attempting to edit an entry
        if( isset($this->url1) && $this->url1==='admin'
                && AdminUtilities::check_clearance(1) )
        {
            // Load the entry ID if one was passed
            $id = isset($this->url2) ? (int) $this->url2 : NULL;

            // Output the admin controls
            return $this->display_admin($id);
        }

        // Load the entries
        $this->get_all_entries();

        // Set the template file
        $this->template = $this->url0 . '.inc';

        // Organize the data
        $this->generate_template_tags();

        // Return the entry as formatted by the template
        return $this->generate_markup($this->entries);
    }

    /**
     * Generates HTML to display a given array of entries
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function generate_template_tags()
    {
        parent::generate_template_tags();

        // Add custom tags here
        foreach( $this->entries as $entry )
        {
            $entry->contact_form = $this->_generate_contact_form();
        }
    }

    private function _generate_contact_form()
    {
        // Define necessary variables
        $form_action = FORM_ACTION;
        $site_name = SITE_NAME;

        // Return the markup
        return <<<CONTACT_FORM
    <form action="$form_action" method="post" id="contact">
        <fieldset id="cf">
            <legend> Contact $site_name </legend>
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
            <input type="submit" name="cf_s" id="cf_s"
                   value="Send This Message" />
        </fieldset>
    </form>
CONTACT_FORM;
    }

    public function sendMessage()
    {
        $msg_to = SITE_CONTACT_NAME." <".SITE_CONTACT_EMAIL.">";
        $msg_sub = "[".SITE_NAME."] New Message from the Contact Form";
        $siteUrl = SITE_URL;

        // Sanitize the form data and load into variables
        list($name, $email, $phone, $msg) = $this->checkMessage();

        $headers = <<<MESSAGE_HEADER
From: $name <$email>
Content-Type: text/plain
MESSAGE_HEADER;
        $msg_body = <<<MESSAGE_BODY
Name:  $name
Email: $email
Phone: $phone

Message:

$msg

--
This message was sent via the contact form on $siteUrl
MESSAGE_BODY;
        if( !mail($msg_to,$msg_sub,$msg_body,$headers) )
        {
            return false;
        }
        else
        {
            return $this->sendConfirmation();
        }
    }

    private function sendConfirmation()
    {
        // Sanitize the form data and load into variables
        list($name, $email, $phone, $message) = $this->checkMessage($p);

        // Set site-specific variables from constants
        $siteName = SITE_NAME;
        $siteUrl = SITE_URL;
        $return_email = 'donotreply@' . str_replace('/', '', str_replace('http://', '', SITE_URL));
        $confMsg = SITE_CONFIRMATION_MESSAGE;
        $siteEmail = SITE_CONTACT_EMAIL;

        $conf_to  = "$name <$email>";
        $conf_sub = "Thank You for Contacting Us!";
        $conf_headers = <<<MESSAGE_HEADER
From: $siteName <$return_email>
Content-Type: text/plain
MESSAGE_HEADER;
        $conf_message = <<<MESSAGE_BODY
$confMsg

$siteEmail
$siteUrl
MESSAGE_BODY;

        return mail($conf_to,$conf_sub,$conf_message,$conf_headers);
    }

    private function checkMessage()
    {
        $msg[] = (isset($_POST['cf_n'])) ? htmlentities($_POST['cf_n']) : NULL;
        $msg[] = (isset($_POST['cf_e'])) ? htmlentities($_POST['cf_e']) : NULL;
        $msg[] = (isset($_POST['cf_p'])) ? htmlentities($_POST['cf_p']) : NULL;
        $msg[] = (isset($_POST['cf_m'])) ? htmlentities($_POST['cf_m']) : NULL;

        return array_map('stripslashes', $msg);
    }
}
