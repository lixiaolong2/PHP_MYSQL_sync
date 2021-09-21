<?php

include "tool.php";

// 入口文件
ob_start();
define('APP_ROOT', str_replace("\\", "/", dirname(__FILE__)));
define('BASE_ROOT', str_replace("\\", "/", dirname(APP_ROOT)));
date_default_timezone_set("PRC");

$db_config = array(
    'user'     => 'root',
    'password' => 'root',
    'host'     => '127.0.0.1:3308',
	'port'     => 3308,
    'dbname'   => 'rdc_3',
    'charset'  => 'utf8'
);

DB::$config = $db_config;

$sync_time_config = DB::queryObject("SELECT * FROM `sys_config` WHERE `name` = 'sync_time'");

$sync_time = $sync_time_config == NULL ? '2015-01-01' : $sync_time_config['value'];

$data_list = DB::query("
SELECT *
FROM `table_change_log` 
WHERE id IN (
	SELECT max(ID)
	FROM `table_change_log` 
  WHERE `add_time` > '" . $sync_time . "'
	GROUP BY `table_name`, `table_id` 
)
ORDER BY `table_name`, `table_id`
");

$dt_now = date('Y-m-d H:i:s');

if ($sync_time_config == NULL)
{
	DB::query("INSERT INTO `sys_config` (`name`, `value`, `add_time`, `update_time`) VALUES('sync_time', '$dt_now', NOW(), NOW())");
}
else
{
	DB::query("UPDATE `sys_config` SET `value` = '$dt_now', `update_time` = NOW() WHERE `id` = " . $sync_time_config['id']);
}

$sync = array(
	'file_list' => array(),
	'db_list' => array(),
);

$table_list = array();
foreach ($data_list as $r)
{
	$table_name = $r['table_name'];
	$table_id = $r['table_id'];
	
	if (!isset($table_list[$table_name]))
	{
		$table_list[$table_name] = array(
			'id_list' => array(),
			'del_id_list' => array(),
			'list' => array(),
		);
	}
	if ($r['table_op'] == 'insert' || $r['table_op'] == 'update')
	{
		$table_list[$table_name]['id_list'][] = $table_id;
	}
	else if ($r['table_op'] == 'delete')
	{
		$table_list[$table_name]['del_id_list'][] = $table_id;
	}
}

foreach ($table_list as $k => &$r)
{
	$r['list'] = DB::query("SELECT * FROM `" . $k . "` WHERE id IN (" . implode(',', $r['id_list']) . ")");
}
unset($r);

$sync['db_list'] = $table_list;

add_file_log('同步了 ' . count($data_list) . ' 条数据！');

echo json_encode($sync);
