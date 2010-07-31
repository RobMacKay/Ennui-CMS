<?php

/**
 * Generic functions for page interactions.
 * 
 * This class handles database interaction and file uploads for most publicly
 * displayed pages built on the EnnuiCMS platform.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available at
 * http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @author     Drew Douglass <drew.douglass@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 */
class Page extends AdminUtilities
{
    /**
     * Image dimensions
     * 
     * @var array 
     */
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
     * Loads the mysqli object and organizes the URL into variables
     *
     * @param object $mysqli
     * @param array $url_array
     */
    public function __construct($url_array=NULL)
    {
        // Creates a database object
        parent::__construct();
		
        for ( $i=0, $c=count($url_array); $i<$c; ++$i )
        {
            if ( !empty($url_array[$i]) )
            {
                $prop = "url$i";
                $this->$prop = $url_array[$i];
            }
        }
    }

    public function getPageTitle($menuPage)
    {
        $page = $menuPage['display'];
        $title = SITE_TITLE;
        $sep = SITE_TITLE_SEPARATOR;

        $lookup = array(
                'category', 'more'
            );

        if ( in_array($this->url1, $lookup) && isset($this->url2) )
        {
            $entry_title = ucwords(str_replace("-", " ", $this->url2));
            $entry = ucwords(str_replace("-", " ", $this->url1))
                    . ": " .$entry_title .  $sep;
        }
        else if ( isset($this->url1) )
        {
            $arr = $this->getEntryByUrl($this->url1);
            $entry = isset($arr[0]['title']) ? $arr[0]['title'] . $sep : NULL;
        }
        else
        {
            $entry = NULL;
        }

        return "$entry $page $sep $title";
    }

    public function getPageDescription()
    {
        if ( isset($this->entries[0]->excerpt) )
        {
            return strip_tags($this->entries[0]->excerpt);
        }
        else if ( isset($this->entries[0]->entry) )
        {
            $preview = Utilities::textPreview($this->entries[0]->entry, 25);
            return strip_tags($preview);
        }
        else
        {
            return SITE_DESCRIPTION;
        }
    }

    protected function getEntriesByCategory($category, $limit=10, $offset=0)
    {
        /*
         * Prepare the query and execute it
         */
        $sql = "SELECT
                id, page, title, subhead, body, img, imgcap, data1, data2,
                data3, data4, data5, data6, data7, data8, author, created
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                AND LOWER(data2) LIKE ?
                ORDER BY created DESC
                LIMIT $offset, $limit";
        $stmt = $this->mysqli->prepare($sql);
        $var = '%'.str_replace('-', ' ', $category).'%';
        $stmt->bind_param("ss", $this->url0, $var);
        return $this->loadEntryArray($stmt);
    }

    protected function getEntryOrder( $id )
    {
        $sql = "SELECT data7
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE id=?
                LIMIT 1";
		$stmt = $this->mysqli->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->bind_result($data7);
		$stmt->fetch();
		$stmt->close();

        return $data7;
    }

    protected function setDefaultEntry( $admin=NULL )
    {
        // Set default values if no entries are found
        $default = new Entry();
        $default->admin = isset($admin) ? $admin : '';
        $default->title = "No Entry Found";
        $default->entry = "<p>That entry doesn't appear to exist.</p>";
        $this->entries = array($default);

        // Load the default template
        return 'default.inc';
    }

    public function reorderEntries( $id, $pos, $direction )
    {
        $newpos = ($direction=="up") ? $pos-1 : $pos+1;
        $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                SET data7=?
                WHERE page=?
                AND id=?
                LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("isi", $newpos, $_POST['page'], $id);
        $stmt->execute();
        $stmt->close();

        $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."entryMgr`
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
     * Removes an entry from the entryMgr table
     *
     * @param int $id
     * @return bool        Returns TRUE on success, FALSE on failure
     */
    public function delete($id)
    {
        $sql = "DELETE FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE id=?
                LIMIT 1";
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
     * @return string    Image path on success or FALSE on failure
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
                    $img_ctrl->preview = TRUE;
                    $img_ctrl->max_dims = array(IMG_PREV_WIDTH, IMG_PREV_HEIGHT);
                    if ( !$img_ctrl->processStoredImage($stored) )
                    {
                        throw new Exception("Couldn't create image preview!");
                    }
                    $img_ctrl->preview = FALSE;
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
     * @return string    the path of the successfully uploaded file
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

        $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."imgCap`
                    (album_id, photo_id, photo_cap)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE photo_cap=?";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("isss", $albumID, $imageID, $imageCap, $imageCap);
        $stmt->execute();
        $stmt->close();
        return TRUE;
    }
}
