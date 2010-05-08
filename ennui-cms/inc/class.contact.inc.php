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
         * Store variables for the form
         */
        $siteName = SITE_NAME;
        $formProcessing = FORM_ACTION;

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
            $entries[0]['site-name'] = $siteName;
            $entries[0]['form-processing'] = $formProcessing;

            $template_file = $this->url0.'.inc';
        }

        /*
         * If no entry exists, output some default text to avoid a broken layout
         */
        else
        {
            $entries[0] = array(
                    'admin' => $admin,
                    'page' => $this->url0,
                    'title' => "No Entry Found",
                    'body' => "<p>That entry doesn't appear to exist.</p>",
                    'site-name' => $siteName,
                    'form-processing' => $formProcessing
                );

            $template_file = $this->url0.'.inc';
        }

        /*
         * Load the template into a variable
         */
        $template = UTILITIES::loadTemplate($template_file);

        /*
         * Return the entry as formatted by the template
         */
        return UTILITIES::parseTemplate($entries, $template);
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
