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
     * Generates HTML to display a given array of entries with a contact form
     *
     * @param array $entries an array of entries to be formatted
     * @return string HTML markup to display the entry
     */
    protected function displayEntry($entries)
    {
        /*
         * Extracts the ID of the entry if one was supplied
         */
        $id = isset($entries[0]['id']) ? $entries[0]['id'] : NULL;

        /*
         * If logged in, loads the admin options for the entry
         */
        $admin = $this->admin_entry_options($this->url0, $id, false);

        /*
         * If an entry exists, load the template and insert the data into it
         */
        if( isset($entries[0]['title']) )
        {
            /*
             * Store the entries in the entry array for templating purposes
             */
            $entries[0]['admin'] = $admin;

            /*
             * Generate the contact form
             */
            $siteName = SITE_NAME;
            $entries[0]['contact'] = <<<DISPLAY

        <form action="/inc/update.inc.php" method="post" id="contact">
            <fieldset id="cf">
                <legend> Contact $siteName </legend>
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

            /*
             * Load the template into a variable
             */
            $template = UTILITIES::loadTemplate($this->url0.'.inc');

            /*
             * Return the entry as formatted by the template
             */
            return UTILITIES::parseTemplate($entries, $template);
        }

        /*
         * If no entry exists, output some default text to avoid a broken layout
         */
        else
        {
            return "\n$admin<h2> No Entry Found </h2>"
                . "\n<p>This page has not been created yet.</p>\n";
        }
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
