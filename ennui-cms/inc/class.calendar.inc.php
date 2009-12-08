<?php

class Calendar extends Page
{
	public $m = NULL;
	public $y = NULL;

	public function displayPublic()
	{
		$entries = $this->getAllEntries();
		return $this->displayEntry($entries);
	}

	public function displayAdmin($id)
	{
		$form = $this->createForm('write', $id);

		$markup = $form['start'];
		$markup .= $this->createFormInput('title', 'Event Name', $id);
		$markup .= $this->createFormInput('data1', 'Date (MM/DD/YYYY HH:MM AM/PM)', $id);

		$c1 = NEWSLETTER_CATEGORY_1;
		$c2 = NEWSLETTER_CATEGORY_2;
		$c3 = NEWSLETTER_CATEGORY_3;
		$c4 = NEWSLETTER_CATEGORY_4;
		$markup .= "
<label for=\"data2\">Event Category</label>
<select name=\"data2\">
<option value=\"$c1\">$c1</option>
<option value=\"$c2\">$c2</option>
<option value=\"$c3\">$c3</option>
<option value=\"$c4\">$c4</option>
</select>";
		$markup .= $this->createFormInput('body','Description',$id);
		$markup .= $form['end'];

		return $markup;
	}

	private function displayEntry($entries)
	{
		if($_SESSION['loggedIn']==1) {
			$id = (isset($entries[0]['id'])) ? $entries[0]['id'] : NULL;
			$admin = $this->admin_general_options($this->url0, $id);
		} else {
			$admin = NULL;
		}

		$entry = $admin . $this->buildCalendar($entries, $this->url2, $this->url1);

		return $entry;
	}

	private function showDates()
	{
		if(isset($this->url3))
		{
			$output = "<h2>Events on $this->f $this->url3, $this->y</h2>\n<ul id=\"cal-events\">\n";
			$d = (int) $this->url3;
			if(isset($this->dates[$d]))
			{
				ksort($this->dates[$d]);
				$i=0;
				foreach($this->dates[$d] as $timestamp => $date)
				{
					if($_SESSION['loggedIn']==1)
					{
						$admin = $this->admin_simple_options($this->url0, $date['id']);
					}
					else
					{
						$admin = NULL;
					}
					$time = date("g:iA", $timestamp);
					$output .= "\t<li>\n\t\t<h3>$time&mdash;$date[title]</h3>\n\t\t<p>$date[description]</p>$admin</li>\n";
					++$i;
				}
			}
			else
			{
				$output .= "<li>No events scheduled for $this->f $this->url3, $this->y</li>";
			}
			return $output . "</ul>\n";
		}
	}

	private function buildCalendar($data=array(), $m=NULL, $y=NULL)
	{
		// If no dates were passed, use the current date
		$this->m = isset($m) ? $m : date('m');
		$this->y = isset($y) ? $y : date('Y');
	
		// Full month name
		$this->f = date('F', mktime(0, 0, 0, $this->m, 1, $this->y));

		// Rework the dates to group by day
		$this->dates = $this->makeDateArray($data, $this->m);

		// Get previous month and year
		$prev_m = ($this->m-1<1) ? 12 : sprintf("%02d", $this->m-1);
		$prev_y = ($this->m-1<1) ? $this->y-1 : $this->y;
		$prev_f = date('F', mktime(0, 0, 0, $prev_m, 1, $prev_y));

		// Generate a previous month link
		$prev = "\n\t\t<a href=\"/$this->url0/$prev_y/$prev_m#calendar\" class=\"prev\">&#171; $prev_f</a>";

		// Get the next month and year
		$next_m = ($this->m+1>12) ? 1 : sprintf("%02d", $this->m+1);
		$next_y = ($this->m+1>12) ? $this->y+1 : $this->y;
		$next_f = date('F', mktime(0, 0, 0, $next_m, 1, $next_y));

		// Generate a next month link
		$next = "\n\t\t<a href=\"/$this->url0/$next_y/$next_m#calendar\" class=\"next\">$next_f &#187;</a>";

		// Generate a legend
		$legend = "\n\t\t<div><span class=\"" . strtolower(str_replace(' ', '', NEWSLETTER_CATEGORY_1)) 
			. "\"></span> " . NEWSLETTER_CATEGORY_1 . "</div>"
			. "\n\t\t<div><span class=\"" . strtolower(str_replace(' ', '', NEWSLETTER_CATEGORY_2)) 
			. "\"></span> " . NEWSLETTER_CATEGORY_2 . "</div>"
			. "\n\t\t<div><span class=\"" . strtolower(str_replace(' ', '', NEWSLETTER_CATEGORY_3)) 
			. "\"></span> " . NEWSLETTER_CATEGORY_3 . "</div>"
			. "\n\t\t<div><span class=\"" . strtolower(str_replace(' ', '', NEWSLETTER_CATEGORY_4)) 
			. "\"></span> " . NEWSLETTER_CATEGORY_4 . "</div>";

		// Format the links
		$l = "<div id=\"cal-nav\">$prev$legend$next\n\t</div>";

		// Load events for the day if selected
		$e = $this->showDates();
	
		// Find which day of the week the month started on
		$first = date('w', mktime(0, 0, 0, $this->m, 1, $this->y));
	
		// Find out how many days are in the month
		$num = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
	
		// Days of the week
		$w = "<ul id=\"weekdays\">\n\t\t<li>Sunday</li>\n\t\t<li>Monday</li>"
			. "\n\t\t<li>Tuesday</li>\n\t\t<li>Wednesday</li>\n\t\t<li>Thursday</li>"
			. "\n\t\t<li>Friday</li>\n\t\t<li>Saturday</li></ul>";
	
		// Start the calendar
		$output = "\n\t<h2>$this->f $this->y</h2>\n$e\n\t$l\n\t$w\n\t<ul>\n";
	
		// Create empty containers for leading days
		for($i=0; $i<$first; ++$i)
		{
			$output .= "\t\t<li class=\"filler\"> </li>\n";
		}
	
		// Loop through and output each day
		for($c=1; $c<=$num; ++$c)
		{
			// Start the week over at seven days
			if($i%7==0)
			{
				$output .= "\t</ul>\n\t<ul>\n";
			}

			if(isset($this->dates[$c]))
			{
				// Make sure the times are lined up
				ksort($this->dates[$c]);
	
				// Output markup for the calendar
				$d = "\t\t\t<ul class=\"dates\">\n";
				$events = 0;
				foreach($this->dates[$c] as $timestamp => $date)
				{
					if($events<1)
					{
						$d .= "\t\t\t\t<li class=\"$date[category]\">$date[title]</li>\n";
					}
					else
					{
						$overflow = TRUE;
					}
					++$events;
				}
				if(isset($overflow) && $overflow===TRUE && $events>1)
				{
					$x = $events-1;
					$ess = $x==1 ? '' : 's';
					$d .= "\t\t\t\t<li>$x more event$ess...</li>\n";
				}
				$d .= "\t\t\t</ul>\n";
			}
			else
			{
				$d = NULL;
			}
	
			// If the today matches the current index, highlight it
			$today = date('m/d/Y')=="$this->m/$c/$this->y" ? ' class="today"' : NULL;

			// Format the 
			$d_link = sprintf("%02d", $c);

			++$i;
			$output .= "\t\t<li$today>\n\t\t\t<a href=\"/$this->url0/$this->y/$this->m/$d_link\"><span class=\"date\">$c</span></a>\n$d\t\t</li>\n";
		}
	
		// Finish out the month
		while($i%7!=0)
		{
			++$i;
			$output .= "\t\t<li class=\"filler\"> </li>\n";
		}
	
		// Return the list
		return $output . "\t</ul>\n";
	}
	
	private function makeDateArray($data)
	{
		$dates = array();
		sort($data);
		foreach($data as $d)
		{
			$time = strtotime($d['data1']);
			if($this->m.$this->y==date('mY', $time))
			{
				$day = date('j', $time);
				$dates[$day][$time] = array(
					"id" => $d['id'],
					"title" => $d['title'],
					"description" => $d['body'],
					"category" => strtolower(str_replace(' ', '', $d['data2']))
				);
			}
		}
		return $dates;
	}
}

?>