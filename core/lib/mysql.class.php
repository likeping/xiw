<?php
/**
 * mysql数据库驱动,完成对mysql数据库的操作
 */

if (!defined('IN_FINECMS')) exit();

class mysql extends Fn_base {
	
	public static $instance;
	public $db_link;
	
	/**
	 * 构造函数
	 */
	public function __construct($params = array()) {
		//检测参数信息是否完整
		if (!$params['host'] || !$params['username'] || !$params['dbname']) Controller::halt('Mysql数据库配置文件不完整！');
		//处理数据库端口
		if ($params['port'] && $params['port'] != 3306) $params['host'] .= ':' . $params['port'];
		//实例化mysql连接ID
		$this->db_link = @mysql_connect($params['host'], $params['username'], $params['password']);
		if (!$this->db_link) {
			Controller::halt('Mysql服务器连接失败！ <br/>Error Message:' . mysql_error() . '<br/>Error Code:' . mysql_errno(), 'Warning');		
		} else {
			if (mysql_select_db($params['dbname'], $this->db_link)) {
				//设置数据库编码
				mysql_query("SET NAMES {$params['charset']}", $this->db_link);
				if (version_compare($this->get_server_info(), '5.0.2', '>=')) mysql_query("SET SESSION SQL_MODE=''", $this->db_link);
			} else {
				//连接错误,提示信息
				Controller::halt('不能连接到数据库！ <br/>' . mysql_errno() . ' Error Message:' . mysql_error(), 'Warning');				
			}
		}
		return true;
	}
	
	/**
	 * 执行SQL语句
	 */
	public function query($sql) {
		$result = mysql_query($sql, $this->db_link);
		//file_put_contents('sql.txt', $sql . PHP_EOL, FILE_APPEND);
		//日志操作,当调试模式开启时,将所执行过的SQL写入SQL跟踪日志文件,便于DBA进行MYSQL优化。若调试模式关闭,当SQL语句执行错误时写入日志文件
		if (SYS_DEBUG === false) {
			if ($result == false) {
				//获取当前运行的namespace、controller及action名称
				$namespace_id	= App::get_namespace_id();
				$controller_id	= App::get_controller_id();
				$action_id		= App::get_action_id();
				$namespace_code = $namespace_id ? '[' . $namespace_id . ']' : '';
				if (SYS_LOG === true) Log::write($namespace_code . '[' . $controller_id . '][' . $action_id . '] SQL execute failed :' . $sql . ' Error Code:' . $this->errno() . 'Error Message:'.$this->error());
			}			
		} else {
			//获取当前运行的namespace、controller及action名称
			$namespace_id		= App::get_namespace_id();
			$controller_id		= App::get_controller_id();
			$action_id			= App::get_action_id();		
			$sql_log_file		= APP_ROOT . 'logs' . DIRECTORY_SEPARATOR . 'SQL_' . date('Y_m_d', $_SERVER['REQUEST_TIME']) . '.log';
			$namespace_code     = $namespace_id ? '[' . $namespace_id . ']' : '';
			if ($result == true) {
				if (SYS_LOG === true) Log::write($namespace_code . '[' . $controller_id . '][' . $action_id . ']:' . $sql, 'Normal', $sql_log_file);
			} else {
				Controller::halt($namespace_code . '[' . $controller_id . '][' . $action_id . '] SQL execute failed :' . $sql . '<br/>Error Message:' . $this->error() . '<br/>Error Code:'.$this->errno(). '<br/>Error SQL:'.$sql);
			} 
		}
		return $result;
	}
	
	/**
	 * 获取mysql数据库服务器信息
	 */
	public function get_server_info() {
		return mysql_get_server_info($this->db_link);
	}
	
	/**
	 * 获取mysql错误描述信息
	 */
	public function error() {
		return ($this->db_link) ? mysql_error($this->db_link) : mysql_error();
	}
	
	/**
	 * 获取mysql错误信息代码
	 */
	public function errno() {
		return ($this->db_link) ? mysql_errno($this->db_link) : mysql_errno();
	}
	
	/**
	 * 通过一个SQL语句获取一行信息(字段型)
	 */
	public function fetch_row($sql) {
		if (strtolower(substr($sql, 0, 6)) == 'select' && !stripos($sql, 'limit') !== false) $sql .= ' LIMIT 1';
		$result = $this->query($sql);
		if (!$result) return false;
		$rows   = mysql_fetch_assoc($result);
		mysql_free_result($result);
		return $rows;
	}
	
	/**
	 * 通过一个SQL语句获取全部信息(字段型)
	 */
	public function get_array($sql) {
		$result = $this->query($sql);
		if (!$result)return false;
		$myrow  = array();
		while ($row = mysql_fetch_assoc($result)) {
			$myrow[] = $row;
		}
		mysql_free_result($result);
		return $myrow;
	}
	
	/**
	 * 获取insert_id
	 */
	public function insert_id() {
		return ($id = mysql_insert_id($this->db_link)) >= 0 ? $id : mysql_result($this->query("SELECT last_insert_id()"));
	}
	
	/**
	 * 字段的数量
	 */
	public function num_fields($sql) {
		$result = $this->query($sql);
		return mysql_num_fields($result);
	}
	
	/**
	 * 结果集中的数量
	 */
	public function num_rows($sql) {
		$result = $this->query($sql);
		return mysql_num_rows($result);
	}
	
	/**
	 * 获取字段类型
	 */
	public function get_fields_type($table_name) {
	    if (!$table_name) return false;
		$sql   = "SELECT * FROM {$table_name}";
		$res   = mysql_query($sql);
		$types = array();
		while ($row = mysql_fetch_field($res)) {
		    $types[$row->name] = $row->type;
		}
		mysql_free_result($res);
		return $types;
	}
	
	/**
	 * 析构函数
	 */
	public function __destruct() {
		if ($this->db_link) @mysql_close($this->db_link);
	}
	
	/**
	 * 单例模式
	 */
	public static function getInstance($params) {
		if (!self::$instance) {			
			self::$instance = new self($params);
		}
		return self::$instance;
	}
}