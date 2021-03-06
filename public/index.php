<?php

    // Define a path for loading the CMS. Default is ../ennui-cms/
    define('CMS_PATH', '../ennui-cms/');

    // Initializes the core functionality of the CMS
    require_once CMS_PATH . 'core/init.inc.php';

    FB::info("Developers: FirePHP is included in the core for easy debugging!");

    // Initialize classes used in the sidebar or in widgets
    $sidebar = new Single($dbo, array('sidebar'));

?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title><?php echo $title; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>" />

    <!-- CSS File Includes -->
    <link rel="stylesheet" type="text/css" media="screen,projection"
          href="/assets/css/default.css" />
<?php
if(file_exists(dirname($_SERVER['SCRIPT_FILENAME'])."/assets/css/$obj->url0.css")):
?>

    <link rel="stylesheet" type="text/css" media="screen,projection"
          href="/assets/css/<?php echo $obj->url0 ?>.css" />
<?php
    endif;

    // If the user is logged in, load stylesheets for the admin controls
    if ( isset($_SESSION['user']) && $_SESSION['user']['clearance']>=1 ):
?>

    <link rel="stylesheet" type="text/css" media="screen,projection"
          href="/assets/css/admin.css" />
    <link rel="stylesheet" type="text/css" media="screen"
          href="/assets/js/thumbbox/css/jquery.ennui.thumbbox.css" />
    <link rel="stylesheet" type="text/css" media="screen,projection"
          href="/assets/css/uploadify.css" />
<?php endif ?>

    <!--[if lte IE 8]>
        <script type="text/javascript"
            src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
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
</head>

<body>

    <header>
        <h1 id="header_title"> <a href="/"><?php echo SITE_NAME; ?></a> </h1>
        <nav>
<?php echo Utilities::buildMenu($url_array, $menuPages); ?>

        </nav>

    </header>

    <div id="content">
        <section class="entrydisplay" id="<?php echo $obj->url0 ?>">

<!-- BEGIN GENERATED CONTENT -->

<?php echo $obj->displayPublic() ?>


<!-- END GENERATED CONTENT -->

        </section><!-- end .entrydisplay -->
        <aside>
            <div id="search">
<?php echo Search::displaySearchBox(); ?>

            </div><!-- end #search -->
            <div id="<?php echo $sidebar->url0 ?>"><?php echo $sidebar->displayPublic(); ?>

            </div>
            <div class="recent-blogs">
                <h2>Latest Blogs</h2>
<?php echo Blog::displayPosts(); ?>

                <a href="/blog/category/recent" class="see-all">See All</a>
            </div><!-- end .recent-blogs -->
        </aside>
    </div><!-- end #content -->

    <footer>
        <p class="credits">
            All content &copy;
<?php echo Utilities::copyrightYear(SITE_CREATED_YEAR), ' ', SITE_NAME; ?> |
            <a href="<?php echo SITE_URL ?>contact">Contact Us</a> |
            <a href="http://ennuidesign.com"
               rel="external">Website by Ennui Design</a>
        </p>
    </footer>

    <!-- Load jQuery and jQuery UI -->
    <script type="text/javascript"
            src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
        google.load("jquery", "1");
        google.load("jqueryui", "1");
    </script>
    <script type="text/javascript"
            src="/assets/js/thumbbox/jquery.ennui.thumbbox.js"></script>
<?php
    // If the user is logged in, load JavaScript for the admin controls
    if($obj->url0=="admin" || isset($_SESSION['user'])
            && $_SESSION['user']['clearance']>=1 ):
?>

    <!-- Admin JS Files -->
    <script type="text/javascript"
            src="/assets/js/tiny_mce/jquery.tinymce.js"></script>
    <script type="text/javascript"
            src="/assets/js/jquery.easing.js"></script>
    <script type="text/javascript"
            src="/assets/js/ennui.admin.js"></script>
    <script type="text/javascript"
            src="/assets/js/jquery.uploadify.js"></script>
<?php endif; ?>

    <!-- Initialization JS File -->
    <script type="text/javascript"
            src="/assets/js/ennui.init.js"></script>
<?php
    // If a Google Analytics user is set, include the Google Analytics code
    if( GOOGLE_ANALYTICS_USER != "" ):
?>

    <!-- Google Analytics -->
    <script type="text/javascript" src="http://www.google-analytics.com/ga.js"></script>
    <script type="text/javascript">
        var pageTracker = _gat._getTracker("<?php echo GOOGLE_ANALYTICS_USER; ?>");
        pageTracker._trackPageview();
    </script>
<?php endif; ?>


</body>

<?php
    // Generate a quick note to let geeks know how long the page render took
    echo "<!-- Page rendered by Ennui CMS in ",
            round((microtime(TRUE)-$start_time)*1000), " milliseconds -->";
?>


</html><?php
    // Clean the buffer
    $cache = ob_get_clean();

    // TODO: Save cache if none exists
    echo $cache;
