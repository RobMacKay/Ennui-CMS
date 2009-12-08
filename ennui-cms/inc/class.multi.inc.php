<?php

class Multi extends Page
{

	public function displayPublic()
	{
		if(isset($this->url1) && $this->url1!='more')
		{
			$entries = $this->getEntryByUrl($this->url1);
			return $this->displayFull($entries);
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
		$markup .= $this->createFormInput('title', 'Headline', $id);
		$markup .= $this->createFormInput('body','Description',$id);
		$markup .= $form['end'];

		return $markup;
	}

	private function displayPreview($entries)
	{
		if($_SESSION['loggedIn']==1) {
			$id = (isset($entries[0]['id'])) ? $entries[0]['id'] : NULL;
			$admin = $this->admin_general_options($this->url0, $id, false);
		} else {
			$admin = NULL;
		}

		$entry = $admin;

		if(isset($entries[0]['title'])) {
			foreach($entries as $e) {
				// Entry options for the admin, if logged in
				if($_SESSION['loggedIn']==1)
				{
					$admin_entry = $this->admin_simple_options($this->url0, $e['id']);
				}
				else
				{
					$admin_entry = NULL;
				}
				if(isset($e['img']))
				{
					$e['image'] = Utilities::formatImageSimple($e);
				}
				else
				{
					$e['image'] = NULL;
				}

				$entry .= "
					<h2> $e[title] </h2>
					$e[image]$e[body]$admin_entry\n\n";
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
			$e['image'] = Utilities::formatImage($e);
			$entry .= "
					<p><a href=\"/$this->url0/\">&#171; Back to All Entries</a></p>
					$e[image]
					<p class=\"prop_cat\">Location</p>
					<div class=\"prop_text\">
						<p>$e[data1]</p>
					</div>
					<p class=\"prop_cat\">Description</p>
					<div class=\"prop_text\">
						<p>$e[body]</p>
					</div>
					<p class=\"prop_cat\">Lot Size</p>
					<div class=\"prop_text\">
						<p>$e[data2]</p>
					</div>
					<p class=\"prop_cat\">Square Feet</p>
					<div class=\"prop_text\">
						<p>$e[data3]</p>
					</div>
					<p class=\"prop_cat\">Bedrooms/Bathrooms</p>
					<div class=\"prop_text\">
						<p>$e[data4]</p>
					</div>
					<p class=\"prop_cat\">MLS #</p>
					<div class=\"prop_text\">
						<p>$e[data5]</p>
					</div>
					<p class=\"prop_cat\">Price</p>
					<div class=\"prop_text\">
						<p>$e[data6]</p>
					</div>\n\n";
		}

		return $entry;
	}

}

?>