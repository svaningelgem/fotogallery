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

  </style>
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
    <a href="{$smarty.server.PHP_SELF}?album={$album|escape}&image={$file|escape}&action=download">
      <img src="{$smarty.server.PHP_SELF}?album={$album|escape}&image={$file|escape}&action=thumb" alt=''><br />
      {$file}
    </a>
  </div>
{/foreach}
</div>
</body>
</html>