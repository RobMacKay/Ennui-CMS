<?php
/*
Copyright (c) 2009 Ronnie Garcia, Travis Nickels

This file is part of Uploadify v1.6.2

Permission is hereby granted, free of charge, to any person obtaining a copy
of Uploadify and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

UPLOADIFY IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

if (!empty($_FILES)) {
	$tempFile = $_FILES['Filedata']['tmp_name'];
	$targetPath = $_SERVER['DOCUMENT_ROOT'] . SERVER_PATH . $_GET['folder'] . '/';
	if ( !$ext=getExtension($_FILES['Filedata']['type']) )
		echo 'Uploaded file is not an image. Type: '.$_FILES['Filedata']['type']."<br />\nExtension: ".$ext;

	$imgnum = getNumImages($targetPath);
	$filename = $imgnum.'_'.$imgnum.'_'.time().$ext;
	$targetFile =  str_replace('//','/',$targetPath) . $filename;
	
	// Makes the directory if it doesn't exist
	if(!is_dir($targetPath)&&strlen($targetPath)>0)
	{
		mkdir(str_replace('//','/',$targetPath), 0755, TRUE);
	}
	
	move_uploaded_file($tempFile,$targetFile);
}
echo $targetFile;

function getExtension($type)
{
	$extArr = array(
			'image/gif' => '.gif',
			'image/jpeg' => '.jpg',
			'image/pjpeg' => '.jpg',
			'image/png' => '.png',
			'application/octet-stream' => '.jpg'
		);

	return isset($extArr[$type]) ? $extArr[$type] : FALSE;
}

function getNumImages($dir)
{
	$i = 0;
	if(is_dir($dir))
	{
		if($folder = opendir($dir))
		{
			while( false !== $file = readdir($folder) )
			{
				/*
				 * Verifies that the current value of $file
				 * refers to an existing file and that the 
				 * file is big enough not to throw an error.
				 */
				if( is_file($dir.$file) && filesize($dir.$file) > 11 )
				{
					++$i;
				}
			}
		}
	}
	return sprintf("%02d", $i);
}

?>