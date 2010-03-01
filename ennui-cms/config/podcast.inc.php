<?php

/*
 ******************************************************************************
 * Configuration information for a podcast.
 ******************************************************************************
 */
$_P['PODCAST_TITLE'] = "My Sweet Podcast";
$_P['PODCAST_SUBTITLE'] = "All Things Awesome";
$_P['PODCAST_AUTHOR'] = "John Doe";
$_P['PODCAST_SUMMARY'] = "My Sweet Podcast is about everything awesome.";
$_P['PODCAST_IMAGE'] = "http://example.com/images/podcast.jpg";
$_P['PODCAST_EXPLICIT'] = FALSE;

/*
 * Categories for the podcast are put in an array like so:
 * 
 * EXAMPLE
 * $podcastCategories = array(
 *			array(
 *					"category" => "Category One"
 *				),
 *			array(
 *					"category" => "Category Two",
 *					"subcategory" => "Sub-Category for Category Two"
 *				)
 *		);
 *
 * For a full list of the available categories, see the documentation:
 * http://www.apple.com/itunes/podcasts/specs.html#categories
 */
$podcastCategories = array(
		array(
				"category" => "Comedy"
			),
		array(
				"category" => "Technology",
				"subcategory" => "Gadgets"
			)
	);

?>
