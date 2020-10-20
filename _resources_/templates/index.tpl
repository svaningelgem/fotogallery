<!DOCTYPE html>
<html>
<head>
  <style type="text/css">
  
.thumbnail {
/*
  width: 512px;
  float: left;
*/
  max-width: 512px;;
  padding: 5px;
  display: inline-table;
}

.lb-number {
  background-color: white;
  padding: 2px;
}

  </style>
  
  <link rel="stylesheet" href="_resources_/lightbox.min.css">
  <script src="_resources_/lightbox-plus-jquery.min.js"></script>
  <script src="https://unpkg.com/imagesloaded@4/imagesloaded.pkgd.js"></script>
  
  <script>
    lightbox.option({
      'resizeDuration': 200,
      'fadeDuration': 200,
      'wrapAround': false,
      'disableScrolling': true,
      'fitImagesInViewport': true
    });

    $(document).ready(function() {
      // setTimeout(function() {
        var img = $('#lightbox img.lb-image').get(0)
        var caption = $($('#lightbox .lb-number').get(0))

        $(img).on('load', function() {
          if ( !img || !img.src || img.src.indexOf('=preview') == -1 ) {
            return;
          }

          download_url = img.src.replace('=preview', '=download');
          setTimeout(function() {
            caption.html('<a href="' + download_url + '">' + caption.text() + '</a>');
          }, 150);
        });
      // }, 1500);
    });
  </script>
</head>
<body>

<div id='Folders'>
{foreach $folders as $folder}
<a href="{$smarty.server.PHP_SELF}?album={$folder|escape}">
  <img src='_resources_/folder.png' alt='' width="25px"/> {$folder}
</a><br />
{/foreach}
</div>

<hr />
<div id='Files'>
{foreach $files as $file}
  <div class='thumbnail'>
    <a href="{$smarty.server.PHP_SELF}?album={$album|escape}&image={$file|escape}&action=preview" data-lightbox="{$album|escape}">
      <img src="{$smarty.server.PHP_SELF}?album={$album|escape}&image={$file|escape}&action=thumb" alt=''>
    </a>
    <br />
    <a href="{$smarty.server.PHP_SELF}?album={$album|escape}&image={$file|escape}&action=download">
      {$file}
    </a>  
  </div>
{/foreach}
</div>
</body>
</html>