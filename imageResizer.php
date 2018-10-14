<?php
/*
 * A simple script to resize all jpeg and png images in a directory to a specified width 
 * or height.
 *
 * (c) Copyright 2014-18, Irwin Associates and Graham R Irwin - www.irwinassociates.eu
 * Last updated:
 * 14 Oct 2018 - rotate image if necessary
 *  4 Apr 2016 - minor changes; released on github
 *  9 Jun 2015 - minor correction
 * 19 Apr 2015 - UI and support for png files added
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software, and to permit 
 * persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or 
 * substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Irwin Associates image resizer</title>
<style>
body {
	font: .83em sans-serif;
	max-width: 60em;
}
ul {
	padding-left: 1.5em;
}
label {
	float: left;
	width: 8em;
}
input {
	display: block;
	font: inherit;
	margin-bottom: .5em;
}
input[type="text"],
input[type="number"] {
	border: solid 1px #a0a0a0;
	box-shadow: 2px 2px 2px #c2c2c2;
	padding: 2px 3px;
	background: #f0fdff;
}
input[type="number"] {
	width: 5em;
}
</style>
</head>
<body>
<h1>Irwin Associates Web Design</h1>
<h2>Image Resizer</h2>
<?php

function resizer($fileName, $maxWidth, $maxHeight, $fixedWidth, $fixedHeight, $oldDir, $newDir, $quality) {

  $file = $oldDir.'/'.$fileName;
  $fileDest = $newDir.'/'.$fileName;
  list($width, $height) = getimagesize($file);

  if ( $fixedWidth ) {
    $newWidth  = $fixedWidth;
    $newHeight = ($newWidth / $width) * $height;

  } elseif ( $fixedHeight ) {
    $newHeight = $fixedHeight;
    $newWidth  = ($newHeight / $height) * $width;

  } elseif ( $width < $height ) {			// image is portrait
    $newHeight = $maxHeight;
    $newWidth  = ($newHeight / $height) * $width;

  } elseif ( $width > $height ) {			// image is landscape
    $newWidth  = $maxWidth;
    $newHeight = ($newWidth / $width) * $height;

  } else {								          	// image is square
    $newWidth  = $maxHeight;
    $newHeight = $maxHeight;
  }

  $extn = strtolower(pathinfo($file, PATHINFO_EXTENSION));

  // it's a jpeg
  if ( $extn == 'jpg' or $extn == 'jpeg' ) {
    $imageSrc = imagecreatefromjpeg($file);
    // rotate image if necessary
    $exif = exif_read_data($file);
    if ( isset($exif['Orientation']) ) {
      switch ( $exif['Orientation'] ) {
        case 3:
          $imageSrc = imagerotate($imageSrc, 180, 0);
          break;
        case 6:
          $imageSrc = imagerotate($imageSrc, -90, 0);
          list($height, $width) = array($width, $height);
          list($newHeight, $newWidth) = array($newWidth, $newHeight);
          break;
        case 8:
          $imageSrc = imagerotate($imageSrc, 90, 0);
          list($height, $width) = array($width, $height);
          list($newHeight, $newWidth) = array($newWidth, $newHeight);
          break;
      }
    }
    $imageDest = imagecreatetruecolor($newWidth, $newHeight);
    if ( imagecopyresampled($imageDest, $imageSrc, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height) ) {
      imagejpeg($imageDest, $fileDest, $quality);
      imagedestroy($imageSrc);
      imagedestroy($imageDest);
      return true;
    }
    return false;
  }

  // it's a png
  if ( $extn == 'png' ) {
    $imageSrc  = imagecreatefrompng($file);
    $imageDest = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($imageDest, false);
    imagesavealpha($imageDest, true);
    if ( imagecopyresampled($imageDest, $imageSrc, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height) ) {
      imagepng($imageDest, $fileDest, ($quality / 10) - 1);
      imagedestroy($imageSrc);
      imagedestroy($imageDest);
      return true;
    }
    return false;
  }

}


// set as needed (depending on the number and size of photos 60 should usually suffice)
set_time_limit(60);

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) :
  $maxWidth    = $_POST['maxWidth'];
  $maxHeight   = $_POST['maxHeight'];
  $fixedWidth  = $_POST['fixedWidth'];
  $fixedHeight = $_POST['fixedHeight'];
  $oldDir      = $_POST['oldDir'];
  $newDir      = $_POST['newDir'];
  $quality     = $_POST['quality'];

  // create destination directory if it doesn't exist
  if ( !file_exists($newDir) )
    mkdir($newDir);

  // check source directory exists, open it and get all files
  if ( !file_exists($oldDir) )
    die('Source directory does not exist.');
  $folder = opendir($oldDir);
  while ( $file = readdir($folder) ) {
    if ( $file[0] != '.' && $file[0] != '..' )
      $files[$file] = $file;
  }
  if ( ( $filess = @scandir($oldDir) ) && count($filess) <= 2 )
    die('Source directory is empty.');

  echo '<p>Settings: fixed width ', $fixedWidth, ', fixed height ', $fixedHeight, ', max width ', $maxWidth, ', max height ', $maxHeight, ', quality ', $quality, '%</p>', PHP_EOL;
  echo '<ul>', PHP_EOL;

  // process each file
  foreach ( $files as $key => $value ) {
    $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
    if ( $ext == 'jpg' or $ext == 'jpeg' or $ext == 'png' ) {
      if ( resizer($key, $maxWidth, $maxHeight, $fixedWidth, $fixedHeight, $oldDir, $newDir, $quality) )
        echo '<li>Resized image: ', $key, '</li>', PHP_EOL;
      else
        echo '<li>** Failed to resize image: ', $key, ' **</li>', PHP_EOL;
    } else {
      echo '<li>** ', $key, ' is not a jpeg or png **</li>', PHP_EOL;
    }
  }
  echo '</ul>', PHP_EOL;

  closedir($folder);
  echo '<p>*** Finished ***</p>', PHP_EOL;
  echo '<p><a href="', htmlspecialchars($_SERVER['PHP_SELF']), '">Resize more</a></p>', PHP_EOL;

else :
?>
<p>This script will resize all jpeg and png images in the source directory to a specified width or height. The source and destination directories will usually be subdirectories of the directory containing the script. The destination directory will be created if it doesn’t exist. You can specify the names of these directories as well as other parameters:</p>
<form id="form1" name="form1" method="post">
  <label for="maxWidth">Max width</label>
  <input type="number" name="maxWidth" id="maxWidth" value="600">
  <label for="maxHeight">Max height</label>
  <input type="number" name="maxHeight" id="maxHeight" value="600" size="6">
  <label for="fixedWidth">Fixed width</label>
  <input type="number" name="fixedWidth" id="fixedWidth" value="0" size="6">
  <label for="fixedHeight">Fixed height</label>
  <input type="number" name="fixedHeight" id="fixedHeight" value="0" size="6">
  <label for="oldDir">Source directory</label>
  <input type="text" name="oldDir" id="oldDir" value="originals">
  <label for="newDir">Dest’n directory</label>
  <input type="text" name="newDir" id="newDir" value="images">
  <label for="quality">Quality %</label>
  <input type="number" name="quality" id="quality" min="10" max="100" value="80" size="6">
  <input type="submit" name="submit" id="submit" value="Resize">
</form>
<p>Once you have pressed Resize please be patient as it may take some minutes to resize your images.</p>
<?php
endif;
?>
</body>
</html>
