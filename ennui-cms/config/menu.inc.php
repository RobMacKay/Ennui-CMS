<?php

/*
 * The default page for the site to load (i.e. the "home" page)
 */
$_CONSTANTS['DEFAULT_PAGE'] = 'home';

/*
 * This array builds the menu as an unordered list
 * 
 * ----------------------------------------------------------------------------
 * 
 * The array index is the URL. Options:
 * 	- display (required): The text to be displayed in the menu/title tag
 *  - type (required): The class type the page uses to display information
 *  - sub (optional): If a sub-menu exists, this contains an array of sub items
 *  - hide (optional): If TRUE, hides the item from the menu display
 *  - url (optional): If set, overrides the array key as URL
 *  - class (optional): If set, adds a class attribute to the 
 *  
 * Example:
 * 	$menuPages = array(
 * 		'home' => array(
 * 			'display' => 'Home Page',
 * 			'type' => 'single',
 * 			'class' => 'default',
 * 			'url' => ''
 * 		),
 * 		'services' => array(
 * 			'display' => 'Our Services',
 * 			'type' => 'multi',
 * 			'inline' => 'id="services-link"',
 * 			'sub' => array(
 * 				'delivery' => array(
 * 					'display' => 'Home Delivery',
 * 					'type' => 'single'
 * 				),
 * 				'online' => array(
 * 					'display' => 'Shop Online',
 * 					'type' => 'single'
 * 				)
 * 			)
 * 		),
 * 		'legal' => array(
 * 			'display' => 'Legal Notices',
 * 			'type' => 'single',
 * 			'hide' => TRUE
 * 		)
 * 	);
 * 
 * Above outputs:
 * 		<ul id="menu">
 * 			<li class="default"><a href="/">Home Page</a></li>
 * 			<li class="" id="services-link"><a href="/services">Our Services</a>
 * 				<ul class="submenu services">
 * 					<li class=""><a href="/delivery">Home Delivery</a></li>
 * 					<li class=""><a href="/online">Shop Online</a></li>
 * 				</ul><!-- end menu -->
 * 			</li>
 * 		</ul><!-- end menu -->
 */
$menuPages = array(
	'home' => array(
		'display' => 'Home',
		'type' => 'single'
	),
	'services' => array(
		'display' => 'Services',
		'type' => 'multi'
	),
	'photos' => array(
		'display' => 'Photos',
		'type' => 'multiphoto'
	),
	'contact' => array(
		'display' => 'Contact Us',
		'type' => 'contact'
	),
	'minibio' => array(
		'display' => 'Mini Bio',
		'type' => 'single',
		'hide' => TRUE
	)
);

?>