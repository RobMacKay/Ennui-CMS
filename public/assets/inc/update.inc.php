<?php

	include_once CMS_PATH . 'config/config.inc.php';
	include_once CMS_PATH . 'config/database.inc.php';
	include_once CMS_PATH . 'config/menu.inc.php';
	include_once CMS_PATH . 'core/class.page.inc.php';

	$menu = $GLOBALS['menuPages'];

	if(isset($_POST['page'])) {

		// Make sure the posted page exists
		$p = strtolower($_POST['page']);
		if($p=='admin')
		{
			$class = 'admin';
		}

		else if(isset($p))
		{
			$class = Utilities::getPageType($menu, $p);
			if($class === FALSE)
			{
				header('Location: /');
				exit;
			}
		}

		// If not, send the user to the home page
		else
		{
			header("Location: /");
			exit;
		}

		include_once CMS_PATH . 'inc/class.'.$class.'.inc.php';
		$obj = new $class(NULL, array(strtolower($_POST['page'])));

		/*
		 * Standard page action handlers
		 */
		$obj->url0 = $p;
		$id = (isset($_POST['id'])) ? $_POST['id'] : NULL;
		if ( $_POST['action'] == 'showoptions' ) {
			echo $obj->displayAdmin($id);
			exit;
		}
		if($_POST['action'] == 'galleryEdit') {
			echo $obj->displayGalleryAdmin($id);
			exit;
		}
		if($_POST['action'] == 'galleryOrder') {
			$obj->reorderGallery($_POST['image'], $id);
			echo $obj->displayPublic($id);
			exit;
		}
		if ( $_POST['action'] == 'write' ) {
			$loc = str_replace('-image', '', $obj->url0);

			if( $obj->write() ) {
				$header = "Location: /$loc/";
			} else {
				$header = "Location: /{$obj->url0}/error/";
			}
		}
		if ( $_POST['action'] == 'contact_form' ) {
			$loc = str_replace('-image', '', $obj->url0);

			if( $obj->sendMessage($_POST) ) {
				$header = "Location: /$loc?send=successful";
			} else {
				$header = "Location: /$loc?send=error";
			}
		}
		if ( $_POST['action'] == 'reorderEntry' ) {
			echo $obj->reorderEntries($_POST['id'], $_POST['pos'], $_POST['direction']);
			exit;
		}
		if ( $_POST['action'] == 'nl_subscribe' ) {
			$loc = $obj->url0;

			if($obj->saveSubscription($_POST)) {
				$header = "Location: /$loc/";
			} else {
				$header = "Location: /$loc/error/";
			}
		}
		if ( $_POST['action'] == 'nl_viewsubs' ) {
			echo $obj->displaySubs();
			exit;
		}
		if ( $_POST['action'] == 'nl_preview' ) {
			echo $obj->newsletterHTML($_POST['body'], $_POST['subject']);
			exit;
		}
		if ( $_POST['action'] == 'deletepost' ) {
			$url = array(0=>$obj->url0,1=>'',2=>'');
			if ( $obj->delete($id) ) {
				echo $obj->displayPublic($url);
				exit;
			}
			exit("Couldn't delete the post.\n");
		}
		if ( $_POST['action'] == 'galleryAddCaption' ) {
			if ( $obj->addPhotoCaption() ) {
				echo $obj->displayGalleryAdmin($_POST['album_id']);
				exit;
			}
			exit("Couldn't update the image caption.\n");
		}
		if ( $_POST['action'] == 'galleryDeletePhoto' ) {
			$img = $_POST['image'];
			if ( $obj->deleteImage($img) ) {
				echo $obj->displayGalleryAdmin($id);
				exit;
			}
			exit("Couldn't delete the image.\n");
		}

		/*
		 * AJAX Calls
		 */
		if ( $_POST['action'] == 'swapcontent' ) {
			$url = array(0=>$obj->url0,1=>$_POST['title'],2=>'');
			echo $obj->ajax_public($url);
			exit;
		}

		/*
		 * Admin class handlers.
		 */
		else if ( $obj->url0 == 'admin' ) {
			switch($_POST['action']) {
				case 'create':
					$check = $obj->createUser($_POST['admin_u'], $_POST['admin_e']);
					break;
				case 'login':
					$check = $obj->login($_POST);
					break;
				case 'verify':
					$check = $obj->verifyUser($_POST);
					break;
				default:
					$check = false;
					break;
			}
			$header = $check === true ? 'Location: /admin/' : "Location: /{$obj->url0}/error/";
		}
	}

	/*
	 * Comment handlers.
	 */
	else if ( $_POST['action'] == 'cmnt_post' ) {
		require_once CMS_PATH . 'inc/class.comments.inc.php';
		$cmnt = new comments();
		$header = $cmnt->postComment($_POST);
	}

	else if ( $_GET['action'] == 'cmnt_delete' ) {
		require_once CMS_PATH . 'inc/class.comments.inc.php';
		$cmnt = new comments();
		$header = $cmnt->deleteComment($_GET['bid'],$_GET['cmntid']);
	}

	/*
	 * Remove newsletter cookies
	 */
	else if($_GET['action'] == 'nl_cookie')
	{
		require_once CMS_PATH . 'inc/class.newsletter.inc.php';
		Newsletter::removeCookies();
		$header = "Location: /" . htmlentities(strip_tags($_GET['page'])) . '/';
	}

	/*
	 * Log out the user
	 */
	else if ( $_GET['action'] == 'logout' ) {
		include CMS_PATH . 'inc/class.admin.inc.php';
		$admin = new admin();
		$check = $admin->logout();
		$header =  $check === true ? 'Location: /': 'Location: /admin/error/';
	}

	else { $header = "Location: /"; }

	header($header);
?>