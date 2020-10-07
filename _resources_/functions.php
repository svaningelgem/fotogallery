<?php
require_once __DIR__ . '/smarty/Smarty.class.php';


function make_absolute($path) {
  $new_path = explode(DIRECTORY_SEPARATOR, str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));

  $is_absolute = false;
  if ( stripos(PHP_OS, 'win') !== false ) {  # Windows?
    $is_absolute = preg_match('~^[a-z]:$~i', $new_path[0]) > 0;
  }
  else {
    $is_absolute = $new_path[0] == '';
  }

  if ( $is_absolute ) {
    return implode(DIRECTORY_SEPARATOR, $new_path);
  }

  $tmp = array_merge(explode(DIRECTORY_SEPARATOR, getcwd()), $new_path);

  $new_path = [];
  foreach( $tmp as $part ) {
    if ( in_array($part, ['', '.']) ) continue;

    if ( $part == '..' ) {
      @array_pop($new_path);
    }
    else {
      $new_path[] = $part;
    }
  }

  return DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $new_path);
}

# https://stackoverflow.com/questions/2090723/how-to-get-the-relative-directory-no-matter-from-where-its-included-in-php
function make_relative($path, $base) {
  $path = make_absolute($path);
  $base = make_absolute($base);

  $separator = DIRECTORY_SEPARATOR;
  
  $from   = str_replace(['/', '\\'], $separator, $base);
  $to     = str_replace(['/', '\\'], $separator, $path);

  $arFrom = explode($separator, rtrim($from, $separator));
  $arTo = explode($separator, rtrim($to, $separator));
  while(count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0]))
  {
      array_shift($arFrom);
      array_shift($arTo);
  }

  return str_pad('', count($arFrom) * 3, '..'.$separator).implode($separator, $arTo);
}


function get_smarty() {
  global $tmp_folder;

  $cache = make_absolute($tmp_folder).'/smarty_cache/';
  @mkdir($cache, 0755, true);
  
  $compile = make_absolute($tmp_folder).'/smarty_compile/';
  @mkdir($compile, 0755, true);
  
  $smarty = new Smarty();
  $smarty->setTemplateDir(__DIR__.'/templates/');
  $smarty->setCacheDir($cache);
  $smarty->setCompileDir($compile);

  return $smarty;
}


function get_thumb_name($src) {
  global $thumb_width, $images_root_fs, $thumbs_folder_fs;

  #var_dump($thumbs_folder_fs, $src, $images_root_fs, make_relative($src, $images_root_fs));
  #die();
  $thumb = $thumbs_folder_fs . make_relative($src, $images_root_fs);
  # Add the width now
  return substr($thumb, 0, strrpos($thumb, '.')) . ".{$thumb_width}.png";
}


function swap(&$a, &$b) {
  $tmp = $b;
  $b = $a;
  $a = $tmp;
}


function create_thumb($src) {
  global $thumb_width;

  $thumb = get_thumb_name($src);
  if ( file_exists($thumb) ) return $thumb;

  @mkdir(dirname($thumb), 0755, true);

  $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
  if ( $ext == 'jpeg' ) $ext = 'jpg';

  $source_image = null;

  switch($ext) {
  case 'bmp':   $source_image = imagecreatefrombmp($src);  break;
  case 'gd2':   $source_image = imagecreatefromgd2($src);  break;
  case 'gd':    $source_image = imagecreatefromgd($src);   break;
  case 'gif':   $source_image = imagecreatefromgif($src);  break;
  case 'jpg':   $source_image = imagecreatefromjpeg($src); break;
  case 'png':   $source_image = imagecreatefrompng($src);  break;
  case 'wbmp':  $source_image = imagecreatefromwbmp($src); break;
  case 'webp':  $source_image = imagecreatefromwebp($src); break;
  case 'xbm':   $source_image = imagecreatefromxbm($src);  break;
  case 'xpm':   $source_image = imagecreatefromxpm($src);  break;
  }  

  if ( !$source_image ) return '_resources_/no_image.png';

  $rotate = 0;
  if ( $ext == 'jpg' ) {  # Adjust orientation
    $exif = @exif_read_data($src);
    if ( !is_array($exif) ) $exif = [];

    switch ($exif['Orientation'] ?? 1) {
    case 0:
    case 1: $rotate = 0; break;
    case 3: $rotate = 180; break;
    case 6: $rotate = -90; break;
    case 8: $rotate = 90; break;
    default: throw new \Exception("Invalid Orientation flag?? --> {$exif['Orientation']} <--");
    }
  }

  $width  = imagesx($source_image);
  $height = imagesy($source_image);

  if ( in_array($rotate, [90, -90]) ) {
    swap($width, $height);
  }

  if ( $width < $thumb_width ) { # if original image is smaller don't resize it
    $thumb_width = $width;
    $thumb_height = $height;
  }
  else {
    $thumb_height = (int) (($thumb_width / $width) * $height);
  }

  if ( in_array($rotate, [90, -90]) ) {
    swap($width, $height);
    swap($thumb_width, $thumb_height);
  }

  $virtual_image = imagecreatetruecolor($thumb_width, $thumb_height);

  if ( in_array($ext, ['gif', 'png']) ) { // preserve transparency
    imagecolortransparent($virtual_image, imagecolorallocatealpha($virtual_image, 0, 0, 0, 127));
    imagealphablending($virtual_image, false);
    imagesavealpha($virtual_image, true);
  }

  imagecopyresampled($virtual_image,$source_image,0,0,0,0,$thumb_width,$thumb_height,$width,$height);

  # Non-rotated image here!
  if ( $rotate != 0 ) {
    $virtual_image = imagerotate($virtual_image, $rotate, 0);
  }

  imagejpeg($virtual_image, $thumb);
  
  imagedestroy($virtual_image); 
  imagedestroy($source_image);

  return $thumb;
}


function filter_images($img) {
  global $images, $images_folder_fs;

  $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));

  if ( !in_array($ext, $images) ) return false;

  return make_relative($img, $images_folder_fs);
}
