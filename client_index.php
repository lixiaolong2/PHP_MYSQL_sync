<?php

// 入口文件
ob_start();
define('APP_ROOT', str_replace("\\", "/", dirname(__FILE__)));
define('BASE_ROOT', str_replace("\\", "/", dirname(APP_ROOT)));
date_default_timezone_set("PRC");

$db_config = array(
    'user'     => 'root',
    'password' => 'root',
    'host'     => '127.0.0.1',
    'dbname'   => 'rdc3',
    'charset'  => 'utf8'
);

DB::$config = $db_config;

add_file_log("开始同步！");
echo '开始同步！<br>';

$str = file_get_contents("http://xxx.xxx/sync/server.php");

if ($str === FALSE)
{
	add_file_log("同步URL访问失败！");
	echo '同步URL访问失败！<br>';
	exit;
}

$sync = json_decode($str, true);
if (count($sync['db_list']) == 0)
{
	add_file_log("数据无更新！");
	echo '数据无更新！<br>';
	exit;
}

foreach ($sync['db_list'] as $k => $r)
{
	if (DB::tableExist($k) === FALSE)
	{
		add_file_log("表 " . $k . " 不存在！");
		echo "表 " . $k . " 不存在！<br>";
		continue;
	}

	echo "更新表 " . $k . " " . count($r['list']) . "条数据！<br>";
	echo "删除表 " . $k . " " . count($r['del_id_list']) . "条数据！<br>";
	
	if (count($r['id_list']) > 0)
	{
		$old_list = DB::query("SELECT id FROM `$k` WHERE id IN (" . implode(',', $r['id_list']) . ")");
		$old_id_list = array();
		foreach ($old_list as $rr)
		{
			$old_id_list[] = $rr['id'];
		}
		foreach ($r['list'] as $row)
		{
			if (in_array($row['id'], $old_id_list))
			{
				DB::update($k, $row);
			}
			else
			{
				DB::insert($k, $row);
			}
		}
	}
	
	if (count($r['del_id_list']) > 0)
	{
		DB::query("DELETE FROM `$k` WHERE id IN (" . implode(',', $r['del_id_list']) . ")");
	}
}

add_file_log("同步成功！");
echo '同步成功，同步 ' . count($sync['db_list']) . ' 张表<br>';

