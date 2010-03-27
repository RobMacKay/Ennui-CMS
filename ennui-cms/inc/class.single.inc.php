<?php

class Single extends Page
{

	public function displayPublic()
	{
		$entries = $this->getAllEntries(1);
		return $this->displayEntry($entries);
	}

	public function displayAdmin($id)
	{
		$form = $this->createForm('write', $id);

		$markup = $form['start'];
		$markup .= $this->createFormInput('title', 'Page Title', $id);
		$markup .= $this->createFormInput('body', 'Body Text', $id);
		$markup .= $form['end'];

		return $markup;
	}

	private function displayEntry($entries)
	{
		$id = isset($entries[0]['id']) ? $entries[0]['id'] : NULL;
		$admin = $this->admin_entry_options($this->url0, $id, false);

		if( isset($entries[0]['title']) )
		{
			/*
			 * Load the template into a variable
			 */
			$template = file_get_contents(CMS_PATH.'template/'.$this->url0.'.inc');

			/*
			 * Return the entry as formatted by the template
			 */
			return UTILITIES::parseTemplate($entries, $template);
		}
		else
		{
			return "\n$admin<h2> No Entry Found </h2>"
				. "\n<p>This page has not been created yet.</p>\n";
		}
	}

}

?>