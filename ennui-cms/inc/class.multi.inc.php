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
		$id = isset($entries[0]['id']) ? $entries[0]['id'] : NULL;
		$admin = $this->admin_general_options($this->url0, $id, false);

		$entry = $admin;

		if(isset($entries[0]['title'])) {
			$entry_array = array();
			foreach($entries as $e) {
				// Entry options for the admin, if logged in
				$e['admin'] = $this->admin_simple_options($this->url0, $e['id']);

				// Rename the URL for use in the template
				$e['url'] = empty($e['data6']) ? urlencode($e['title']) : $e['data6'];

				$e['image'] = isset($e['img']) ? Utilities::formatImageSimple($e) : NULL;

				$e['preview'] = UTILITIES::textPreview($e['body'], 45);

				$entry_array[] = $e;
			}
			if ( file_exists(CMS_PATH.'template/'.$this->url0.'-preview.inc') )
			{
				$template = file_get_contents(CMS_PATH.'template/'.$this->url0.'-preview.inc');
			}
			else
			{
				$template = file_get_contents(CMS_PATH.'template/'.DEFAULT_TEMPLATE);
			}
			$entry .= UTILITIES::parseTemplate($entry_array, $template);
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
		$entry_array = array();
		foreach($entries as $e) {
			// Entry options for the admin, if logged in
			$e['admin'] = $this->admin_simple_options($this->url0, $e['id']);

			$e['image'] = isset($e['img']) ? Utilities::formatImageSimple($e) : NULL;

			$entry_array[] = $e;
		}

		if ( file_exists(CMS_PATH.'template/'.$this->url0.'-full.inc') )
		{
			$template = file_get_contents(CMS_PATH.'template/'.$this->url0.'-full.inc');
		}
		else
		{
			$template = file_get_contents(CMS_PATH.'template/'.DEFAULT_TEMPLATE);
		}
		$entry .= UTILITIES::parseTemplate($entry_array, $template);

		return $entry;
	}

}

?>