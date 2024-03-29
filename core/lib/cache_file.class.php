<?php
/**
 * cache_file class file
 */

if (!defined('IN_FINECMS')) exit();

class cache_file extends Fn_base {
	
	/**
	 * 缓存目录
	 * 
	 * @var string
	 */
	 public $cache_dir;
	 
	 
 	/**
	  * 构造函数,初始化变量
	  * 
	  * @access public
	  * @return boolean
	  */
	 public function __construct() {
	 	//设置缓存目录
	 	$this->cache_dir = APP_ROOT . 'cache/data' . DIRECTORY_SEPARATOR;
	 	return true;
	 }
	 
	/**
	 * 分析缓存文件名.
	 * 
	 * @param string $file_name
	 * @return string
	 */
	protected function parse_cache_file($file_name) {
		return $this->cache_dir . $file_name . '.cache.php';
	}
	
	/**
	 * 设置缓存
	 * 
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	public function set($key, $value) {
		if (!$key) return false;
		//分析缓存文件
		$cache_file = $this->parse_cache_file($key);
		//分析缓存内容
		$value = (!is_array($value)) ? serialize(trim($value)) : serialize($value);
		//分析缓存目录
		if (!is_dir($this->cache_dir)) {
			mkdir($this->cache_dir, 0777);
		} else {
			if (!is_writeable($this->cache_dir)) {
				chmod($this->cache_dir, 0777);
			}
		}
		return file_put_contents($cache_file, $value, LOCK_EX) ? true : false;
	}
	
	/**
	 * 获取一个已经缓存的变量
	 * 
	 * @param string $key
	 * @return string
	 */
	public function get($key) {
		if (!$key) return false;
		//分析缓存文件
		$cache_file = $this->parse_cache_file($key);
		return is_file($cache_file) ? unserialize(file_get_contents($cache_file)) : false;
	}
	
	/**
	 * 删除缓存
	 * 
	 * @param string $key
	 * @return void
	 */
	public function delete($key) {
		if (!$key) return true;
		//分析缓存文件
		$cache_file = $this->parse_cache_file($key);
		return is_file($cache_file) ? unlink($cache_file) : true;
	}
}