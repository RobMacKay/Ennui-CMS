<?php

/*
 ******************************************************************************
 * Basic site information
 ******************************************************************************
 */
$_CONSTANTS['SITE_URL'] = "http://localhost";

/*
 * If $_SERVER['DOCUMENT_ROOT'] does not contain the full path to the web root,
 * finish the path here. Leave blank otherwise.
 *
 * NOTE: This is the path to the public folder. In a default installation, the
 * path points to /public
 *
 * WARNING: DO NOT include a trailing slash in this path
 *	CORRECT:	"/path/to/public"
 *	INCORRECT:	"/path/to/public/"
 */
$_CONSTANTS['SERVER_PATH'] = "/public";

/*
 * Site name (i.e. "Debbie's Donuts" or "John Doe, DDS")
 */
$_CONSTANTS['SITE_NAME'] = "Example Site";

/*
 * Description of the site. This is used in the "description" meta tag, which 
 * is used by some search engines to describe the site.
 */
$_CONSTANTS['SITE_DESCRIPTION'] = "Example installation of the Ennui CMS.";

/*
 * Contact phone number for the site
 */
$_CONSTANTS['PHONE_NUMBER'] = "(800) 555-1234";

/*
 * Address for the site.
 */
$_CONSTANTS['MAILING_ADDRESS'] = '123 Memory Ln, Missoula, MT 59801';
$_CONSTANTS['STREET_ADDRESS'] = '123 Memory Ln';
$_CONSTANTS['CITY_STATE_ZIP'] = 'Missoula, MT 59801';

/*
 * Link to the site RSS feed. If using a third-party service for feeds (such as
 * FeedBurner), be sure to include the http://
 */
$_CONSTANTS['SITE_RSS'] = '/feeds/';

/*
 * Administrative contact for the site. This is the name that will be used in 
 * the confirmation message from the contact page.
 */
$_CONSTANTS['SITE_CONTACT_NAME'] = 'John Doe';

/*
 * Administrative contact email. This is the address to which all site 
 * notifications will be sent.
 */
$_CONSTANTS['SITE_CONTACT_EMAIL'] = 'john.doe@example.com';

/*
 * Information to be displayed in the site's title tag. The name of the page 
 * will be displayed first.
 */
$_CONSTANTS['SITE_TITLE'] = "Example Site &raquo; Ennui CMS";

/*
 * A separator to use between site title data
 */
$_CONSTANTS['SITE_TITLE_SEPARATOR'] = "&raquo;";

/*
 * This is the confirmation email that will be sent to users that submit a 
 * message through the site's contact form.
 */
$_CONSTANTS['SITE_CONFIRMATION_MESSAGE'] = ""
	. "Thanks for contacting me! I try to respond to all messages\n"
	. "within 24 hours. However, if you're writing on a weekend, you may\n"
	. "not receive a response until Monday.\n\n"
	. "Thanks!\n"
	. $_CONSTANTS['SITE_CONTACT_NAME'];

/*
 * The year the site was originally created.
 */
$_CONSTANTS['SITE_CREATED_YEAR'] = '2009';

/*
 * The site's Google Analytics username for stats tracking
 * 
 * EXAMPLE: UA-1234567-89
 */
$_CONSTANTS['GOOGLE_ANALYTICS_USER'] = '';

/*
 ******************************************************************************
 * Newsletter Information
 ******************************************************************************
 */
$_CONSTANTS['NEWSLETTER_HEADLINE'] = "";
$_CONSTANTS['NEWSLETTER_TEASER'] = "";
$_CONSTANTS['NEWSLETTER_SUBMIT'] = "";

/*
 * GetResponse-specific options. Leave blank if you don't use GetResponse.
 */
$_CONSTANTS['GETRESPONSE_CAMPAIGN_NAME'] = "";
$_CONSTANTS['GETRESPONSE_TEASER'] = "Want more content like this? Get free updates!";
$_CONSTANTS['GETRESPONSE_SUBMIT'] = "Send Me Updates";

/*
 ******************************************************************************
 * Image handling info
 ******************************************************************************
 */
$_CONSTANTS['IMG_SAVE_DIR'] = 'images/userPics/';
$_CONSTANTS['GAL_SAVE_DIR'] = 'images/userPics/gallery/';
$_CONSTANTS['IMG_MAX_WIDTH'] = 480;
$_CONSTANTS['IMG_MAX_HEIGHT'] = 375;
$_CONSTANTS['IMG_THUMB_SIZE'] = 140;

/*
 ******************************************************************************
 * Multiple page configuration
 ******************************************************************************
 */
$_CONSTANTS['MAX_ENTRIES_PER_PAGE'] = 20;

/*
 ******************************************************************************
 * Blog configuration
 ******************************************************************************
 */
$_CONSTANTS['BLOG_PREVIEW_NUM'] = 4;

/* 
 * Dimensions for embedded videos
 */
$_CONSTANTS['PAGE_OBJ_WIDTH'] = 480;
$_CONSTANTS['PAGE_OBJ_HEIGHT'] = 270;

/*
 * Gravatar default image. See http://en.gravatar.com for details.
 * 
 * NOTE: If left blank, the gravatar.com default is used
 */
$_CONSTANTS['GRAVATAR_DEFAULT_IMG_URL'] = "";

/*
 * Define the square size of gravatars (in pixels)
 * 
 * NOTE: Gravatar default is 80
 */
$_CONSTANTS['GRAVATAR_SIZE'] = 80;

/*
 * Define the rating of gravatars
 * 
 * OPTIONS: G, PG, R, X
 */
$_CONSTANTS['GRAVATAR_RATING'] = "PG";

/*
 * Define a border color for gravatar images
 */
$_CONSTANTS['GRAVATAR_BORDER_COLOR'] = "222222";

/*
 * Tags to allow in user-posted comments
 */
$_CONSTANTS['COMMENT_WHITELIST'] = "<strong><em>";

?>