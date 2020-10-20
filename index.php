<?php
  require_once '_resources_/functions.php';

  chdir( __DIR__ );
  
  # Assumptions: I assumed you just dropped this in your web dir
  # Folder structure:
  # index.php
  # photos/ <-- path to the images
  # _tmp_/ <-- writable by the server
  # _lib_/ Smarty library for displaying the HTML
  # _resources_/ <-- writable by the server

  ###################################### CONFIG
  $images = ['bmp', 'gd2', 'gd', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'wbmp', 'xpm', 'xbm'];
  $web_root = '.';
  $tmp_folder = '_tmp_';  # Can be anywhere
  $thumbs_folder = '_tmp_/thumbs';  # Should be reachable by the webserver (and writable by the webserver)
  $images_folder = '/opt/photos';  # Where are the images located? Can be anywhere
  $thumb_width = '256';  # In pixels (width), height is adjusted automatically
  $preview_width = '2048';  # In pixels (width), height is adjusted automatically
  ###################################### CONFIG

  $images = array_map('strtolower', $images);

  $root = __DIR__;

  $current_album = preg_replace('~(^../|/..$|/../)~', '', str_replace('\\', '/', $_REQUEST['album'] ?? ''));
  $current_image = str_replace(['\\', '/'], '', $_REQUEST['image'] ?? '');

  $thumbs_folder_fs  = make_absolute($tmp_folder) . '/' . ($current_album != '' ? $current_album . '/' : '');
  @mkdir($thumbs_folder_fs, 0755, true);
  $thumbs_folder_web = make_relative($thumbs_folder_fs, $web_root) . '/';

  $images_root_fs    = make_absolute($images_folder) . '/';
  $images_folder_fs  = $images_root_fs . ($current_album != '' ? $current_album . '/' : '');
  $images_folder_web = make_relative($images_folder_fs, $web_root) . '/';

  if ( !is_writable($thumbs_folder_fs) ) {
    exit("Please make {$thumbs_folder_fs} writable!");
  }

  if ( !is_readable($images_folder_fs) ) {
    exit("I can't read from {$images_folder_fs}!");
  }

  
  
  switch( $_REQUEST['action'] ?? '' ) {
  case 'thumb':
    $image = $images_folder_fs . $current_image;
    header('Content-Type: image/jpeg');
    readfile(create_thumb($image, $thumb_width));
    break;
  
  case 'preview':
    $image = $images_folder_fs . $current_image;
    header('Content-Type: image/jpeg');
    readfile(create_thumb($image, $preview_width));
    break;
  
  case 'download':
    $image = $images_folder_fs . $current_image;
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$current_image.'"');
    readfile($image);
    break;
  
  default:
    $folders = glob($images_folder_fs.'*', GLOB_ONLYDIR);
    $files = array_diff(glob($images_folder_fs.'*'), $folders);

    $smarty = get_smarty();
    $smarty->assign('album', $current_album);
    $smarty->assign('folders', array_map(function($x) { global $images_root_fs; return make_relative($x, $images_root_fs); }, $folders));
    $smarty->assign('files', array_filter(array_map('filter_images', $files)));
    $smarty->display('index.tpl');
    break;
  }
