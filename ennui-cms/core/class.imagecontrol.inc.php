<?php
/**
 *  Class ImageControl
 * 
 * Description:
 * 		A set of controls to easily upload, move, and resize images. Works with
 * 		JPG, GIF, and PNG files (preserves alpha transparency with PNG files).
 * 
 * Source:
 * 		http://ennuidesign.com/projects/ImageControl/
 * 
 * Usage:
 * 		<code>
 * 		$myClass = new ImageControl(400, 325);
 * 		</code>
 *
 * @author		Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright	2009 Ennui Design
 * @license		http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version		Release: 1.0.0
 * @link		http://ennuidesign.com/projects/ImageControl/
 */
class ImageControl
{
    /**
	 * Directory to save/find files
	 * 
	 * NOTE: The trailing slash is necessary for proper execution
	 * 
	 * @var string
	 */
	public $dir = IMG_SAVE_DIR;

	/**
	 * Dimensions to which image will be resized
	 * 
	 * Format: array(
	 * 			0=>500, // Maximum allowed width
	 * 			1=>375, // Maximum allowed height
	 * 		   )
	 * 
	 * @var array
	 */
	public $max_dims = array(500, 375);

	/**
	 * Filter to be applied to an image via image_filter()
	 * 
	 * @var string The filter name (i.e. IMG_FILTER_GRAYSCALE)
	 */
	public $filter;

	/**
	 * If applicable, arguments to accompany the selected filter
	 * 
	 * @var array The filter arguments
	 */
	public $filter_args = array();

	/**
	 * The suffix to be added to filtered image file names
	 * 
	 * If empty, the original image will be overwritten.
	 * 
	 * @var string The suffix to be appended to the file name
	 */
	public $filter_suffix = "-filtered";

	/**
	 * If TRUE, creates a square thumbnail of selected images
	 * 
	 * @var bool
	 */
	public $thumb = FALSE;

	/**
	 * If TRUE, places resized images in a folder called preview
	 * 
	 * @var bool
	 */
	public $preview = FALSE;

	/**
	 * Resizes/resamples an image uploaded via a web form
	 * 
	 * @param array $upload	the array contained in the $_FILES superglobal
	 * @param bool	$rename	flag that determines whether or not to rename the file
	 * 
	 * @return string		the path to the resized uploaded file
	 * @access public
	 */
	public function processUploadedImage($upload, $rename=TRUE)
	{
		/*
		 * Simplify the $_FILES variables
		 */
		list($name, $type, $tmp, $err, $size) = array_values($upload);

		/*
		 * Collect file type information and get new dimensions
		 */
		$imgInfo = $this->getImageInfo($type);

		$filename = $this->renameFile($name, $imgInfo[0], $rename);
		$fullpath = $this->dir . $filename;

		/*
		 * Verify that the upload directory has been created
		 */
		$this->checkDir();

		if(!move_uploaded_file($tmp,$fullpath)) {
			throw new Exception('Could not move file (' 
				. $name 
				. ') to the specified directory (' 
				. $this->dir 
				. ')');
		}

		return $this->doProcessing($fullpath, $imgInfo);
	}

	/**
	 * Resizes/resamples an image already stored on the server
	 * 
	 * @param string $image	path to the image on the server
	 * @param bool $thumb	whether or not a thumbnail should be made
	 * @param bool $rename	whether or not the file should be renamed
	 * 
	 * @return string		the path to the new, processed image
	 */
	public function processStoredImage($image, $thumb=FALSE, $rename=FALSE)
	{
		$type = exif_imagetype($image);
		$imgInfo = $this->getImageInfo($type);

		/*
		 * Verify that the thumb directory has been created
		 */
		$this->thumb = $thumb;
		$this->checkDir();
		
		if(($fullpath=$this->doProcessing($image, $imgInfo)) !== false) {
			return $fullpath;
		} else {
			throw new Exception('Error processing the file.<br />\n');
		}
	}

	/**
	 * Accesses methods to find dimensions and resize the image
	 * 
	 * @param string $fullpath	the path to the image on the server
	 * @param array  $imgInfo	array of information about the image
	 * 
	 * @return string			the path to the processed image
	 */
	private function doProcessing($fullpath, $imgInfo)
	{
		$dims = $this->getNewDimensions($fullpath);
		if($loc = $this->doImageResize($fullpath, $dims, $imgInfo)) {
			return $loc;
		} else {
			return false;
		}
	}

	/**
	 * Determines the file type and associated processing functions
	 * 
	 * @param string $type  the image file type
	 * 
	 * @return array	an array with 3 elements:
	 * 					0 - file extension,
	 * 					1 - imagecreatefrom___ function name,
	 * 					2 - image___ function name
	 * @access private
	 */
	private function getImageInfo($type)
	{
		switch($type) {
			case 1:
			case 'image/gif':
				$imgInfo = array('.gif', 'imagecreatefromgif', 'imagegif');
				break;
			case 2:
			case 'image/jpeg':
			case 'image/pjpeg':
				$imgInfo = array('.jpg', 'imagecreatefromjpeg', 'imagejpeg');
				break;
			case 3:
			case 'image/png':
				$imgInfo = array('.png', 'imagecreatefrompng', 'imagepng');
				break;
			default:
				throw new Exception('This file is not in JPG, GIF, or PNG format!');
		}

		return $imgInfo;
	}

	/**
	 * Checks image dimensions and scales down if image exceeds maximum specs
	 * 
	 * @param string $img	the path to the image to be checked
	 * 
	 * @return array 	an array containing 4 elements:
	 *					0 - new width,
	 *					1 - new height,
	 *					2 - original width,
	 *					3 - original height,
	 *					4 - X offset (for thumbs),
	 *					5 - Y offset (for thumbs)
	 * @access private
	 */
	private function getNewDimensions($img)
	{
		if(!$img) {
			throw new Exception('No image supplied');
		} else {

			/*
			 * Get the dimensions of the original image
			 */
			list($src_w,$src_h) = getimagesize($img);

			/*
			 * If the image is bigger than our max values, calculate
			 * new dimensions that keep the aspect ratio intact
			 */
			if($src_w>$this->max_dims[0] || $src_h>$this->max_dims[1]) {

				/*
				 * Squares off the image if set to TRUE
				 */
				if($this->thumb===TRUE) {
					$new[0] = $this->max_dims[0];
					$new[1] = $this->max_dims[1];
					if($src_w>$src_h) {
						$to_x = round($this->max_dims[0]/$src_h*$src_w-$this->max_dims[0]/2);
						$to_y = 0;
						$src_w = $src_h;
					} else {
						$to_x = 0;
						$to_y = round($this->max_dims[0]/$src_w*$src_h-$this->max_dims[0]/2);
						$src_h = $src_w;
					}
				}

				/*
				 * Non-thumbnail resizing
				 */
				else {
					if($src_w > $src_h) {
						$scale = $this->max_dims[0]/$src_w;
						$dblchk = 1; // Identifies the short side
					} else {
						$scale = $this->max_dims[1]/$src_h;
						$dblchk = 0; // Identifies the short side
					}
					$new[0] = round($scale*$src_w);
					$new[1] = round($scale*$src_h);
	
					/*
					 * Double-checks to make sure image fits within the 
					 * boundaries and processes it again if not
					 */
					if($new[$dblchk]>$this->max_dims[$dblchk]) {
						$scale = $this->max_dims[$dblchk]/$new[$dblchk];
						$new[0] = round($scale*$new[0]);
						$new[1] = round($scale*$new[1]);
					}
					$to_x = 0;
					$to_y = 0;
				}

				/*
				 * Sets the array to return
				 */
				$imgDims = array($new[0], $new[1], $src_w, $src_h, $to_x, $to_y);
			} else {
				$imgDims = array($src_w, $src_h, $src_w, $src_h, 0, 0);
			}
			return $imgDims;
		}
	}

	/**
	 * Creates a resized version of the supplied image
	 * 
	 * @param string $loc	the path to the image
	 * @param array  $dims	array of image size information
	 * @param array  $funcs	array of image type-specific functions supplied by getImageInfo()
	 * 
	 * @return bool		true on success
	 * @access private
	 */
	private function doImageResize($loc, $dims, $funcs)
	{
		/*
		 * Sets the final location for the file depending upon whether or not
		 * the script is creating a thumbnail or not
		 */
		if($this->thumb===TRUE) {
			$finalloc = str_replace($this->dir, $this->dir.'thumbs/', $loc);
		} elseif($this->preview===TRUE) {
			$finalloc = str_replace($this->dir, $this->dir.'preview/', $loc);
		} else {
			$finalloc = $loc;
		}

		/*
		 * If a filter has been specified, add the filter suffix to the file
		 * name so the filtered image doesn't override the default image.
		 */
		if(isset($this->filter)) {
			$pattern = '/([\w]+)(\.[a-z]{3,4})/';
			$replacement = "$1$this->filter_suffix$2";
			$finalloc = preg_replace($pattern, $replacement, $finalloc);
		}

		/*
		 * Use the stored functions from getImageInfo() to create an image
		 * resource and a resource to copy the resampled image into
		 */
		$src_img = $funcs[1]($loc);
		$new_img = imagecreatetruecolor($dims[0], $dims[1]);

		/*
		 * Because PNG images support alpha transparency, they are handled
		 * differently here.
		 */
		if($funcs[0]=='.png') {
			imagealphablending($new_img, false);
			imagesavealpha($new_img, true);
		}

		/*
		 * Resamples the image, then free the resources used for the original
		 */
		if(imagecopyresampled($new_img, $src_img, 0, 0, $dims[4], $dims[5], $dims[0], $dims[1], $dims[2], $dims[3])) {
			imagedestroy($src_img);

			/*
			 * Runs the filtering function if a filter was specified
			 */
			if(isset($this->filter) 
				&& $filtered=$this->applyFilter($new_img)) {
				$new_img = $filtered;
			}

			/*
			 * Saves the newly resized and resampled image in the 
			 * destination specified above, then frees the resources used for
			 * the temporary image
			 */
			if($new_img && $funcs[2]($new_img, $finalloc)) {
				imagedestroy($new_img);
				return $finalloc;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Applies a filter to an image using image_filter()
	 * 
	 * @param resource $im An image resource to be filtered
	 * 
	 * @return resource The filtered image
	 */
	private function applyFilter($im)
	{
		if(function_exists('imagefilter')) {
			list($arg1, $arg2, $arg3, $arg4) = $this->filter_args;
			if($im && imagefilter($im, $this->filter, $arg1, $arg2, $arg3, $arg4)) {
				return $im;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * If set to TRUE, generates a new name for a file
	 * 
	 * @param string $name		the name of the file
	 * @param string $ext		the file extension
	 * @param bool   $rename	flag to trigger rename
	 * 
	 * @return string	the new file name
	 * @access private
	 */
	private function renameFile($name, $ext, $rename)
	{
		return ($rename===TRUE) ? time().'_'.mt_rand(1000,9999).$ext : $name;
	}

	/**
	 * Checks if a directory exists, then creates it if it doesn't
	 * 
	 * @return void
	 */
	private function checkDir()
	{
		$dir = ($this->thumb===TRUE) ? $this->dir.'thumbs/' : $this->dir;
		$dir = ($this->preview===TRUE) ? $this->dir.'preview/' : $dir;
		if (!is_dir($dir)&&strlen($dir)>0) {
	  		mkdir($dir,0777,true) or die("'$dir' could not be created.<br />");
		}
	}
}

?>