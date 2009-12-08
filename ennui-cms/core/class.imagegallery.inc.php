<?php
/**
 *  Class ImageGallery
 * 
 * Description:
 * 		Extends the functionality of the ImageControl class to allow for easy
 * 		reading and display of images stored in a folder.
 * 
 * Requirements:
 * 		PHP 5+
 * 		ImageControl.inc.php
 * 		
 * 
 * Source:
 * 		http://ennuidesign.com/projects/ImageGallery/
 * 
 * Usage:
 * 	<code>
 * 		require_once 'path/to/ImageControl.inc.php';
 * 		require_once 'path/to/ImageGallery.inc.php';
 * 
 * 		try {
 * 			$gal = new ImageGallery();
 * 			$gal->max_dims = array(550, 400);	// Maximum dimensions of the images (width, height, thumbnail)
 * 			$gal->dir = 'gallery/';				// Folder you want to read images from
 * 			$gal->altAttr = 'Class Test';		// Alt attribute (to produce valid markup)
 * 			$gal->getImages();					// Reads all images out of a folder
 * 			$gal->checkSize();					// Makes sure the images are the right size
 * 			$gal->makeThumb(120);				// Creates thumbs, stored in 'thumbs/' in directory defined above
 * 		} catch(Exception $e) {
 * 			echo $e->getMessage();
 * 		}
 * 	</code>
 *
 * @author		Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright	2009 Ennui Design
 * @license		http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version		Release: 1.0.0
 * @link		http://ennuidesign.com/projects/ImageGallery/
 */
class ImageGallery extends ImageControl
{
	/**
	 * Location of ImageControl class
	 * 
	 * @var string
	 */
	public $classLoc = '_class/ImageControl.inc.php';
	public $relAttr;
	public $altAttr = "Created by Ennui Design's ImageGallery Tool";
	public $imgCap_album = NULL;

	/**
	 * Array of files found in the specified folder
	 * 
	 * @var array
	 */
	private $_imageArray = array();

	/**
	 * Loads the class variable with
	 * 
	 * @return void
	 * @access public
	 */
	public function getImages()
	{
		if(is_dir($this->dir))
		{
			if($folder = opendir($this->dir))
			{
				while(($file = readdir($folder)) !== false)
				{
					/*
					 * Verifies that the current value of $file
					 * refers to an existing file and that the 
					 * file is big enough not to throw an error.
					 */
					if(is_file($this->dir.$file) 
					&& filesize($this->dir.$file) > 11)
					{
						/*
						 * Checks the image type of the file,
						 * which verifies that the file is 
						 * actually an image. If the check doesn't
						 * fail, adds the image to the array
						 */
						if(exif_imagetype($this->dir.$file) !== false)
						{
							$this->_imageArray[] = $file;
						}
					}
				}
				natsort($this->_imageArray);
				array_values($this->_imageArray);
			}
		}
	}

	/**
	 * Create thumbnails for each photo in the folder
	 */
	public function makeThumb($size=140)
	{
		foreach($this->_imageArray as $img) {

			/*
			 * Checks if the file already has a thumbnail
			 */
			if(!file_exists($this->dir.'thumbs/'.$img)) {

				/*
				 * Verifies that the 'thumbs/' directory exists within the main
				 * directory
				 */
				if (!is_dir($this->dir.'thumbs/')) {

					/*
					 * If the directory doesn't exist, creates the folder
					 */
					if(!mkdir($this->dir.'thumbs/',0777,true))
						throw new Exception("Couldn't create the thumbnail 
							directory.");
				}

				/*
				 * Sets the thumbnail size and sends the image for processing
				 */
				$this->max_dims = array($size, $size);
				$this->processStoredImage($this->dir.$img, TRUE);
			}
		}
	}

	/**
	 * Checks if an image is within the defined size constraints
	 * 
	 * @return void
	 */
	public function checkSize()
	{
		$preview = TRUE;
		foreach($this->_imageArray as $img) {
			list($w, $h) = getimagesize($this->dir.$img);

			if(!is_file($this->dir."preview/".$img))
			{
				$preview = FALSE;
			}

			/*
			 * If the image is larger than the defined maximum width and 
			 * height, it's sent to be processed
			 */
			if($w > $this->max_dims[0] || $h > $this->max_dims[1]) {
				$this->processStoredImage($this->dir.$img);
			}
		}
		return $preview;
	}

	/**
	 * Displays the images
	 * 
	 * @return string The HTML to display gallery images.
	 */
	public function displayGallery()
	{
		$display = "\t\t\t\t\t<ul class=\"thumbbox\">\n";
		foreach($this->_imageArray as $img) {
			if ( isset($this->imgCap_album) )
			{
				$title = $this->getImageCaption($img);
			}
			else
			{
				$title = (isset($this->imgTitle)) ? " title=\"$this->imgTitle\"" : NULL;
			}
			$thumb = '<img src="/'.$this->dir."thumbs/".$img.'" alt="Gallery Image" />';
			$display .= "\t\t\t\t\t\t<li>\n\t\t\t\t\t\t\t<a href=\"/$this->dir$img\" "
				. " title=\"$title\">$thumb</a>\n\t\t\t\t\t\t</li>\n";
		}
		return $display . "\t\t\t\t\t</ul>\n";
	}

	public function getImagesAsArray()
	{
		return $this->_imageArray;
	}

	public function getFirstImage()
	{
		if(isset($this->_imageArray[0]))
		{
			return $this->dir.array_shift($this->_imageArray);
		}
		else
		{
			return NULL;
		}
	}

	public function getNumImages()
	{
		return count($this->_imageArray);
	}

	public function getImageCaption($img)
	{
		$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
		$sql = "SELECT photo_cap
				FROM imgCap
				WHERE album_id=:album
				AND photo_id=:photo
				LIMIT 1";
		$stmt = $db->prepare($sql);
		$stmt->bindParam(":album", $this->imgCap_album, PDO::PARAM_INT);
		$stmt->bindParam(":photo", substr($img, 3), PDO::PARAM_STR);
		$stmt->execute();
		$r = $stmt->fetch();
		$stmt->closeCursor();
		return isset($r['photo_cap']) ? $r['photo_cap'] : NULL;
	}

	/**
	 * ToString method
	 * 
	 * @return string The result of the displayGallery method
	 */
	public function __toString()
	{
		return $this->displayGallery();
	}
}

?>