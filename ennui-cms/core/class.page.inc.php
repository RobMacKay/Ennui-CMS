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
     * Loads the mysqli object and organizes the URL into variables
     *
     * @param object $mysqli
     * @param array $url_array
     */
    public function __construct($mysqli=NULL, $url_array=NULL)
    {
        // TODO: Migrate to PDO
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
                    . ": " . $entry_title . " " . $sep;
        }
        elseif( !empty($this->url1) )
        {
            $arr = $this->getEntryByUrl($this->url1);
            $entry = isset($arr[0]['title']) ? $arr[0]['title'] . " " . $sep : NULL;
        }
        else
        {
            $entry = NULL;
        }

        return "$entry $page $sep $title";
    }

    protected function getEntryCategories($entries)
    {
        $cat_arr = array();
        if ( isset($entries[0]['data2']) )
        {
            foreach ( $entries as $e )
            {
                $cat_url = strtolower(Utilities::makeUrl($e['data2']));
                if ( !isset($cat_arr[$cat_url]) )
                {
                    $cat_arr[$cat_url] = array(
                            'category-url' => "$this->url0/category/$cat_url",
                            'category-name' => $e['data2'],
                            'count' => 1
                        );
                }
                else
                {
                    $cat_arr[$cat_url]['count'] += 1;
                }
            }

            /*
             * Sort the array
             */
            usort($cat_arr, "CategorizedGallery::cmp");

            /*
             * Load the template into a variable
             */
            $template = UTILITIES::loadTemplate($this->url0.'-category.inc');

            return UTILITIES::parseTemplate(array_values($cat_arr), $template);
        }

        /*
         * If no categories exist, there's no reason to display this view
         */
        else
        {
            return NULL;
        }
    }

    static function cmp($a, $b)
    {
        if ( $a['count']===$b['count'] )
        {
            return 0;
        }
        return $a['count']<$b['count'] ? 1 : -1;
    }

    /**
     * Returns an entry by its ID
     *
     * @param int $id
     * @return array    The entry as an associative array
     */
    protected function getEntryById($id)
    {
        /*
         * Prepare the query and execute it
         */
        $sql = "SELECT
                id, page, title, subhead, body, img, imgcap, data1, data2,
                data3, data4, data5, data6, data7, data8, author, created
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE id=?
                LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param("i", $id);
        return $this->loadEntryArray($stmt);
    }

    protected function getEntryByUrl($url)
    {
        $title = urldecode($url);

        /*
         * Prepare the query and execute it
         */
        $sql = "SELECT
                id, page, title, subhead, body, img, imgcap, data1, data2,
                data3, data4, data5, data6, data7, data8, author, created
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE title LIKE ?
                OR data6=?
                LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        $var = '%'.$title.'%';
        $stmt->bind_param("ss", $var, $title);
        return $this->loadEntryArray($stmt);
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
                WHERE page = ?
                AND LOWER(data2) LIKE ?
                ORDER BY created DESC
                LIMIT $offset, $limit";
        $stmt = $this->mysqli->prepare($sql);
        $var = '%'.str_replace('-', ' ', $category).'%';
        $stmt->bind_param("ss", $this->url0, $var);
        return $this->loadEntryArray($stmt);
    }

    protected function getEntriesBySearch($search, $limit=MAX_ENTRIES_PER_PAGE, $offset=0)
    {
        // Prepare the statement and execute it
        $sql = "SELECT
					MATCH (`body`) AGAINST (?) AS Relevance,
					id, page, title, subhead, body, img, imgcap, data1, data2,
                    data3, data4, data5, data6, data7, data8, author, created
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE title LIKE ?
                OR MATCH (`body`) AGAINST (? IN BOOLEAN MODE)
				ORDER  BY Relevance DESC
				LIMIT $offset, $limit";
		try
        {
            $query = htmlentities($search, ENT_QUOTES);
            $keys = explode(' ', $query);
            $key_search = NULL;
            foreach ( $keys as $key )
            {
                $key_search .= empty($key_search) ? "+$key" : " +$key";
            }
            $like = "%$query%";
            $stmt = $this->mysqli->prepare($sql);
            if ( !is_object($stmt) )
            {
                throw new Exception($this->mysqli->error);
            }
            $stmt->bind_param("sss", $query, $like, $key_search);
            return $this->loadEntryArray($stmt, TRUE);
        }
        catch ( Exception $e )
        {
            FB::log($this->mysqli->error, "MySQLi Error");
            die ( "Search Error: " . $e->getMessage() );
        }
    }

    /**
     * Retrieves all values for the given page from the database
     *
     * @param int $offset
     * @param int $limit
     * @return array    A multi-dimensional array of entries
     */
    protected function getAllEntries($limit=10, $offset=0, $orderby="created DESC")
    {
        $entries = array();

        /*
         * Prepare the statement and execute it
         */
        $sql = "SELECT
                id, page, title, subhead, body, img, imgcap, data1, data2,
                data3, data4, data5, data6, data7, data8, author, created
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
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

    private function loadEntryArray($stmt, $search=FALSE)
    {
        $stmt->execute();
        if ( $search===TRUE )
        {
            $stmt->bind_result($rel, $id, $page, $title, $subhead, $body, $img,
                    $imgcap, $data1, $data2, $data3, $data4, $data5, $data6,
                    $data7, $data8, $author, $created);
        }
        else
        {
            $stmt->bind_result($id, $page, $title, $subhead, $body, $img,
                    $imgcap, $data1, $data2, $data3, $data4, $data5, $data6,
                    $data7, $data8, $author, $created);
        }

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
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
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

    protected function getEntryOrder($id)
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

    protected function getEntryCountByCategory()
    {
        $param1 = $page = empty($this->url0) ? 'blog' : $this->url0;
        $url1 = empty($this->url1) ? 'category' : $this->url1;
        $param2 = empty($this->url2) ? 'recent' : $this->url2;
        $param2 = $param2!='recent' ? '%'.str_replace('-', ' ', $param2).'%' : '%';
        $link = "$url1/$param2";
        $num = empty($this->url3) ? 1 : $this->url3;
        $sql = "SELECT
                    COUNT(title) AS theCount
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE page=?
                AND data2 LIKE ?";
        try
        {
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param("ss", $param1, $param2);
            $stmt->execute();
            $stmt->bind_result($c);
            $stmt->fetch();
            $stmt->close();
        }
        catch ( Exception $e )
        {
            FB::log($e->getMessage());
            return 1;
        }
        return $c;
    }

    protected function getEntryCountBySearch($search)
    {
        $query = htmlentities($search, ENT_QUOTES);
        $keys = explode(' ', $query);
        $param2 = NULL;
        foreach ( $keys as $key )
        {
            $param2 .= empty($param2) ? "+$key" : " +$key";
        }
        $param1 = "%$query%";

        $sql = "SELECT
                    COUNT(title) AS theCount
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE title LIKE ?
                OR MATCH (`body`) AGAINST (? IN BOOLEAN MODE)";
        try
        {
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param("ss", $param1, $param2);
            $stmt->execute();
            $stmt->bind_result($c);
            $stmt->fetch();
            $stmt->close();
        }
        catch ( Exception $e )
        {
            FB::log($e->getMessage());
            return 1;
        }
        return $c;
    }

    protected function paginateEntries($preview=BLOG_PREVIEW_NUM)
    {
        if ( $this->url0=="search" )
        {
            $page = 'search';
            $link = $this->url1;
            $num = empty($this->url2) ? 1 : $this->url2;
            $c = $this->getEntryCountBySearch($link);
        }
        else
        {
            $param1 = $page = empty($this->url0) ? 'blog' : $this->url0;
            $url1 = empty($this->url1) ? 'category' : $this->url1;
            $url2 = empty($this->url2) ? 'recent' : $this->url2;
            $link = "$url1/$url2";
            $num = empty($this->url3) ? 1 : $this->url3;
            $c = $this->getEntryCountByCategory();
        }

        $span = 6; // How many pages shown adjacent to current page

        $pagination = "<ul id=\"pagination\">";

        /*
         * Determine minimum and maximum page numbers
         */
        $pages = ceil($c/$preview);

        $prev_page = $num-1;
        if($num==1)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "
                <li>
                    <a href=\"/$page/$link/1/\">&#171;</a>
                </li>
                <li>
                    <a href=\"/$page/$link/$prev_page/\">&#139;</a>
                </li>";
        }

        $mod = ($span>$num) ? $span-$num : 0;
        $max_mod = ($num+$span>$pages) ? $span-($pages-$num) : 0;
        $max = ($num+$span<=$pages) ? $num+$span+$mod : $pages;
        $max_num = ($max>$pages) ? $pages : $max;
        $min = ($max_num>$span*2) ? $num-$span-$max_mod : 1;
        $min_num = ($min<1) ? 1 : $min;

        for($i=$min_num; $i<=$max_num; ++$i)
        {
            $sel = ($i==$num) ? ' class="selected"' : NULL;
            $pagination .= "
                <li$sel>
                    <a href=\"/$page/$link/$i/\">$i</a>
                </li>";
        }

        $next_page = $num+1;
        if($next_page>$pages)
        {
            $pagination .= "";
        }
        else
        {
            $pagination .= "
                <li>
                    <a href=\"/$page/$link/$next_page/\">&#155;</a>
                </li>
                <li>
                    <a href=\"/$page/$link/$pages/\">&#187;</a>
                </li>";
        }

        return $pagination . "
            </ul>\n";
    }

    public function reorderEntries($id, $pos, $direction)
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
     * Writes data to the database; either updates or creates an entry
     *
     * @return bool        Returns true on success or false on error
     */
    public function write()
    {
        /*
         * Initialize all variables to prevent any notices
         */
        $id = ''; $title = NULL; $subhead = NULL; $body = NULL; $imgcap = NULL;
        $data1 = NULL; $data2 = NULL; $data3 = NULL; $data4 = NULL;
        $data5 = NULL; $data6 = NULL; $data7 = NULL; $data8 = NULL;

        /*
         * Loop through the POST array and define all variables
         */
        foreach ( $_POST as $key => $val )
        {
            if ( $key=="body" )
            {
                $$key = $val;
            }
            else
            {
                $$key = htmlentities($val, ENT_QUOTES);
            }
        }

        /*
         * If a value wasn't explicity passed for data6, save a URL version of
         * the title
         */
        if ( !isset($_POST['data6']) )
        {
            $data6 = UTILITIES::makeUrl($title);
        } else { $data6 = $_POST['data6']; }

        /*
         * Processes the image and returns the path, or sets the variable to
         * NULL if no image was uploaded
         */
        $img = isset($_FILES['img']) ? $this->checkIMG($_FILES['img']) : NULL;
        if ( $img===false )
        {
            $img = isset($_POST['stored_img']) ? $_POST['stored_img'] : NULL;
        }

        /*
         * PDF uploads go through the data8 field. If the $_FILES superglobal
         * isn't set, handle the input as a string. Otherwise, process as a PDF
         */
        if ( isset($_FILES['data8']) && $_FILES['data8']['size']>0 )
        {
            $data8check = $this->uploadPDF($_FILES['data8'],$title);
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
        if ( $id )
        {
            $sql = "UPDATE `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                    SET
                        title=?, subhead=?, body=?, img=?, imgcap=?,
                        data1=?, data2=?, data3=?, data4=?,
                        data5=?, data6=?, data7=?, data8=?
                    WHERE id=?
                    LIMIT 1";
            $stmt = $this->mysqli->prepare($sql);
            $stmt->bind_param("sssssssssssssi",$title, $subhead, $body, $img,
                    $imgcap, $data1, $data2, $data3, $data4, $data5, $data6,
                    $data7, $data8, $id);
        }

        /*
         * Otherwise, save a new entry
         */
        else {
            $sql = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                        (page, title, subhead, body, img, imgcap,
                        data1, data2, data3, data4, data5, data6, data7, data8,
                        author, created)
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
     * @return bool        Returns TRUE on success, FALSE on failure
     */
    public function delete($id)
    {
        $sql = "DELETE FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr` WHERE id=? LIMIT 1";
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
