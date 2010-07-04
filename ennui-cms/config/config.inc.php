<?php

/*
 ******************************************************************************
 * Basic site information
 ******************************************************************************
 */
$_CONSTANTS['SITE_URL'] = "http://ennui-cms.ennuidesign.com/";

/*
 * Site name (i.e. "Debbie's Donuts" or "John Doe, DDS")
 */
$_CONSTANTS['SITE_NAME'] = "ECMS Demo";

/*
 * Description of the site. This is used in the "description" meta tag, which 
 * is used by some search engines to describe the site.
 */
$_CONSTANTS['SITE_DESCRIPTION'] = "Beta testing for the new ECMS.";

/*
 * Contact phone number for the site
 */
$_CONSTANTS['PHONE_NUMBER'] = "(800) 555-1234";

/*
 * Address for the site.
 */
$_CONSTANTS['STREET_ADDRESS'] = '123 Memory Ln';
$_CONSTANTS['CITY_STATE_ZIP'] = 'Missoula, MT 59801';

/*
 * Link to the site RSS feed. If using a third-party service for feeds (such as
 * FeedBurner), be sure to include the http://
 */
$_CONSTANTS['SITE_RSS'] = '/assets/feeds/';

/*
 * Administrative contact for the site. This is the name that will be used in 
 * the confirmation message from the contact page.
 */
$_CONSTANTS['SITE_CONTACT_NAME'] = 'Ennui Design';

/*
 * Administrative contact email. This is the address to which all site 
 * notifications will be sent.
 */
$_CONSTANTS['SITE_CONTACT_EMAIL'] = 'answers@ennuidesign.com';

/*
 * Information to be displayed in the site's title tag. The name of the page 
 * will be displayed first.
 */
$_CONSTANTS['SITE_TITLE'] = "ECMS";

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
$_CONSTANTS['SITE_CREATED_YEAR'] = '2010';

/*
 * The site's Google Analytics username for stats tracking. Leave empty if the
 * site doesn't use Google Analytics
 * 
 * EXAMPLE: UA-1234567-89
 */
$_CONSTANTS['GOOGLE_ANALYTICS_USER'] = '';

/*
 ******************************************************************************
 * Image handling info
 ******************************************************************************
 */
$_CONSTANTS['FORM_ACTION'] = '/assets/inc/update.inc.php';

/*
 ******************************************************************************
 * Image handling info
 ******************************************************************************
 */
$_CONSTANTS['IMG_SAVE_DIR'] = 'assets/images/userPics/';
$_CONSTANTS['GAL_SAVE_DIR'] = 'assets/images/userPics/gallery/';
$_CONSTANTS['IMG_MAX_WIDTH'] = 1280;
$_CONSTANTS['IMG_MAX_HEIGHT'] = 1024;
$_CONSTANTS['IMG_PREV_WIDTH'] = 175;
$_CONSTANTS['IMG_PREV_HEIGHT'] = 255;
$_CONSTANTS['IMG_THUMB_SIZE'] = 75;
$_CONSTANTS['IMG_QUALITY'] = 8; // Range: 0-9, 9 is highest quality

/*
 ******************************************************************************
 * Multiple page configuration
 ******************************************************************************
 */
$_CONSTANTS['MAX_ENTRIES_PER_PAGE'] = 20;

// Number of pages to display to the left and right of the current page
$_CONSTANTS['ENTRY_PAGINATION_SPAN'] = 6;

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
 * Tags to allow in filtered posts
 */
$_CONSTANTS['STRIP_TAGS_WHITELIST'] = "<strong><em><p><img[src|alt|title]><a[href|title]>";

$_CONSTANTS['CACHE_DIR'] = '../ennui-cms/cache/';
$_CONSTANTS['CACHE_EXPIRES'] = 86400;