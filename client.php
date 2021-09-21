<?php


$str = file_get_contents("tool.php");

echo $str;
echo "\r\n";

$str = file_get_contents("client_index.php");
$str = str_replace("<?php", "", $str);

echo $str;