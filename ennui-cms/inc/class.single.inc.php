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

		if(isset($entries[0]['title'])) {
			$e = $entries[0];
			return "\n$admin<h2>$e[title] </h2>\n$e[body]\n";
		} else {
			return "\n$admin<h2> No Entry Found </h2>\n<p>Log in to create this entry.</p>\n";
		}
	}

}

?>