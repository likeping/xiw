<?php
/**
 * App.php
 * 核心类,并初始化系统的基本设置
 */

if (!defined('IN_FINECMS')) exit();
 
error_reporting(E_ALL^E_NOTICE);

/**
 * 配置
 */

define('SYS_ROOT',           dirname(__FILE__) . DIRECTORY_SEPARATOR);          //核心文件所在路径
define('SYS_START_TIME',     microtime(true));                                  //设置程序开始执行时间
define('CONTROLLER_DIR',     APP_ROOT . 'controllers' . DIRECTORY_SEPARATOR);   //controller目录的路径
define('MODEL_DIR',          APP_ROOT . 'models' . DIRECTORY_SEPARATOR);        //model目录的路径	
define('VIEW_DIR',           APP_ROOT . 'views' . DIRECTORY_SEPARATOR);         //view目录的路径
define('CONFIG_DIR',         APP_ROOT . 'config' . DIRECTORY_SEPARATOR);        //config目录的路径
define('EXTENSION_PATH',     'extensions');                                     //extension目录文件夹
define('EXTENSION_DIR',      APP_ROOT . EXTENSION_PATH . DIRECTORY_SEPARATOR);  //extension目录的路径
define('PLUGIN_DIR',         APP_ROOT . 'plugins' . DIRECTORY_SEPARATOR);       //插件目录文件夹
define('SYS_THEME_DIR',      $config['SITE_THEME'] . DIRECTORY_SEPARATOR);      //模板风格
define('DEFAULT_CONTROLLER', 'Index');                                          //设置系统默认的controller名称,默认为:Index
define('DEFAULT_ACTION',     'index');                                          //设置系统默认的action名称,默认为index
define('SYS_LOG',            $config['SYS_LOG']);                               //设置是否开启运行日志
define('SYS_DEBUG',          $config['SYS_DEBUG']);                             //设置是否开启调试模式.开启后,程序运行出现错误时,显示错误信息
define('SYS_DOMAIN',         $config['SYS_DOMAIN']);                            //域名目录，针对虚拟主机用户
define('URL_SEGEMENTATION',  '/');                                              //定义网址路由的分割符
define('ENTRY_SCRIPT_NAME',  'index.php');                                      //定义入口文件名
define('SITE_MEMBER_COOKIE', $config['SITE_MEMBER_COOKIE']);                    //会员登录Cookie随机字符码
define('SYS_ATTACK_LOG',     isset($config['SYS_ATTACK_LOG']) && $config['SYS_ATTACK_LOG']     ? $config['SYS_ATTACK_LOG']  : false);          //系统攻击日志开关
define('SYS_LANGUAGE',       isset($config['SYS_LANGUAGE']) && $config['SYS_LANGUAGE']         ? $config['SYS_LANGUAGE']    : 'zh-cn');        //网站语言设置
define('ADMIN_NAMESPACE',    isset($config['ADMIN_NAMESPACE']) && $config['ADMIN_NAMESPACE']   ? $config['ADMIN_NAMESPACE'] : 'admin');        //定义后台管理路径的名字
define('SYS_VAR_PREX',       isset($config['SYS_VAR_PREX']) && $config['SYS_VAR_PREX']         ? $config['SYS_VAR_PREX']    : 'finecms_');     //SESSION和COOKIE变量前缀
define('TIME_FORMAT',        isset($config['SITE_TIME_FORMAT']) && $config['SITE_TIME_FORMAT'] ? $config['SITE_TIME_FORMAT'] : 'Y-m-d H:i:s'); //输出时间格式化
define('SYS_TIME_ZONE',      'Etc/GMT' . ($config['SITE_TIMEZONE'] > 0 ? '-' : '+') . (abs($config['SITE_TIMEZONE'])));                        //时区
define('LANGUAGE_DIR',       EXTENSION_DIR . 'language' . DIRECTORY_SEPARATOR . SYS_LANGUAGE . DIRECTORY_SEPARATOR);                           //网站语言文件

/**
 * 环境参数
 */

if (!file_exists(LANGUAGE_DIR)) exit('语言目录不存在：' . LANGUAGE_DIR);
if (function_exists('ini_set'))  SYS_DEBUG ? ini_set('display_errors', true) : ini_set('display_errors', false);
if (function_exists('ini_set'))  ini_set('memory_limit', '1024M');
date_default_timezone_set(SYS_TIME_ZONE);
if (isset($config['SYS_GZIP']) && $config['SYS_GZIP'] && function_exists('ob_gzhandler')) ob_start('ob_gzhandler');
$language = require LANGUAGE_DIR . 'lang.php';

/**
 * 系统核心全局控制类
 */

abstract class App {
    
	public static $namespace;
	public static $controller;
	public static $action;
	public static $plugin;
	public static $_objects   = array();
	public static $_inc_files = array();
	public static $config     = array();
	
	/**
	 * 分析URL信息
	 */
	private static function parse_request() {
	    if (SYS_DOMAIN) {
		    $_SERVER['SCRIPT_NAME'] = str_replace('/' . SYS_DOMAIN, '', $_SERVER['SCRIPT_NAME']);
			$_SERVER['REQUEST_URI'] = str_replace('/' . SYS_DOMAIN, '', $_SERVER['REQUEST_URI']);
		}
		$path_url_string = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : $_SERVER['REQUEST_URI'];
		$new_url_string  = '';
		if (!isset($_SERVER['QUERY_STRING']) || empty($_SERVER['QUERY_STRING'])) {
			$router_config_file = CONFIG_DIR . 'router.ini.php';
			if (is_file($router_config_file)) {
				$router_array   = require_once $router_config_file;
				if (is_array($router_array) && !empty($router_array)) {
					$path_url_router = str_replace(str_replace('/' . ENTRY_SCRIPT_NAME, '', $_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']);
					$path_url_router = str_replace('/' . ENTRY_SCRIPT_NAME, '', $path_url_router);
					if (substr($path_url_router, 0, 1) == '/') $path_url_router = substr($path_url_router, 1);
					if ($path_url_router) {
						foreach ($router_array as $router_key=>$router_value) {									
							if (preg_match('#' . $router_key . '#', $path_url_router)) {
							    $new_url_string = preg_replace('#' . $router_key . '#', $router_value, $path_url_router);
								break;
							}
						}
						if (empty($new_url_string)) self::display_404_error(0);
					}
				}
			}
		}
		$path_url_string  = $new_url_string ? $new_url_string : $path_url_string;
		parse_str($path_url_string, $url_info_array);
		$namespace_name   = trim((isset($url_info_array['s']) && $url_info_array['s']) ? $url_info_array['s'] : '');
		$controller_name  = trim((isset($url_info_array['c']) && $url_info_array['c']) ? $url_info_array['c'] : DEFAULT_CONTROLLER);						
		$action_name      = trim((isset($url_info_array['a']) && $url_info_array['a']) ? $url_info_array['a'] : DEFAULT_ACTION);
		if ($namespace_name == 'admin' && ADMIN_NAMESPACE != 'admin') self::display_404_error(5);
		$namespace_name   = $namespace_name == ADMIN_NAMESPACE ? 'admin' : $namespace_name;
		self::$namespace  = strtolower($namespace_name);
		self::$controller = ucfirst(strtolower($controller_name));
		self::$action 	  = strtolower($action_name);
		$_GET             = array_merge($_GET, $url_info_array);
		return true;
	}
	
	/**
	 * 项目运行函数
	 */
	public static function run($config) {
		static $_app    = array();
		self::$config   = $config;
		self::parse_request();
		$app_id         = self::$controller . '_' . self::$action;
		if (!isset($_app[$app_id]) || $_app[$app_id] == null) {
			$namespace  = self::$namespace;
			$controller = self::$controller . 'Controller';
			$action     = self::$action . 'Action';
			self::load_file(SYS_ROOT . 'Base.php');
			self::load_file(SYS_ROOT . 'Controller.php');
			self::load_file(CONTROLLER_DIR . 'Common.php');
			if ($namespace && is_dir(CONTROLLER_DIR . $namespace)) {
				$controller_file = CONTROLLER_DIR . $namespace . DIRECTORY_SEPARATOR . $controller . '.php';
				if (!is_file($controller_file)) self::display_404_error(1);
				if (is_file(CONTROLLER_DIR . $namespace . DIRECTORY_SEPARATOR . 'Common.php')) self::load_file(CONTROLLER_DIR . $namespace . DIRECTORY_SEPARATOR . 'Common.php');
			    self::load_file($controller_file);
			} elseif ($namespace && is_dir(PLUGIN_DIR . $namespace)) {
			    $common_file      = PLUGIN_DIR . $namespace . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'Common.php';
				$controller_file  = PLUGIN_DIR . $namespace . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $controller . '.php';
				if (is_file($common_file) && is_file($controller_file)) {
				    self::$plugin =  strtolower($namespace);
					self::load_file($common_file);
					self::load_file($controller_file);
				} else {
					self::display_404_error(2);
				}						
			} elseif (is_file(CONTROLLER_DIR . $controller . '.php')) {	
				self::load_file(CONTROLLER_DIR . $controller . '.php');				
			} else {
			    self::display_404_error(3);		
			}
			$app_object = new $controller();
			if (method_exists($controller, $action)) {
				$_app[$app_id] = $app_object->$action();
			} else {
				self::display_404_error(4);
			}
		}
		return $_app[$app_id];
	}
	
	/**
	 * 显示404错误提示
	 */
	private static function display_404_error($id=0) {
	    header('HTTP/1.1 404 Not Found');
		require SYS_ROOT . 'html/error404.php';
		exit();		
	}
	
	/**
     * 显示错误提示
     */
    public static function display_error($message, $back=0) {
        if (!$message) return false;
		require SYS_ROOT . 'html/message.php';
        exit();
    }
	
	/**
	 * 核心类引导数组
	 */
	public static $core_class_array = array(
		'Model'     		=> 'Model.php',
		'Log'       		=> 'Log.php',
		'View'				=> 'View.php',
		'mysql'				=> 'lib/mysql.class.php',
		'html'				=> 'lib/html.class.php',
		'cache_file'		=> 'lib/cache_file.class.php',
		'pagelist'			=> 'lib/pagelist.class.php',
		'cookie'			=> 'lib/cookie.class.php',
		'session'			=> 'lib/session.class.php',
		'file_list'			=> 'lib/file_list.class.php',
		'image_lib'			=> 'lib/image_lib.class.php',
		'check'				=> 'lib/check.class.php',
		'file_upload'		=> 'lib/file_upload.class.php',
		'client'			=> 'lib/client.class.php',
		'pinyin'			=> 'lib/pinyin.class.php',
		'tree'				=> 'lib/tree.class.php',
		'loader'			=> 'lib/loader.class.php',
		'auth'              => 'lib/auth.class.php',
		'mail'              => 'lib/mail.class.php',
		'captcha'           => 'lib/captcha.class.php',
		'pclzip'            => 'lib/pclzip.class.php',
		'linkage_tree'      => 'lib/linkage_tree.class.php',
	);
	
	/**
	 * 项目文件的自动加载
	 */
	public static function auto_load($class_name) {
		if (isset(self::$core_class_array[$class_name])) {				
			self::load_file(SYS_ROOT . self::$core_class_array[$class_name]);			
		} else if (substr($class_name, -5) == 'Model') {	
			if (is_file(MODEL_DIR . $class_name . '.php')) {
				self::load_file(MODEL_DIR . $class_name . '.php');
			} elseif ((is_file(PLUGIN_DIR . self::$namespace. DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $class_name . '.php'))) {
			    self::load_file(PLUGIN_DIR . self::$namespace. DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $class_name . '.php');
			} else {
				Controller::halt('The Model file: ' . $class_name . ' is not exists!');	
			}
		} else {
			if (is_file(EXTENSION_DIR . $class_name . '.php')) {
				self::load_file(EXTENSION_DIR . $class_name . '.php');
			} else {
				Controller::halt('The File:' . $class_name . '.php is not exists!');
			}		
		}
	}
	
	/**
	 * 获取当前运行的namespace名称
	 */
	public static function get_namespace_id() {
		return strtolower(self::$namespace);
	}
	
	/**
	 * 获取当前运行的controller名称
	 */
	public static function get_controller_id() {
		return strtolower(self::$controller);
	}
	
	/**
	 * 获取当前运行的action名称
	 */
	public static function get_action_id() {
		return self::$action;
	}
	
	/**
	 * 获取配置信息
	 */
	public static function get_config() {
	    $data = self::$config;
		$data['PLUGIN_DIR'] = basename(PLUGIN_DIR);
	    return $data;
	}
	
   /**
	 * 获取当前运行的plugin名称
	 */
	public static function get_plugin_id() {
		return self::$plugin;
	}
	
	/**
	 * 单例模式
	 */
	public static function singleton($class_name) {
		if (!$class_name) return false;
		$key = strtolower($class_name);
		if (isset(self::$_objects[$key])) return self::$_objects[$key];
		return self::$_objects[$key] = new $class_name();
	}
	
	/**
	 * 返回插件模型的唯一实例(单例模式)
	 */
    public static function plugin_model($plugin, $table_name) {
	    if (!$table_name || !$plugin) return false;
		$model_name = ucfirst(strtolower($table_name)) . 'Model';
	    $model_file = PLUGIN_DIR . $plugin . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $model_name . '.php';
	    if (!is_file($model_file)) Controller::halt('The pluginModel(#' . $plugin . ') file:' . $model_name . '.php is not exists!');
	    $key        = strtolower($model_name);
	    if (isset(self::$_objects[$key])) return self::$_objects[$key];
	    require $model_file;
		return self::$_objects[$key] = new $model_name();
	}
	
	/**
	 * 静态加载文件
	 */
	public static function load_file($file_name) {
		if (!$file_name) return false;
		if (!isset(self::$_inc_files[$file_name]) || self::$_inc_files[$file_name] == false) {
			if (!is_file($file_name)) Controller::halt('The file:' . $file_name . ' not found!');
			include_once $file_name;
			self::$_inc_files[$file_name] = true;
		}
		return self::$_inc_files[$file_name];
	}
}

/**
 * URL函数
 */
function url($route, $params = null) {
	return Controller::create_url($route, $params);
}

/**
 * 插件中的URL函数
 */
function purl($route, $params = null) {
	$route = App::get_namespace_id() . '/' . $route;
	return url($route, $params);
}

/**
 * 语言调用函数
 */
function lang($name, $data=null) {
    global $language;
	$string   = isset($language[$name]) ? $language[$name] : '未知(#' . $name . ')';
	if ($data) {
	    foreach ($data as $r=>$t) {
		    $string = str_replace('{' . $r . '}', $t, $string);
		}
	}
	return $string;
}

/**
 * 程序执行时间
 */
function runtime() {
	$temptime = explode(' ', SYS_START_TIME);
	$time     = $temptime[1] + $temptime[0];
	$temptime = explode(' ', microtime());
	$now      = $temptime[1] + $temptime[0];
	return number_format($now - $time, 6);
}

spl_autoload_register(array('App', 'auto_load'));