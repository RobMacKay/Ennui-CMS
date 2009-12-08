<?php

/**
 * Generic functions for page interactions.
 * 
 * This class handles database interaction and file uploads for most publicly
 * displayed pages built on the EnnuiCMS platform.
 *
 */
class Page extends AdminUtilities
{
	/**
	 * The mysqli database object
	 *
	 * @var object
	 */
	public $mysqli;

	public $img_dims = array(
		'w' => IMG_MAX_WIDTH, 
		'h' => IMG_MAX_HEIGHT, 
		't' => IMG_THUMB_SIZE
	);

	/**
	 * First level URL index
	 *
	 * @var string
	 */
	public $url0;

	/**
	 * Second level URL index
	 *
	 * @var string
	 */
	public $url1;

	/**
	 * Third level URL index
	 *
	 * @var string
	 */
	public $url2;

	/**
	 * Fourth level URL index
	 *
	 * @var string
	 */
	public $url3;

	/**
	 * Page header (optional)
	 *
	 * @var string
	 */
	public $headline=NULL;

	/**
	 * Loads the mysqli object and organizes the URL into variables
	 *
	 * @param object $mysqli
	 * @param array $url_array
	 */
	public function __construct($mysqli=NULL, $url_array=NULL)
	{
		if(isset($mysqli)) {
			$this->mysqli = $mysqli;
		} else {
			$this->mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		}
		$this->url0 = (isset($url_array[0]) && !empty($url_array[0])) ? $url_array[0] : NULL;
		$this->url1 = (isset($url_array[1]) && !empty($url_array[1])) ? $url_array[1] : NULL;
		$this->url2 = (isset($url_array[2]) && !empty($url_array[2])) ? $url_array[2] : NULL;
		$this->url3 = (isset($url_array[3]) && !empty($url_array[3])) ? $url_array[3] : NULL;
	}

	/**
	 * Returns an entry by its ID
	 *
	 * @param int $id
	 * @return array	The entry as an associative array
	 */
	public function getEntryById($id)
	{
		/*
		 * Prepare the query and execute it
		 */
		$sql = "SELECT
				id, page, title, subhead, body, img, imgcap, data1, data2,
				data3, data4, data5, data6, data7, data8, author, created
				FROM entryMgr
				WHERE id=?
				LIMIT 1";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $id);
		return $this->loadEntryArray($stmt);
	}

	public function getEntryByUrl($url)
	{
		$title = urldecode($url);

		/*
		 * Prepare the query and execute it
		 */
		$sql = "SELECT
				id, page, title, subhead, body, img, imgcap, data1, data2,
				data3, data4, data5, data6, data7, data8, author, created
				FROM entryMgr
				WHERE title LIKE ?
				LIMIT 1";
		$stmt = $this->mysqli->prepare($sql);
		$var = '%'.$title.'%';
		$stmt->bind_param("s", $var);
		return $this->loadEntryArray($stmt);
	}

	public function getEntriesByCategory($category, $limit=10, $offset=0)
	{
		/*
		 * Prepare the query and execute it
		 */
		$sql = "SELECT
				id, page, title, subhead, body, img, imgcap, data1, data2,
				data3, data4, data5, data6, data7, data8, author, created
				FROM entryMgr
				WHERE data2 LIKE ?
				ORDER BY created DESC
				LIMIT $offset, $limit";
		$stmt = $this->mysqli->prepare($sql);
		$var = '%'.str_replace('-', ' ', $category).'%';
		$stmt->bind_param("s", $var);
		return $this->loadEntryArray($stmt);
	}

	/**
	 * Retrieves all values for the given page from the database
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return array	A multi-dimensional array of entries
	 */
	public function getAllEntries($limit=10, $offset=0, $orderby="created DESC")
	{
		$entries = array();

		/*
		 * Prepare the statement and execute it
		 */
		$sql = "SELECT
				id, page, title, subhead, body, img, imgcap, data1, data2,
				data3, data4, data5, data6, data7, data8, author, created
				FROM entryMgr
				WHERE page=?
				ORDER BY $orderby
				LIMIT $offset, $limit";
		if($stmt = $this->mysqli->prepare($sql)) {
			$stmt->bind_param("s", $this->url0);
			return $this->loadEntryArray($stmt);
		} else {
			exit('Database query failed. '.$this->mysqli->error);
		}
	}

	private function loadEntryArray($stmt)
	{
		$stmt->execute();
		$stmt->bind_result($id, $page, $title, $subhead, $body, $img, $imgcap,
			$data1, $data2, $data3, $data4, $data5, $data6, $data7, $data8,
			$author, $created);

		/*
		 * Cycle through the results and load each into an array element
		 */
		$entries = array();
		while($stmt->fetch()) {
			$img = (substr($img,0,1)!='/') ? $img = "/$img" : $img;
			$entries[] = array(
				'id' => stripslashes($id),
				'page' => stripslashes($page),
				'title' => stripslashes($title),
				'subhead' => stripslashes($subhead),
				'body' => stripslashes($body),
				'img' => stripslashes($img),
				'imgcap' => stripslashes($imgcap),
				'data1' => stripslashes($data1),
				'data2' => stripslashes($data2),
				'data3' => stripslashes($data3),
				'data4' => stripslashes($data4),
				'data5' => stripslashes($data5),
				'data6' => stripslashes($data6),
				'data7' => stripslashes($data7),
				'data8' => stripslashes($data8),
				'author' => stripslashes($author),
				'created' => stripslashes($created)
			);
		}
		$stmt->close();

		return $entries;
	}

	protected function countEntries($page)
	{
		$sql = "SELECT COUNT(id) AS numRows
				FROM entryMgr
				WHERE page=?";
		if($stmt = $this->mysqli->prepare($sql)) {
			$stmt->bind_param("s", $page);
			$stmt->execute();
			$stmt->bind_result($numRows);
			$stmt->fetch();
			return $numRows;
		} else {
			exit('Database query failed. '.$this->mysqli->error);
		}
	}

	public function parseTemplate($replacement_array, $template)
	{
		//$pattern = "/\{(\w+?)\}/i"; // Matches any template tag
		$markup = NULL;
		$pattern = "/\{loop[|:\s\[|:\[]([\s\w:,]*?)\}/i";
		preg_match($pattern, $template, $matches);
		print_r($matches);
		//$callback = Utilities::curry('Utilities::replaceTags', 2);

		//return preg_replace_callback($pattern, $callback($replacement_array), $template);
	}

	protected function paginateEntries()
	{
		$url0 = empty($this->url0) ? 'blog' : $this->url0;
		$url1 = empty($this->url1) ? 'category' : $this->url1;
		$url2 = empty($this->url2) ? 'recent' : $this->url2;
		$url3 = empty($this->url3) ? 1 : $this->url3;

		$span = 6; // How many pages shown adjacent to current page

		$sql = "SELECT COUNT(*)
				AS theCount
				FROM entryMgr
				WHERE page=?
				AND data2 LIKE ?";
		if($stmt = $this->mysqli->prepare($sql))
		{
			$pagination = "<ul id=\"pagination\">";

			$category = ($url2!='recent') ? '%'.str_replace('-', ' ', $url2).'%' : '%';

			$stmt->bind_param("ss", $url0, $category);
			$stmt->execute();
			$stmt->bind_result($c);
			$stmt->fetch();

			/*
			 * Determine minimum and maximum page numbers
			 */
			$pages = ceil($c/BLOG_PREVIEW_NUM);

			$prev_page = $url3-1;
			if($url3==1)
			{
				$pagination .= "
					<li class=\"off\">
						<span>&#171;</span>
					</li>
					<li class=\"off\">
						<span>&#139;</span>
					</li>";
			}
			else
			{
				$pagination .= "
					<li>
						<a href=\"/$url0/$url1/$url2/1/\">&#171;</a>
					</li>
					<li>
						<a href=\"/$url0/$url1/$url2/$prev_page/\">&#139;</a>
					</li>";
			}

			$mod = ($span>$url3) ? $span-$url3 : 0;
			$max_mod = ($url3+$span>$pages) ? $span-($pages-$url3) : 0;
			$max = ($url3+$span<=$pages) ? $url3+$span+$mod : $pages;
			$max_num = ($max>$pages) ? $pages : $max;
			$min = ($max_num>$span*2) ? $url3-$span-$max_mod : 1;
			$min_num = ($min<1) ? 1 : $min;

			for($i=$min_num; $i<=$max_num; ++$i)
			{
				$sel = ($i==$url3) ? ' class="selected"' : NULL;
				$pagination .= "
					<li$sel>
						<a href=\"/$url0/$url1/$url2/$i/\">$i</a>
					</li>";
			}
			$stmt->close();

			$next_page = $url3+1;
			if($next_page>$pages)
			{
				$pagination .= "
					<li class=\"off\">
						<span>&#155;</span>
					</li>
					<li class=\"off\">
						<span>&#187;</span>
					</li>";
			}
			else
			{
				$pagination .= "
					<li>
						<a href=\"/$url0/$url1/$url2/$next_page/\">&#155;</a>
					</li>
					<li>
						<a href=\"/$url0/$url1/$url2/$pages/\">&#187;</a>
					</li>";
			}

			return $pagination."</ul>";
		}
	}

	public function reorderEntries($id, $pos, $direction)
	{
		$newpos = ($direction=="up") ? $pos-1 : $pos+1;
		$sql = "UPDATE entryMgr
				SET data7=?
				WHERE page=?
				AND id=?
				LIMIT 1";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("isi", $newpos, $_POST['page'], $id);
		$stmt->execute();
		$stmt->close();

		$sql = "UPDATE entryMgr
				SET data7=?
				WHERE page=?
				AND data7=?
				AND id!=?
				LIMIT 1";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("isii", $pos, $_POST['page'], $newpos, $id);
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * Writes data to the database; either updates or creates an entry
	 *
	 * @param array $post
	 * @param array $files
	 * @return bool		Returns true on success or false on error
	 */
	public function write($post, $files)
	{
		/*
		 * Check all the variables and make sure they're escaped for storage
		 */
		$id = isset($post['id']) ? $post['id'] : '';
		$page = $post['page'];
		$title = isset($post['title']) ? $post['title'] : NULL;
		$subhead = isset($post['subhead']) ? $post['subhead'] : NULL;
		$body = isset($post['body']) ? $post['body'] : NULL;
		$imgcap = isset($post['imgcap']) ? $post['imgcap'] : NULL;
		$data1 = isset($post['data1']) ? $post['data1'] : NULL;
		$data2 = isset($post['data2']) ? $post['data2'] : NULL;
		$data3 = isset($post['data3']) ? $post['data3'] : NULL;
		$data4 = isset($post['data4']) ? $post['data4'] : NULL;
		$data5 = isset($post['data5']) ? $post['data5'] : NULL;
		$data6 = isset($post['data6']) ? $post['data6'] : NULL;
		$data7 = isset($post['data7']) ? $post['data7'] : NULL;

		/*
		 * Processes the image and returns the path, or sets the variable to
		 * NULL if no image was uploaded
		 */
		$img = (isset($files['img'])) ? $this->checkIMG($files['img']) : NULL;
		if($img===false) {
			$img = (isset($post['stored_img'])) ? $post['stored_img'] : NULL;
		}

		/*
		 * PDF uploads go through the data8 field. If the $_FILES superglobal isn't
		 * set, handle the input as a string. Otherwise, process as a PDF
		 */
		if(!is_array($files['data8'])) {
			$data8 = $post['data8'] ? $post['data8'] : NULL;
		} else if($files['data8']['size']>0) {
			$data8check = $this->uploadPDF($files['data8'],$title);
			$data8 = ($data8check===false) ? NULL : $data8;
		}

		/*
		 * Store the author's name and a timestamp
		 */
		$author = $_SESSION['admin_u'];
		$created = time();

		/*
		 * If the ID was passed, set up the query to update the entry
		 */
		if ( $id ) {
			$sql = "UPDATE entryMgr SET title=?, subhead=?, body=?, img=?, imgcap=?
					, data1=?, data2=?, data3=?, data4=?, data5=?, data6=?, data7=?
					, data8=? WHERE id=? LIMIT 1";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("sssssssssssssi",$title, $subhead, $body, $img, 
					$imgcap, $data1, $data2, $data3, $data4, $data5, $data6, 
					$data7, $data8, $id);
		}

		/*
		 * Otherwise, save a new entry
		 */
		else {
			$sql = "INSERT INTO entryMgr (page, title, subhead, body, img, imgcap,
					data1, data2, data3, data4, data5, data6, data7, data8, author,
					created) 
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$stmt = $this->mysqli->prepare($sql);
			$stmt->bind_param("ssssssssssssssss", $page, $title, $subhead, $body, 
					$img, $imgcap, $data1, $data2, $data3, $data4, $data5, $data6,
					$data7, $data8, $author, $created);
		}
		$success = $stmt->execute();
		$stmt->close();

		return $success;
	}

	/**
	 * Removes an entry from the entryMgr table
	 *
	 * @param int $id
	 * @return bool		Returns TRUE on success, FALSE on failure
	 */
	public function delete($id)
	{
		$sql = "DELETE FROM entryMgr WHERE id=? LIMIT 1";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $id);
		if ($stmt->execute()) {
			$stmt->close();
			return true;
		} else {
			$stmt->close();
			return false;
		}
	}

	/**
	 * Establishes dimensions for the image, then sends for processing
	 *
	 * @param array $files
	 * @return string	Image path on success or FALSE on failure
	 */
	public function checkIMG($files)
	{
		$img_ctrl = new ImageControl();
		if($files['error']==0) {
			$img_ctrl->max_dims = array($this->img_dims['w'], $this->img_dims['h']);
			try {
				$stored = $img_ctrl->processUploadedImage($files);
				if(!$stored) {
					return false;
				} else {
					$img_ctrl->max_dims = array($this->img_dims['t'], $this->img_dims['t']);
					if($img_ctrl->processStoredImage($stored, TRUE)) {
						return $stored;
					} else {
						return false;
					}
				}
			} catch(Exception $e) {
				exit($e->getMessage());
			}
		} else {
			return false;
		}
	}

	/**
	 * Verifies that an uploaded file is a PDF & saves it to a folder
	 *
	 * @param array $files
	 * @param string $name
	 * @return string	the path of the successfully uploaded file
	 */
	private function uploadPDF($files,$name) {
		$_dir = 'article/';

		/*
		 * If the file isn't a PDF, throw an error
		 */
		if ( $files['type'] != 'application/pdf' ) {
			throw new Exception('Only PDF files are accepted at this time.');
		}

		/*
		 * Make sure all spaces are replaced with underscores
		 */
		$name = str_replace(' ','_',$name).'.pdf';

		/*
		 * If the directory doesn't exist, create it
		 */
		if (!is_dir($_dir)) {
			mkdir($_dir,0777,true) or die("Could not create the directory '$_dir'.");
		}

		/*
		 * Place the uploaded file into the directory
		 */
		move_uploaded_file($files['tmp_name'],$_dir.$name);

		return 'article/'.$name;
	}

	public function deleteImage($url)
	{
		if(is_file($url))
		{
			unlink($url);
			return true;
		}
		else
		{
			return false;
		}
	}

	public function addPhotoCaption()
	{
		$albumID = $_POST['album_id'];
		$imageID = $_POST['image_id'];
		$imageCap = htmlentities(trim($_POST['image_cap']), ENT_QUOTES);

		$sql = "INSERT INTO imgCap (album_id, photo_id, photo_cap)
				VALUES (?, ?, ?)
				ON DUPLICATE KEY UPDATE photo_cap=?";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("isss", $albumID, $imageID, $imageCap, $imageCap);
		$stmt->execute();
		$stmt->close();
		return TRUE;
	}
}

?>