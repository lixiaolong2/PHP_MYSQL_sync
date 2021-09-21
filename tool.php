<?php

/*
* 循环检测并创建文件夹
*/
function create_dir($path)
{
	if (!file_exists($path))
	{
		self::create_dir(dirname($path));
		mkdir($path, 0777);
	}
}


function add_file_log($content, $type = 'log')
{
	$file = APP_ROOT . "/log/" . $type . "_" . date("Y-m-d") . ".txt";

	$str = date('Y-m-d H:i:s ') . $content . "\r\n";
	file_put_contents($file, $str, FILE_APPEND);
}

class DB
{
	//pdo对象
	public $con = NULL;
	public static $config = NULL;

	function DB()
	{
		$db = DB::$config;
		
		$port = 3306;
		if (isset($db['port']))
		{
			$port = $db['port'];
		}
		
		$str_con = "mysql:host=" . $db['host'] . ";port=" . $port . ";dbname=" . $db['dbname'];
		
		$this->con = new PDO($str_con, $db['user'], $db['password'], array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES `utf8`',
			PDO::ATTR_PERSISTENT => TRUE,
		));

		$this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->con->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
	}

	/*
	 * 执行SQL操作
	 */
	function runSql($sql, $para = NULL)
	{
		$sql = trim($sql);

		$arr = explode(' ', $sql);
		$sqlType = strtoupper($arr[0]);

		if (strpos($sql, 'INTO OUTFILE') !== FALSE)
		{
			$sqlType = 'OUTFILE';
		}

		try
		{
			$cmd = $this->con->prepare($sql);
			if ($para == NULL)
			{
				$cmd->execute();
			}
			else
			{
				$cmd->execute($para);
			}
		}
		catch (Exception $ex)
		{
			echo "SQL ERROR!";
			add_file_log("SQL ERROR! " . $sql);
			die;
		}

		$return = NULL;
		if($sqlType == "SELECT" || $sqlType == "SHOW")
		{
			$return = $cmd->fetchAll(PDO::FETCH_ASSOC);
		}
		else if($sqlType == "INSERT")
		{
			$return = $this->con->lastInsertId();
		}
		else
		{
			$return = $cmd->rowCount();
		}

		return $return;
	}

	/*
	 * 执行SQL操作 返回列表
	 */
	public static function query($sql, $para = NULL)
	{
		$db = new DB();
		$res = $db->runSql($sql, $para);
		$db = NULL;

		return $res;
	}

	/*
	 * 执行SQL操作 返回一个对象
	*/
	public static function queryObject($sql, $para = NULL)
	{
		$db = new DB();
		$list = $db->runSql($sql, $para);
		$db = NULL;

		if ($list === false)
		{
			return null;
		}

		return count($list) > 0 ? $list[0] : null;
	}

	/*
	* 判断数据库表是否存在
	*/
	public static function tableExist($table)
	{
		$db = new DB();
		$list = $db->runSql("show tables like '" . $table . "'");
		$db = NULL;

		return count($list) > 0;
	}

	/*
	 * 通过ID获取
	 */
	public static function getById($table, $id)
	{
		return DB::queryObject("SELECT * FROM $table WHERE `id` = :id", array('id' => $id));
	}

	/*
	 * 通过ID删除
	 */
	public static function delById($table, $id)
	{
		return DB::query("DELETE FROM $table WHERE `id` = :id", array('id' => $id));
	}

	/*
	 * 添加
	 */
	public static function insert($table, $para)
	{
		$sql_para = array();

		foreach ($para as $k => $v)
		{
			$sql_para[] = $k;
		}

		$res = DB::query("INSERT INTO `" . $table . "` (`" . implode("`, `", $sql_para) . "`)
				VALUES(:" . implode(", :", $sql_para) . ")", $para);

		return $res;
	}

	/*
	 * 更新
	 */
	public static function update($table, $para)
	{
		$sql_para = array();

		foreach ($para as $k => $v)
		{
			if ($k == 'id')
			{
				continue;
			}
			$sql_para[] = '`' . $k . '` = :' . $k;
		}

		$res = DB::query("UPDATE `" . $table . "` SET " . implode(", ", $sql_para) . " WHERE `id` = :id", $para);

		return $res;
	}

	/*
	 * 获取个数
	 */
	public static function getCount($sql, $para = NULL)
	{
		$item = DB::queryObject($sql, $para);

		$count = 0;
		if ($item != NULL)
		{
			$count = array_values($item);
			$count = intval($count[0]);
		}

		return $count;
	}
}