<?php

$table_list = array(
	'device', 
	'layer', 
	'orgnization', 
	'project', 
	'project_pile', 
	'province', 
	'reciver', 
	'section', 
	'siteunit', 
	'tech_check',
	'technology',
	'workarea',
	'workarea_analyze',
);


$str = "
DROP TRIGGER IF EXISTS `tg_{0}_insert`;
CREATE TRIGGER `tg_{0}_insert` AFTER INSERT ON `{0}`
FOR EACH ROW INSERT INTO table_change_log (`table_name`, `table_op`, `table_id`, `add_time`) VALUES('{0}', 'insert', new.id, now());

DROP TRIGGER IF EXISTS `tg_{0}_update`;
CREATE TRIGGER `tg_{0}_update` AFTER UPDATE ON `{0}`
FOR EACH ROW INSERT INTO table_change_log (`table_name`, `table_op`, `table_id`, `add_time`) VALUES('{0}', 'update', old.id, now());

DROP TRIGGER IF EXISTS `tg_{0}_delete`;
CREATE TRIGGER `tg_{0}_delete` AFTER DELETE ON `{0}`
FOR EACH ROW INSERT INTO table_change_log (`table_name`, `table_op`, `table_id`, `add_time`) VALUES('{0}', 'delete', old.id, now());
";

$sql = "";

foreach ($table_list as $r)
{
	$sql .= str_replace('{0}', $r, $str);
}

echo $sql;



