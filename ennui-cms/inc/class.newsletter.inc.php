<?php

class Newsletter extends Page
{

	public function displayPublic()
	{
		if(isset($this->url1) && $this->url1!='more')
		{
			if ( $this->url1 == 'unsubscribe' ) {
				/*
				 * This allows the user to unsubscribe from a comment stream
				 */
				return $this->unsubscribe();
			}

			else
			{
				$entries = $this->getEntryByUrl($this->url1);
				return $this->displayFull($entries);
			}
		}
		else
		{
			$limit = MAX_ENTRIES_PER_PAGE; // Number of entries per page
			if(isset($this->url1) && $this->url1=='more')
			{
				$offset = (isset($this->url2)) ? $limit*($this->url2-1) : 0;
			}
			else
			{
				$offset = 0;
			}
			$entries = $this->getAllEntries($limit, $offset);
			return $this->displayPreview($entries);
		}
	}

	public function displayAdmin($id)
	{
		$form = $this->createForm('write', $id);

		$markup = $form['start'];
		$markup .= $this->createFormInput('title', 'Subject', $id);
		$markup .= $this->createFormInput('body','Body',$id);
		$markup .= "<input type=\"submit\" value=\"Preview\" name=\"submit\" class=\"nl_preview\" onclick=\"return false;\" />";
		$markup .= $form['end'];

		return $markup;
	}

	private function displayPreview($entries)
	{
		if($_SESSION['loggedIn']==1) {
			$id = (isset($entries[0]['id'])) ? $entries[0]['id'] : NULL;
			$admin = <<<ADMIN_OPTIONS

<!--// BEGIN ADMIN OPTIONS //-->
<div class="admintopopts">
	<p>
		You are logged in as $_SESSION[admin_u].<br />
		[ <a href="javascript:showedit('$this->url0','showoptions','');">create a new entry</a> | 
		<a href="javascript:showedit('$this->url0','nl_viewsubs','');">view subscribers</a> | 
		<a href="/_engine/Update.inc.php?action=logout" 
			onclick="return confirm('Are you sure you want to log out?\\n\\nClick OK to continue.');">logout</a> ]
	</p>
</div>
<!--// END ADMIN OPTIONS //-->

ADMIN_OPTIONS;
		} else {
			$admin = NULL;
		}

		$entry = $admin;

		if(isset($entries[0]['title'])) {
			foreach($entries as $e) {
				// Prepare the data for display
				$e['title'] = htmlentities(strip_tags($e['title']));
				$e['url'] = '/'.$this->url0.'/'.urlencode($e['title']).'/';
				$e['date'] = date('m/d/Y', $e['created']);
				
				// Entry options for the admin, if logged in
				$admin_entry = $this->admin_simple_options($this->url0, $e['id']);

				$entry .= "
					<h2> <a href=\"$e[url]\">$e[date] - $e[title]</a> </h2>$admin_entry\n\n";
			}
		} else {
			$entry .= "
					<h2> No Entry Found </h2>
					<p>
						Log in to create this entry.
					</p>";
		}

		return $entry;
	}

	private function displayFull($entries)
	{
		if($_SESSION['loggedIn']==1) {
			$id = (isset($entries[0]['id'])) ? $entries[0]['id'] : NULL;
			$admin = $this->admin_entry_options($this->url0, $id, false);
		} else {
			$admin = NULL;
		}

		$entry = $admin;
		foreach($entries as $e)
		{
			$entry .= "
					<p><a href=\"/$this->url0/\">&#171; Back to All Entries</a></p>
					<h2> $e[title] </h2>
					<p>$e[body]</p>
					<p><a href=\"/$this->url0/\">&#171; Back to All Entries</a></p>\n\n";
		}

		return $entry;
	}

	public function displaySubs()
	{
		$subs = $this->loadSubs();
		$disp = "<table id=\"nl_subs\">
	<tbody>
		<tr>
			<th scope=\"col\">Name</th>
			<th scope=\"col\">Email Address</th>
		</tr>";
		foreach($subs as $sub)
		{
			$disp .= "\t\t<tr>
			<td>$sub[name]</td>
			<td>$sub[email]</td>
		</tr>\n";
		}
		return $disp . "\t</tbody>\n</table>\n<a href=\"/$this->url0\">Back to Main View</a>\n";
	}

	private function loadSubs()
	{
		$sql = "SELECT name, email, cat1, cat2, cat3, cat4
				FROM `".DB_NAME."`.`".DB_PREFIX."nlMgr`
				GROUP BY email
				ORDER BY email_id";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->execute();
		$stmt->bind_result($name, $email, $cat1, $cat2, $cat3, $cat4);
		$subs = array();
		while($stmt->fetch())
		{
			$subs[] = array(
				'name' => $name,
				'email' => $email,
				'cat1' => $cat1,
				'cat2' => $cat2,
				'cat3' => $cat3,
				'cat4' => $cat4
			);
		}
		$stmt->close();
		return $subs;
	}

	private function makeUrlsAbsolute($body)
	{
		// Make sure all links are absolute in the HTML message
		$pattern = "/(src|href)=\"(?!http:\/\/)([\/\w-\.]+)\"/i";
		$replacement = '$1="' . SITE_URL . '$2"';
		return preg_replace($pattern, $replacement, stripslashes($body));
	}

	private function sendNewsletter($e)
	{
		// Admin information for sending
		$name = SITE_CONTACT_NAME;
		$email = SITE_CONTACT_EMAIL;
		$sig_name = SITE_NAME;
		$sig_email = SITE_CONTACT_EMAIL;
		$sig_phone = PHONE_NUMBER;
		$sig_address = MAILING_ADDRESS;
		$siteUrl = SITE_URL;

		// Message information
		$subject = stripslashes($e['title']);
		$url = '/'.$this->url0.'/'.urlencode($e['title']).'/';
		$msg_body_plain = strip_tags($e['body']);

		// Build HTML message
		$html_message = $this->newsletterHTML($e['body'], $subject, $url);

		// Generate a boundary string
		$mime_boundary = '_x'.sha1(time()).'x';

		// Build the message
		$headers = <<<HEADERS
MIME-Version: 1.0
From: $name <$email>
Content-Type: multipart/alternative; boundary=$mime_boundary
HEADERS;

		$emails = $this->loadSubscribers();

		$err = 0;
		foreach($emails as $email)
		{
			if(Utilities::isValidEmail($email))
			{
				$message = <<<MESSAGE

--==PHP-alt$mime_boundary
Content-Type: text/plain; charset=ISO-8859-1

$msg_body_plain

_
$sig_name
$sig_email

$sig_address
$sig_phone

View this message online by visiting $siteUrl$url
To stop receiving these messages, visit $siteUrl/$this->url0/unsubscribe/$email

--$mime_boundary
Content-Type: text/html; charset=ISO-8859-1

$html_message

--$mime_boundary--
MESSAGE;

				if(!mail($email, $subject, $message, $headers))
				{
					++$err;
				}
			}
		}

		return $err > 0 ? FALSE : TRUE;
	}

	public function newsletterHTML($msg_body, $subject, $url="#")
	{
		// Admin information for sending
		$name = SITE_CONTACT_NAME;
		$email = SITE_CONTACT_EMAIL;
		$sig_name = SITE_NAME;
		$sig_email = SITE_CONTACT_EMAIL;
		$sig_phone = PHONE_NUMBER;
		$sig_address = MAILING_ADDRESS;
		$siteUrl = SITE_URL;

		$msg_body = $this->makeUrlsAbsolute($msg_body);

		$markup = <<<HTML_EMAIL
<html><body marginwidth="0" marginheight="0" bgcolor="#ffffff" offset="0" topmargin="0" leftmargin="0">
<table width="100%" cellspacing="0" cellpadding="10" bgcolor="#ffffff">
<tbody><tr><td valign="top" align="center"><table width="600" cellspacing="0" cellpadding="0"><tbody><tr>

<!--// View this in your browser link //-->
<td align="center" style="border-top: 0px solid rgb(0, 0, 0); border-bottom: 1px solid rgb(255, 255, 255); background-color:#ffffff; text-align: center;">
<span style="font-size: 10px; color:#000000; line-height: 200%; font-family: helvetica, sans-serif; text-decoration: none;">
Email not displaying correctly?
<a style="font-size: 10px; color:#1123B5; line-height: 200%; font-family: verdana; text-decoration: none;" href="$siteUrl$url">View this message online.</a></span></td></tr>
<!--// End "view this in your browser" link //-->

<!--// Header Image //-->
<tr><td valign="middle" align="left" style="border-bottom: 1px solid rgb(255, 255, 255); background-color:#ffffff;"><center>

<a href="$siteUrl">
<img border="0" align="center" alt="$sig_name Newsletter" src="$siteUrl/images/nl_header.jpg"/>
</a>

</center></td></tr></tbody></table>
<!--// End Header Image //-->

<!--// Left (Main) Column //-->
<table width="600" cellspacing="0" cellpadding="20" bgcolor="#ffffff"><tbody><tr>
<td width="600" valign="top" bgcolor="#ffffff" style="font-size: 12px; color: rgb(0, 0, 0); line-height: 150%; font-family: helvetica, sans-serif;">
<p>

<!--// Headline 1 //-->
<span style="font-size: 20px; font-weight: bold; color:#000000; font-family: helvetica; line-height: 110%;">$subject</span>

<br/>

<!--// Content 1 //-->
$msg_body

</p></td>
<!--// End Left (Main) Column //-->

</tr>

<!--// Footer Information //-->
<tr><td valign="top" width="600" style="border-top: 1px solid rgb(0, 0, 0); background-color:#B2B2BA;">
<span style="font-size: 10px; color:#000000; line-height: 100%; font-family: verdana;">
$sig_name<br />
$sig_email<br />
$sig_phone
<br/>
<br/>
Copyright &copy; $sig_name. All rights reserved.
<br/>
<br/>
<a href="$siteUrl$url">View this message online.</a><br/>
If you wish to stop receiving these messages, 
<a href="$siteUrl/$this->url0/unsubscribe/$email">click here</a>.
</span></td></tr></tbody></table></td></tr></tbody></table></body></html>
HTML_EMAIL;

		return $markup;
	}

	static function displaySignup($cur_page)
	{
		if(isset($_COOKIE['nl_subscribed']))
		{
			return "
<div id=\"nl_form\">
	<h3 class=\"cookie\"> You're Already Subscribed! </h3>
	<p class=\"cookie\">
		Welcome back, $_COOKIE[nl_name]! 
		Our records show that you have already subscribed to our newsletter 
		using the email address $_COOKIE[nl_email].
		<a href=\"/inc/engine.ennui.update.inc.php?page=$cur_page&action=nl_cookie\">Not you?</a>
	</p>
</div>";
		}


		else
		{
			$nl_headline = NEWSLETTER_HEADLINE;
			$nl_teaser = NEWSLETTER_TEASER;
			$nl_submit = NEWSLETTER_SUBMIT;
			$page = self::determineNewsletterType();

			return "
<form action=\"/inc/engine.ennui.update.inc.php\" method=\"post\" id=\"nl_form\">
	<h1> $nl_headline </h1>
	<fieldset id=\"nl_info\">
		<p>$nl_teaser</p>
		<label for=\"nl_n\">Name</label>
		<input type=\"text\" name=\"nl_n\" id=\"nl_n\" maxlength=\"75\" />
		<label for=\"nl_n\">Email</label>
		<input type=\"text\" name=\"nl_e\" id=\"nl_e\" maxlength=\"125\" />
		<input type=\"submit\" name=\"nl_s\" id=\"nl_s\" value=\"$nl_submit\" />
		<input type=\"hidden\" name=\"page\" class=\"nl_h\" value=\"$page\" />
		<input type=\"hidden\" name=\"action\" class=\"nl_h\" value=\"nl_subscribe\" />
	</fieldset>
</form>";
		}
	}

	public function write($post, $files)
	{
		/*
		 * Check all the variables and make sure they're escaped for storage
		 */
		$id = isset($post['id']) ? $post['id'] : '';
		$page = $post['page'];
		$title = isset($post['title']) ? $post['title'] : NULL;
		$subhead = isset($post['subhead']) ? $post['subhead'] : NULL;
		$body = isset($post['body']) ? $post['body'] : NULL;
		$imgcap = isset($post['imgcap']) ? $post['imgcap'] : NULL;
		$data1 = isset($post['data1']) ? $post['data1'] : NULL;
		$data2 = isset($post['data2']) ? $post['data2'] : NULL;
		$data3 = isset($post['data3']) ? $post['data3'] : NULL;
		$data4 = isset($post['data4']) ? $post['data4'] : NULL;
		$data5 = isset($post['data5']) ? $post['data5'] : NULL;
		$data6 = isset($post['data6']) ? $post['data6'] : NULL;
		$data7 = isset($post['data7']) ? $post['data7'] : NULL;

		/*
		 * Processes the image and returns the path, or sets the variable to
		 * NULL if no image was uploaded
		 */
		$img = (isset($files['img'])) ? $this->checkIMG($files['img']) : NULL;
		if($img===false) {
			$img = (isset($post['stored_img'])) ? $post['stored_img'] : NULL;
		}

		/*
		 * PDF uploads go through the data8 field. If the $_FILES superglobal isn't
		 * set, handle the input as a string. Otherwise, process as a PDF
		 */
		if(!is_array($files['data8'])) {
			$data8 = $post['data8'] ? $post['data8'] : NULL;
		} else if($files['data8']['size']>0) {
			$data8check = $this->uploadPDF($files['data8'],$title);
			$data8 = ($data8check===false) ? NULL : $data8;
		}

		/*
		 * Store the author's name and a timestamp
		 */
		$author = $_SESSION['uname'];
		$created = time();

		/*
		 * If the ID was passed, set up the query to update the entry
		 */
		if ( $id ) {
			$sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."entryMgr`
					SET title=?, subhead=?, body=?, img=?, imgcap=?
						, data1=?, data2=?, data3=?, data4=?, data5=?, data6=?, data7=?
						, data8=? WHERE id=?
					LIMIT 1";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("sssssssssssssi",$title, $subhead, $body, $img, 
					$imgcap, $data1, $data2, $data3, $data4, $data5, $data6, 
					$data7, $data8, $id);
		}

		/*
		 * Otherwise, save a new entry
		 */
		else {
			if(!$this->sendNewsletter($post))
			{
				throw new Exception('Sending the newsletter failed.');
			}
			$sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."entryMgr`
						(page, title, subhead, body, img, imgcap,
						data1, data2, data3, data4, data5, data6, data7, data8,
						author, created) 
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("ssssssssssssssss", $page, $title, $subhead, $body, 
					$img, $imgcap, $data1, $data2, $data3, $data4, $data5, $data6,
					$data7, $data8, $author, $created);
		}
		$success = $stmt->execute();
		$stmt->close();

		return $success;
	}

	public function saveSubscription($p)
	{
		$pattern = '/^[\w\s]+$/i';
		$name = preg_match($pattern, $p['nl_n']) == 1 ? $p['nl_n'] : NULL;

		$email = Utilities::isValidEmail($p['nl_e']) ? $p['nl_e'] : FALSE;

		// Check categories
		$c1 = isset($p['nl_cat1']) ? 1 : 0;
		$c2 = isset($p['nl_cat2']) ? 1 : 0;
		$c3 = isset($p['nl_cat3']) ? 1 : 0;
		$c4 = isset($p['nl_cat4']) ? 1 : 0;

		if(!$email)
		{
			return FALSE;
		}
		else
		{
			$sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."nlMgr`
						(name, email, cat1, cat2, cat3, cat4)
					VALUES (?, ?, ?, ?, ?, ?)";
			if($stmt = $this->mysqli->prepare($sql))
			{
				$stmt->bind_param('ssiiii', $name, $email, $c1, $c2, $c3, $c4);
				$stmt->execute();
				$stmt->close();
			}
			else
			{
				return FALSE;
			}
		}

		$one_month = time() + 60*60*30;
		setcookie('nl_subscribed', '1', $one_month, '/');
		setcookie('nl_name', $name, $one_month, '/');
		setcookie('nl_email', $email, $one_month, '/');

		return TRUE;
	}

	public static function removeCookies()
	{
		$the_past = time()-30;
		setcookie('nl_subscribed', '', $the_past, '/');
		setcookie('nl_name', '', $the_past, '/');
		setcookie('nl_email', '', $the_past, '/');
	}

	private function loadSubscribers()
	{
		$sql = "SELECT email
				FROM `".DB_NAME."`.`".DB_PREFIX."nlMgr`
				GROUP BY email";
		$emails = NULL;
		if($stmt = $this->mysqli->prepare($sql))
		{
			$stmt->execute();
			$stmt->bind_result($email);
			while($stmt->fetch())
			{
				$emails[] = $email;
			}
			$stmt->close();
		}
		return $emails;
	}

	private function unsubscribe() {
		if ( $this->url1 == 'unsubscribe' ) {
			$email = $this->url2;
			$sql = "DELETE FROM `".DB_NAME."`.`".DB_PREFIX."nlMgr`
					WHERE email=?
					LIMIT 1";
			if($stmt = $this->mysqli->prepare($sql))
			{
				$stmt->bind_param("s", $email);
				$stmt->execute();
				$stmt->close();

				$content = <<<SUCCESS_MSG

				<h1> You Have Unsubscribed </h1>
				<p>
					You will no longer received our newsletter.
				</p>
				<p>
					If you have any questions or if you
					continue to get notifications, contact
					<a href="mailto:answers@ennuidesign.com">answers@ennuidesign.com</a>
					for further assistance.
				</p>
SUCCESS_MSG;
			} else {
				$content = <<<ERROR_MSG

				<h1> Uh-Oh </h1>
				<p>
					Somewhere along the lines, something went wrong,
					and we were unable to remove you from the mailing list.
				</p>
				<p>
					Please try again, or contact
					<a href="mailto:answers@ennuidesign.com">answers@ennuidesign.com</a>
					for further assistance.
				</p>
ERROR_MSG;
			}
		} else {
			header('Location: /');
			exit;
		}

		return $content;
	}

	static function determineNewsletterType()
	{
		include_once 'vars/config.inc.php';
		foreach($GLOBALS['menuPages'] as $page => $attr)
		{
			if($attr['type']=='newsletter')
			{
				return $page;
			}
		}
	}
}

?>