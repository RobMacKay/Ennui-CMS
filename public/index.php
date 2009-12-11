<?php
	/*
	 * Starts output buffering and enables GZIP compression
	 */
	ob_start("ob_gzhandler");

	/*
	 * Initializes the core functionality of the CMS
	 */
	require_once '../ennui-cms/core/core.inc.php';

	/*
	 * Classes used in the sidebar or in widgets need to be included explicitly
	 */
	require_once '../ennui-cms/inc/class.single.inc.php';
	require_once '../ennui-cms/inc/class.blog.inc.php';

	/*
	 * Initialize classes used in the sidebar or in widgets
	 */
	$minibio = new Single(NULL, array('minibio'));
?>
<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" type="text/css" media="screen,projection"
		href="/assets/css/default.css" />
<?php if(file_exists(dirname($_SERVER['SCRIPT_FILENAME'])."/assets/css/$obj->url0.css")): ?>
	<link rel="stylesheet" type="text/css" media="screen,projection"
		href="/assets/css/<?php echo $obj->url0 ?>.css" />
<?php
	endif;

	/*
	 * If the user is logged in, load stylesheets for the admin controls
	 */
	if($obj->url0=="admin"
		|| isset($_SESSION['loggedIn'])
		&& $_SESSION['loggedIn']==1):
?>
	<link rel="stylesheet" type="text/css" media="screen,projection"
		href="/assets/css/admin.css" />
	<link rel="stylesheet" type="text/css" media="screen,projection"
		href="/assets/css/uploadify.css" />
<?php endif ?>
	<!--[if lte IE 8]>
		<link rel="stylesheet" type="text/css" media="screen,projection"
			href="/css/ie8.css" />
	<![endif]-->
	<!--[if lte IE 7]>
		<link rel="stylesheet" type="text/css" media="screen,projection"
			href="/css/ie7.css" />
	<![endif]-->
    <!--[if lte IE 6]>
		<link rel="stylesheet" type="text/css" media="screen,projection"
			href="/css/ie6.css" />
    <![endif]-->

	<title><?php echo $title; ?></title>
</head>

<body>

	<div id="master">
		<div id="header">
			<h1 id="header_title"> <a href="/"><?php echo SITE_NAME; ?></a> </h1>
<?php echo Utilities::buildMenu($url_array, $menuPages) ?>
		</div><!-- end #header -->
		<div id="content">
			<div class="entrydisplay" id="<?php echo $obj->url0 ?>">
<?php echo $obj->displayPublic() ?>
			</div><!-- end .entrydisplay -->
			<div id="sidebar">
				<div id="<?php echo $minibio->url0 ?>"><?php echo $minibio->displayPublic(); ?>

				</div><!-- end #minibio -->
				<div class="entries-list">
					<h2> Most Recent Entries </h2>
<?php echo blog::displayRecentPosts() ?>
					<a href="/blog/category/recent/3/">See More Entries</a>
				</div><!-- end .entries-list -->
			</div><!-- end #sidebar -->
			<div id="footer">
				<p class="credits">
					All content &copy; <?php echo Utilities::copyrightYear(SITE_CREATED_YEAR), ' ', SITE_NAME; ?> |
					<a href="<?php echo SITE_URL ?>/contact">Contact Us</a> |
					<a href="http://ennuidesign.com" rel="external">Website by Ennui Design</a>
				</p>
			</div><!-- end #footer -->
		</div><!-- end #content -->
	</div><!-- end #master -->

	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script type="text/javascript">
		google.load("jquery", "1");
		google.load("jqueryui", "1");
	</script>
<?php
	/*
	 * If the user is logged in, load JavaScript for the admin controls
	 */
	if($obj->url0=="admin" || isset($_SESSION['loggedIn']) && $_SESSION['loggedIn']==1):
?>
	<script type="text/javascript" src="/assets/js/tiny_mce/jquery.tinymce.js"></script>
	<script type="text/javascript" src="/assets/js/ennui.admin.js"></script>
	<script type="text/javascript" src="/assets/js/jquery.uploadify.js"></script>
<?php endif ?>
	<script type="text/javascript" src="/assets/js/ennui.init.js"></script>

<?php
	/*
	 * If a Google Analytics user is set, include the Google Analytics code
	 */
	if( GOOGLE_ANALYTICS_USER != "" ):
?>
	<!--// Google Analytics //-->
	<script type="text/javascript" src="http://www.google-analytics.com/ga.js"></script>
	<script type="text/javascript">
		var pageTracker = _gat._getTracker("<?php echo GOOGLE_ANALYTICS_USER; ?>");
		pageTracker._trackPageview();
	</script>
	<!--// End Google Analytics //-->
<?php endif; ?>

</body>

</html>
<?php
  $mysqli->close();
  ob_end_flush();
?>