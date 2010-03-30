<?php

/*
 * This class depends on the ImageGallery class
 */
include_once CMS_PATH.'/core/class.imagegallery.inc.php';

/**
 * Methods to display and edit a page with a contact form
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to the MIT License, available
 * at http://www.opensource.org/licenses/mit-license.html
 *
 * @author     Jason Lengstorf <jason.lengstorf@ennuidesign.com>
 * @copyright  2010 Ennui Design
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
class Gallery extends Multi
{

    public function displayAdmin($id)
    {
        $data7 = $this->getEntryOrder($id);
        $d7 = isset($data7) ? $data7 : $this->countEntries($this->url0)+1;

        $form = $this->createForm('write', $id);

        $markup = $form['start'];
        $markup .= $this->createFormInput('title', 'Album Title', $id);
        $markup .= $this->createFormInput('body', 'Additional Info', $id);
        $markup .= '<input type="hidden" name="data7" value="'.$d7.'" />';
        $markup .= $form['end'];

        return $markup;
    }

    public function displayGalleryAdmin($id)
    {
        $image_disp = "<div id=\"admin_gal\">";
        $dir = GAL_SAVE_DIR . $this->url0 .  $id . '/';
        $images = $this->getGalleryImages($id, TRUE);
        $i = 0;
        foreach($images as $img)
        {
            $thumb = $dir.'thumbs/'.$img;
            $imgURL = $dir.$img;
            $imgID = substr($img, 3);
            $gal = new ImageGallery;
            $gal->dir = $dir;
            $gal->imgCap_album = $id;
            $title = $gal->getImageCaption($img);
            $image_disp .= "
    <div id=\"image_$i\">
        <img src=\"/$thumb\" alt=\"Gallery Image\" title=\"$title\" />
        <a href=\"javascript:var image_cap=prompt('Enter a caption for this photo');addPhotoCaption('$this->url0', '$id', '$imgID',image_cap);\">add a caption</a><br />
        <a href=\"javascript:deletePhoto('$this->url0', '$id', '$imgURL');\"
            onclick=\"return confirm('Are you sure you want to delete this entry?\\n\\nClick OK to continue.');\">delete this image</a>
    </div>";
            ++$i;
        }
        $image_disp .= "</div>";

        return "\n\n<!-- BEGIN FORM DISPLAY -->
<form action=\"/_engine/Uploadify.inc.php\"
        method=\"post\"
        enctype=\"multipart/form-data\"
        class=\"ennui_form\">
    <fieldset class=\"ennui_form\">
        <h2>Add Photos</h2>
        <div id=\"fileUpload\">Loading...</div>
        <a href=\"javascript:window.location.reload()\">Back to This Entry</a>
    </fieldset>
</form>$image_disp
<!-- END FORM DISPLAY -->\n";
    }

    protected function displayPreview($entries)
    {
        $id = (isset($entries[0]['id'])) ? $entries[0]['id'] : NULL;
        $entry = $this->admin_general_options($this->url0, $id, false);

        if(isset($entries[0]['title'])) {
            // Number of results
            $n = count($entries);
            $entry_array = array(); // Initialize the variable to avoid a notice
            foreach ( $entries as $e )
            {
                // Entry options for the admin, if logged in
                $e['admin'] = $this->admin_gallery_options($this->url0, $e['id'], $n, $e['data7']);

                /*
                 * Load the photos associated with this entry as HTML
                 */
                $e['gallery'] = $this->getGalleryImages($e['id'], FALSE, $e['title']);

                $entry_array[] = $e;
            }

            /*
             * Load the template into a variable
             */
            $template = UTILITIES::loadTemplate($this->url0.'-preview.inc');

            $entry .= UTILITIES::parseTemplate($entry_array, $template);
        } else {
            $entry .= "
                    <h2> No Entry Found </h2>
                    <p>
                        Log in to create this entry.
                    </p>";
        }

        return $entry;
    }

    private function getGalleryImages($id, $edit=FALSE, $caption=NULL, $category=NULL)
    {
        try {
            $gal = new ImageGallery();
            $gal->max_dims = array(700, 470); // Maximum dimensions of the images (w, h)
            $gal->dir = GAL_SAVE_DIR . $this->url0 .  $id . '/';
            $gal->imgCap_album = $id;
            $gal->imgTitle = $caption;
            $gal->relAttr = ' class="gal-disp"';
            $gal->getImages(); // Read all images out of a folder
            if($gal->checkSize()===FALSE) // Make sure the images are the right size
            {
                $gal->preview = TRUE;
                $gal->max_dims = array(233, 136); // Maximum dimensions of the images (w, h)
                $gal->checkSize(); // Make sure the images are the right size
            }
            $gal->makeThumb(IMG_THUMB_SIZE); // Creates thumb if they don't exist

            if($edit)
            {
                return $gal->getImagesAsArray();
            }
            else
            {
                return ($gal->getNumImages()>0)? $gal : NULL;
            }
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }

    public function reorderGallery($images, $id)
    {
        $gal = new ImageGallery();
        $gal->dir = GAL_SAVE_DIR . '/' . $this->url0.$id."/";
        $gal->getImages();
        $img = $gal->getImagesAsArray();
        sort($img);
        array_merge(array(), $img);

        for($i=0, $c=count($images); $i<$c; ++$i)
        {
            $n = $images[$i];
            if(is_file($gal->dir.$img[$n]))
            {
                $imgID = substr($img[$n], 3);
                rename($gal->dir.$img[$n], $gal->dir.sprintf("%02d", $i).'_'.$imgID);
                if(is_file($gal->dir."preview/".$img[$n]))
                {
                    rename($gal->dir."preview/".$img[$n], $gal->dir."preview/".sprintf("%02d", $i).'_'.$imgID);
                }
                if(is_file($gal->dir."thumbs/".$img[$n]))
                {
                    rename($gal->dir."thumbs/".$img[$n], $gal->dir."thumbs/".sprintf("%02d", $i).'_'.$imgID);
                }
            }
        }
    }

    public function displayFeatured()
    {
        $markup = "\n<ul id=\"featured-gallery\">";

        // Select the featured gallery titles
        $sql = "SELECT id, page
                FROM `".DB_NAME."`.`".DB_PREFIX."entryMgr`
                WHERE data7='featured'";
        $stmt = $this->mysqli->prepare($sql);
        $stmt->execute();
        $stmt->bind_result($id, $page);

        // Create a new ImageGallery object
        $gal = new ImageGallery();

        // Loop through the featured cars
        while($stmt->fetch())
        {
            $gal->dir = "img/gallery/$page$id/preview/";
            $gal->getImages();
            $img = $gal->getFirstImage();
            $markup .= "\n\t<li><a href=\"/$page/$id\"><img src=\"/$img\" alt=\"Featured Customization\" /></a></li>";
        }
        return $markup . "\n</ul>\n";
    }

}
