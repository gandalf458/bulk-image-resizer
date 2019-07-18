<?php
/*
 * A script to resize all jpeg and png images in a directory to a specified width
 * or height.
 *
 * (c) Copyright 2014-19, Irwin Associates and Graham R Irwin - www.irwinassociates.eu
 *
 * Last updated:
 * 18 Jul 2019 - @ out exif_read_data + changes to calcSize()
 * 13 Nov 2018 - fixed a bug introduced with previous update; also converts saved file
 *               name to lowercase; some cosmetic changes and code improvements
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
  font: .91em/1.3 sans-serif;
  max-width: 50em;
}
ul {
  padding-left: 1.5em;
}
label {
  float: left;
  width: 8em;
}
input {
  font: inherit;
  margin-bottom: .5em;
}
input[type="text"],
input[type="number"] {
  padding: 1px 3px;
}
input[type="number"] {
  width: 5em;
}
input[type="submit"] {
  padding: 1px 12px;
  cursor: pointer;
}
</style>
</head>
<body>
<h1>Irwin Associates Web Design</h1>
<h2>Image Resizer</h2>
<?php

function calcSize($fw, $fh, $mw, $mh, $w, $h) {
  // fixed width, fixed height, max width, max height, image width, image height
  // nw = new width, nh = new height

  if ($fw) {
    $nw = $fw;
    $nh = ($nw / $w) * $h;
  }
  elseif ($fh) {
    $nh = $fh;
    $nw = ($nh / $h) * $w;
  }
  elseif ($w < $h) {     // image is portrait
    $nh = $mh;
    $nw = ($nh / $h) * $w;
    if ($nw > $mw) {
      $nw = $mw;
      $nh = ($nw / $w) * $h;
    }
  }
  elseif ($w > $h) {     // image is landscape
    $nw = $mw;
    $nh = ($nw / $w) * $h;
    if ($nh > $mh) {
      $nh = $mh;
      $nw = ($nh / $h) * $w;
    }
  }
  else {                 // image is square
    $nw = $mh;
    $nh = $mh;
  }
  return(array($nw, $nh));

}

function resizer($fileName, $maxWidth, $maxHeight, $fixedWidth, $fixedHeight, $oldDir, $newDir, $quality) {

  $file = $oldDir.'/'.$fileName;
  $fileDest = $newDir.'/'.strtolower($fileName);   // save with lowercase file name
  list($width, $height) = getimagesize($file);

  list($newWidth, $newHeight) = calcSize($fixedWidth, $fixedHeight, $maxWidth, $maxHeight, $width, $height);

  $extn = strtolower(pathinfo($file, PATHINFO_EXTENSION));

  // it's a jpeg
  if ($extn === 'jpg' || $extn === 'jpeg') {
    $imageSrc  = imagecreatefromjpeg($file);
    // rotate image if necessary
    $exif = @exif_read_data($file);
    if (isset($exif['Orientation'])) {
      switch ($exif['Orientation']) {
        case 3:
          $imageSrc = imagerotate($imageSrc, 180, 0);
          break;
        case 6:
          $imageSrc = imagerotate($imageSrc, -90, 0);
          list($height, $width) = array($width, $height);  // swap width and height
          list($newWidth, $newHeight) = calcSize($fixedWidth, $fixedHeight, $maxWidth, $maxHeight, $width, $height);  // recalculate the size
          break;
        case 8:
          $imageSrc = imagerotate($imageSrc, 90, 0);
          list($height, $width) = array($width, $height);  // swap width and height
          list($newWidth, $newHeight) = calcSize($fixedWidth, $fixedHeight, $maxWidth, $maxHeight, $width, $height);  // recalculate the size
          break;
      }
    }
    $imageDest = imagecreatetruecolor($newWidth, $newHeight);
    if (imagecopyresampled($imageDest, $imageSrc, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
      imagejpeg($imageDest, $fileDest, $quality);
      imagedestroy($imageSrc);
      imagedestroy($imageDest);
      return true;
    }
    return false;
  }

  // it's a png
  if ($extn === 'png') {
    $imageSrc  = imagecreatefrompng($file);
    $imageDest = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($imageDest, false);
    imagesavealpha($imageDest, true);
    if (imagecopyresampled($imageDest, $imageSrc, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
      imagepng($imageDest, $fileDest, ($quality / 10) - 1);
      imagedestroy($imageSrc);
      imagedestroy($imageDest);
      return true;
    }
    return false;
  }

}


// set as needed depending on the number and size of photos (60 should usually suffice)
set_time_limit(60);

if ($_SERVER['REQUEST_METHOD'] === 'POST') :
  $maxWidth    = (int)$_POST['maxWidth'];
  $maxHeight   = (int)$_POST['maxHeight'];
  $fixedWidth  = (int)$_POST['fixedWidth'];
  $fixedHeight = (int)$_POST['fixedHeight'];
  $oldDir      = htmlspecialchars($_POST['oldDir']);
  $newDir      = htmlspecialchars($_POST['newDir']);
  $quality     = (int)$_POST['quality'];

  // create destination directory if it doesn't exist
  if (!file_exists($newDir))
    mkdir($newDir);
  // check source directory exists
  if (!file_exists($oldDir))
    die('Source directory does not exist.');
  // get all files
  $files = scandir($oldDir);
  if (count($files) <= 2)
    die('Source directory is empty.');

  echo '<p>Settings: fixed width ', $fixedWidth, ', fixed height ', $fixedHeight, ', max width ', $maxWidth, ', max height ', $maxHeight, ', quality ', $quality, '%</p>', PHP_EOL;
  echo '<ul>', PHP_EOL;
  // process each file
  foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'png') {
        if (resizer($file, $maxWidth, $maxHeight, $fixedWidth, $fixedHeight, $oldDir, $newDir, $quality)) {
          echo '<li>Resized image: ', $file, '</li>', PHP_EOL;
        } else {
          echo '<li>** Failed to resize image: ', $file, ' **</li>', PHP_EOL;
        }
      } else {
        echo '<li>** ', $file, ' is not a jpeg or png **</li>', PHP_EOL;
      }
    }
  }
  echo '</ul>', PHP_EOL;
  echo '<p>*** Finished ***</p>', PHP_EOL;
  echo '<p><a href="', htmlspecialchars($_SERVER['PHP_SELF']), '">Resize more</a></p>', PHP_EOL;

else :
?>
<p>This script will resize all jpeg and png images in the source directory to a specified fixed or maximum width or height. The source and destination directories should be subdirectories of the directory containing the script. The destination directory will be created if it doesn’t exist.</p>
<form id="form1" name="form1" method="post">
  <div>
    <label for="maxWidth">Max width</label>
    <input type="number" name="maxWidth" id="maxWidth" value="600">
  </div>
  <div>
    <label for="maxHeight">Max height</label>
    <input type="number" name="maxHeight" id="maxHeight" value="600">
  </div>
  <div>
    <label for="fixedWidth">Fixed width</label>
    <input type="number" name="fixedWidth" id="fixedWidth" value="0">
  </div>
  <div>
    <label for="fixedHeight">Fixed height</label>
    <input type="number" name="fixedHeight" id="fixedHeight" value="0">
  </div>
  <div>
    <label for="oldDir">Source directory</label>
    <input type="text" name="oldDir" id="oldDir" value="originals">
  </div>
  <div>
    <label for="newDir">Dest’n directory</label>
    <input type="text" name="newDir" id="newDir" value="images">
  </div>
  <div>
    <label for="quality">Quality %</label>
    <input type="number" name="quality" id="quality" min="10" max="100" value="80">
  </div>
  <div>
    <input type="submit" name="submit" id="submit" value="Resize">
  </div>
</form>
<p>Once you have pressed Resize please be patient as it may take some minutes to resize your images depending on how many there are.</p>
<?php
endif;
?>
</body>
</html>
