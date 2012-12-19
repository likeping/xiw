<?php
/**
 * 模板解析类
 */
 
if (!defined('IN_FINECMS')) exit();

class View extends Fn_base {
	
	protected static $_instance;
	public $view_dir;
	public $compile_dir;
	public $left_delimiter  = '{';
	public $right_delimiter = '}';
	public $_options = array();
	public $theme;
	public $viewpath;
	
	public function __construct() {
		$this->view_dir    = VIEW_DIR;
		$this->compile_dir = APP_ROOT . 'cache' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
		$viewpath          = basename(VIEW_DIR) . '/';
		$this->viewpath    = $viewpath;
		$this->_options['viewpath'] = $viewpath;
		$this->theme       = APP::get_namespace_id() == 'admin' ? false : true;
	}
	
	/**
	 * 获取视图文件的路径
	 */
	protected function get_view_file($file_name) {	
		return $this->view_dir . $file_name . '.html';
	}
	
	/**
	 * 获取视图编译文件的路径
	 */
	protected function get_compile_file($file_name) {	
		return $this->compile_dir . $file_name . '.cache.php';
	}
	
	/**
	 * 生成视图编译文件
	 */
	protected function create_compile_file($compile_file, $content) {
		$compile_dir = dirname($compile_file);
		if (!is_dir($compile_dir)) {
			@mkdir($compile_dir, 0777) or App::display_error(lang('app-9', array('1'=>$compile_dir)));
		} else if (!is_writable($compile_dir)) {
			@chmod($compile_dir, 0777) or App::display_error(lang('app-9', array('1'=>$compile_dir)));
		}
		file_put_contents($compile_file, $content, LOCK_EX) or App::display_error(lang('app-9', array('1'=>$compile_dir)));
	}
	
	/**
	 * 缓存重写分析
	 */
	protected function is_compile($view_file, $compile_file) {
		return (is_file($compile_file) && is_file($view_file) && (filemtime($compile_file) >= filemtime($view_file))) ? false : true;
	}
	
	/**
	 * 设置视图变量
	 */
	public function assign($key, $value = null) {
		if(!$key) return false;
		if(is_array($key)) {
			foreach ($key as $k=>$v) {
				$this->_options[$k] = $v;
			} 
		} else {
			$this->_options[$key] = $value;
		}
		return true;
	}
	
	/**
	 * 分析视图文件名
	 */
	protected function parse_file_name($file_name = null) {
		return $this->theme ? SYS_THEME_DIR . $file_name : $file_name;
	}
	
	/**
	 * 加载视图文件
	 */
	protected function load_view_file($view_file) {
		if (!is_file($view_file)) App::display_error(lang('app-8') . ': ' . $view_file);
		$view_content = file_get_contents($view_file);
		return $this->handle_view_file($view_content);
	}
	
	/**
	 * 编译视图标签
	 */
	protected function handle_view_file($view_content) {
		if (!$view_content) return false;
		//正则表达式匹配的模板标签
		$regex_array = array(
		'#'.$this->left_delimiter.'([a-z_0-9]+)\((.*)\)'.$this->right_delimiter.'#Ui',
		'#'.$this->left_delimiter.'([A-Z_]+)'.$this->right_delimiter.'#',
		'#'.$this->left_delimiter.'\$(.+?)'.$this->right_delimiter.'#i',
		'#'.$this->left_delimiter.'\s*include\s+(.+?)\s*'.$this->right_delimiter.'#is',
		'#'.$this->left_delimiter.'\s*template\s+(.+?)\s*'.$this->right_delimiter.'#is',
		'#'.$this->left_delimiter.'php\s+(.+?)'.$this->right_delimiter.'#is',
		
		'#'.$this->left_delimiter.'sql:([a-z_]+)\s+(.+?)'.$this->right_delimiter.'#is',
		'#'.$this->left_delimiter.'plugin:([a-z_]+)::([a-z_]+)\s+(.+?)'.$this->right_delimiter.'#is',
		'#'.$this->left_delimiter.'relation\s+(.+?)'.$this->right_delimiter.'#is',
		
		'#'.$this->left_delimiter.'list\s+(.+?)return=(.+?)\s?'.$this->right_delimiter.'#i',
		'#'.$this->left_delimiter.'list\s+(.+?)\s?'.$this->right_delimiter.'#i',
		'#'.$this->left_delimiter.'\s?\/list\s?'.$this->right_delimiter.'#i',
		
		'#'.$this->left_delimiter.'\s?if\s+(.+?)\s?'.$this->right_delimiter.'#i',
		'#'.$this->left_delimiter.'\s?else\sif\s+(.+?)\s?'.$this->right_delimiter.'#i',
		'#'.$this->left_delimiter.'\s?else\s?'.$this->right_delimiter.'#i',
		'#'.$this->left_delimiter.'\s?\/if\s?'.$this->right_delimiter.'#i',
		
		'#'.$this->left_delimiter.'\s?loop\s+\$(.+?)\s+\$(\w+?)\s?'.$this->right_delimiter.'#i',
		'#'.$this->left_delimiter.'\s?loop\s+\$(.+?)\s+\$(\w+?)\s?=>\s?\$(\w+?)\s?'.$this->right_delimiter.'#i',
		'#'.$this->left_delimiter.'\s?\/loop\s?'.$this->right_delimiter.'#i',
		
		'#\?\>\s*\<\?php\s#s',
		);
		
		///替换直接变量输出
		$replace_array = array(
		"<?php echo \\1(\\2); ?>",
		"<?php echo \\1; ?>",
		"<?php echo \$\\1; ?>",
		"<?php include \$this->_include('\\1'); ?>",
		"<?php include \$this->_include('\\1'); ?>",
		"<?php \\1 ?>",
		
		"<?php \$sql_model = Controller::model('\\1');\$return = \$sql_model->\\2; ?>",
		"<?php \$plugin_model = App::plugin_model('\\1','\\2');\$return = \$plugin_model->\\3; ?>",
		"<?php \$return = \$this->relation(\\1);?>",
		
		"<?php \$return_\\2 = \$this->_listdata(\"\\1 return=\\2\"); extract(\$return_\\2); \$count_\\2=count(\$return_\\2); if (is_array(\$return_\\2)) { foreach (\$return_\\2 as \$key_\\2=>\$\\2) { ?>",
		"<?php \$return = \$this->_listdata(\"\\1\"); extract(\$return); \$count=count(\$return); if (is_array(\$return)) { foreach (\$return as \$key=>\$t) { ?>",
		"<?php } } ?>",
		
		"<?php if (\\1) { ?>",
		"<?php } else if (\\1) { ?>",
		"<?php } else { ?>",
		"<?php } ?>",
		
		"<?php if (is_array(\$\\1)) { \$count=count(\$\\1);foreach (\$\\1 as \$\\2) { ?>",
		"<?php if (is_array(\$\\1)) { \$count=count(\$\\1);foreach (\$\\1 as \$\\2=>\$\\3) { ?>",
		"<?php } } ?>",
		
		" ",
		);
		return preg_replace($regex_array, $replace_array, $view_content);
	}
	
	/**
	 * 解析标签list
	 */
	protected function _listdata($param) {
	    $_param = explode(' ', $param);
		$param  = array();
		foreach ($_param as $p) {
		    $mark    = strpos($p, '=');
			if ($p && $mark !== false) {
			    $var = substr($p, 0, $mark);
				$val = substr($p, $mark + 1);
				if (isset($var) && $var) $param[$var] = $val;
			}
		}
		$system  = $fields = $_fields = $not = $in = $or = $between = $like = array();
		$dbcache = isset($param['cache']) ? (int)$param['cache'] : 0;
		unset($param['cache']);
		if (is_array($param)) {
		    foreach($param as $key=>$val) {
				//参数归类
				if (in_array($key, array('return', 'more', 'page', 'urlrule', 'num', 'join', 'on', 'order', 'table', 'pagesize', 'action', 'tag'))) {
				    $system[$key] = $val;
				} else {
				    if (substr($key, 0, 3) == 'NOT') {
					    $key       = substr($key, 3);
						$not[]     = $key;
					} elseif(substr($key, 0, 2) == 'OR') {
					    $key       = substr($key, 2);
						$or[]      = $key;
					} elseif(substr($key, 0, 2) == 'IN') {
					    $key       = substr($key, 2);
						$in[]      = $key;
					} elseif(substr($key, 0, 2) == 'BW') {
					    $key       = substr($key, 2);
						$between[] = $key;
					} elseif(substr($key, 0, 4) == 'LIKE') {
					    $key       = substr($key, 4);
						$like[]    = $key;
					}
				    $fields[$key]  = $val;
					$_fields[]     = $key;
				}
			}
		}
		$where = '';
		//status条件
		if (!isset($fields['status'])) {
		    $fields['status'] = 1;
			$_fields[] = 'status';
		}
		//Action判断
		if (isset($system['action']) && $system['action'] == 'position') {
		    //推荐位
			$data = position($fields['id'], (isset($fields['catid']) ? $fields['catid'] : 0), (isset($fields['num']) ? $fields['num'] : 0));
			if (isset($system['return']) && $system['return'] && $system['return'] != 't') {
				return array(
					'return_' . $system['return'] => $data, 
					'total_' . $system['return']  => count($data),
				);
			}
		    return array('return'=>$data, 'total'=>count($data));
		} elseif (isset($system['action']) && $system['action'] == 'keywords') {
		    //搜索关键字
			$search = Controller::model('search');
			$num    = $system['num'] ? (int)$system['num'] : 5;
			if (isset($system['order']) && $system['order']) {
			    $order  = null;
			    $orders = explode(',', $system['order']);
				foreach ($orders as $t) {
					list($_field, $_order) = explode('_', $t);
					if (in_array($_field, array('id', 'keywords', 'addtime', 'total'))) {
						$_orderby = isset($_order) && strtoupper($_order) == 'ASC' ? 'ASC' : 'DESC';
					    $order   .= '`' . $_field . '` ' . $_orderby . ',';
					}
				}
				if (substr($order, -1) == ',') $order = substr($order, 0, -1);
			} else {
			    $order = '`total` DESC';
			}
			$data   = $search->execute('select distinct keywords as title from ' . $search->prefix . 'search order by ' . $order . ' limit ' . $num, true, $dbcache);
			if (isset($system['return']) && $system['return'] && $system['return'] != 't') {
				return array(
					'return_' . $system['return'] => $data, 
					'total_' . $system['return']  => count($data),
				);
			}
		    return array('return'=>$data, 'total'=>count($data));
		} elseif (isset($system['action']) && $system['action'] == 'relation') {
			if (isset($system['tag']) && $system['tag']) {
			    //按关键字搜索
				if (isset($fields['id']) && $fields['id']) $where .= '`id`<>' . (int)$fields['id'];
				$tags   = @explode(',', $system['tag']);
				$kwhere = $k = NULL;
				foreach ($tags as $tag) {
					if ($tag) {
						if (empty($k)) {
							$kwhere .= '`title` like "%' . $tag . '%"';
						} else {
							$kwhere .= ' OR `title` like "%' . $tag . '%"';
						}
						$k = 1;
					}
				}
				if ($kwhere) $where .= ' AND (' . $kwhere . ')';
				unset($k, $tags, $tag, $kwhere, $system['table'], $fields['id']);
			} else {
			    //手动设置的相关文章
				$data = $this->relation($fields['id'], $system['num']);
				if (isset($system['return']) && $system['return'] && $system['return'] != 't') {
					return array(
						'return_' . $system['return'] => $data, 
						'total_' . $system['return']  => count($data),
					);
				}
				return array('return'=>$data, 'total'=>count($data));
			}
		} elseif (isset($system['action']) && $system['action'] == 'field' && isset($fields['modelid']) && $fields['modelid'] && isset($fields['name']) && $fields['name']) {
		    //字段信息
			$cache = new cache_file();
			$mods  = $cache->get('model');
			$mod   = $mods[$fields['modelid']];
			$data  = array();
			if ($mod['fields']) {
			    foreach ($mod['fields']['data'] as $t) {
				    if ($fields['name'] == $t['field']) {
					    //加载字段配置文件
						App::auto_load('fields');
						$data_fields     = '';
						$data_fields    .= '<tr>';
						$data_fields    .= '<th>' . (isset($t['not_null']) && $t['not_null'] ? '<font color="red">*</font> ' : '') . $t['name'] . '：</th>';
						$data_fields    .= '<td>';
						$func            = 'content_' . $t['formtype'];
						$t['setting']    = $t['setting'] ? $t['setting'] : 0;
						//防止出错，把字段内容转换成数组格式
						$content         = array($fields['value']);
						$content         = var_export($content, true);
						if (function_exists($func)) eval("\$data_fields .= " . $func . "(" . $t['field'] . ", " . $content . ", " . $t['setting'] . ");");
						$data_fields    .= $t['tips'] ? '<div class="onShow">' . $t['tips'] . '</div>' : '';
						$data_fields    .= '<span id="ck_' . $t['field'] . '"></span>';
						$data_fields    .= '</td>';
						$data_fields    .= '</tr>';
						$data[0]['form'] = $data_fields;
						if ($t['setting']) {
						    $c = string2array($t['setting']);
							if ($c['content']) {
							    $select  = explode(chr(13), $c['content']);
								$vdata   = array();
								foreach ($select as $i=>$c) {
								    list($n, $v)     = explode('|', $c);
									$vdata[trim($n)] = trim($v);
								}
							    $data[0]['data'] = $vdata;
							}
						}
					}
				}
			}
			if (isset($system['return']) && $system['return'] && $system['return'] != 't') {
				return array(
					'return_' . $system['return'] => $data, 
				);
			}
		    return array('return'=>$data);
		}
		//加载Model实例
		$table  = isset($system['table']) && $system['table'] ? $system['table'] : 'content';
		if (strpos($table, '.') !== false) {
			list($plugin, $table) = explode('.', $table);
			$db = App::plugin_model($plugin, $table);
		} else {
			$db = Controller::model($table);
		}
		$table         = $db->prefix . $table;
		$table_join    = $table_data = $table_fields = $table_join_fields = $table_data_fields = $arrchilds = null;
		$_table_fields = $db->get_table_fields();
		$table_fields  = array_intersect($_fields, $_table_fields);
		if (isset($fields['catid']) && $fields['catid']) { //栏目信息
		    $cache = new cache_file();
			$cats  = $cache->get('category');
			$cat   = $cats[$fields['catid']];
		}
		if (isset($system['join']) && $system['join'] && $system['on']) { //JOIN联合查询
		    $table_join = $system['join'];
			$db_join    = Controller::model($table_join);
			$_table_join_fields = $db_join->get_table_fields();
			$table_join_fields  = array_intersect($_fields, $_table_join_fields);
			foreach ($table_join_fields as $k=>$c) {
			    if (in_array($c, $table_fields)) unset($table_join_fields[$k]);
			}
		    $table_join = $db->prefix . $table_join;
		}
        if (isset($system['more']) && $system['more']) { //附表
		    $model = null;
		    $cache = new cache_file();
		    if ($table == $db->prefix . 'content') {
				$models = $cache->get('model');
				if (isset($fields['catid']) && $fields['catid'] && isset($cat) && $cat) {
					$model = $models[$cat['modelid']];
				} elseif (isset($fields['modelid']) && $fields['modelid']) {
					$model = $models[$fields['modelid']];
				}
			} elseif ($table == $db->prefix . 'member' && isset($fields['modelid']) && $fields['modelid']) {
			    $models = $cache->get('membermodel');
				$model  = $models[$fields['modelid']];
			}
			if ($model) {
				$table_data = $model['tablename'];
				$db_data    = Controller::model($table_data);
				$_table_data_fields = $db_data->get_table_fields();
				$table_data_fields  = array_intersect($_fields, $_table_data_fields);
				foreach ($table_data_fields as $k=>$c) {
					if (in_array($c, $table_fields)) unset($table_data_fields[$k]);
				}
				$table_data = $db->prefix . $table_data;
			}
		}
		//WHERE整合
		$fieldsAll = array($table=>$table_fields, $table_join=>$table_join_fields, $table_data=>$table_data_fields);
		foreach ($fieldsAll as $_table=>$t) {
			if (is_array($t)) {
				foreach ($t as $f) {
				    if ($fields[$f] == '') continue;
				    $and_or = in_array($f, $or) ? 'OR' : 'AND';
					//栏目条件根据子栏目来做为条件
					if ($f == 'catid' && isset($fields['catid']) && $fields['catid']) {
					    if (isset($cat) && $cat && $cat['child']) {
							$arrchilds = $cat['arrchilds'];
							$not_in    = in_array($f, $not) ? 'NOT IN' : 'IN';
							$where    .= ' ' . $and_or . ' `' . $_table . '`.`catid` ' . $not_in . ' (' . $arrchilds . ')';
						} elseif (strpos($fields['catid'], ',') !== false) {
							$not_in    = in_array($f, $not) ? 'NOT IN' : 'IN';
						    $where    .= ' ' . $and_or . ' `' . $_table . '`.`catid` ' . $not_in . ' (' . $fields['catid'] . ')';
						} else {
							$not_in    = in_array($f, $not) ? '<>' : '=';
						    $where    .= ' ' . $and_or . ' `' . $_table . '`.`catid`' . $not_in . $fields['catid'];
						}
					} elseif ($f == 'thumb' && isset($fields['thumb']) && is_numeric($fields['thumb'])) {
					    $where .= $fields['thumb'] ? ' ' . $and_or . ' `' . $_table . '`.`thumb`<>""' : ' ' . $and_or . ' `' . $_table . '`.`thumb`=""';
					} else {
					    $not_in = in_array($f, $in)  ? 'IN'     : '';
						$not_in = in_array($f, $not) ? 'NOT IN' : $not_in;
						if (in_array($f, $between)) {
						    if (strpos($fields[$f], '_') !== false) {
								list($v1, $v2) = explode('_', $fields[$f]);
								$v1     = is_numeric($v1) ? $v1 : '"' . addslashes($v1) . '"';
								$v2     = is_numeric($v2) ? $v2 : '"' . addslashes($v2) . '"';
								$where .= ' ' . $and_or . ' `' . $_table . '`.`' . $f . '` BETWEEN ' . $v1 . ' AND ' . $v2;
							} elseif (strpos($fields[$f], ',') !== false) {
								list($v1, $v2) = explode(',', $fields[$f]);
								$v1     = is_numeric($v1) ? $v1 : '"' . addslashes($v1) . '"';
								$v2     = is_numeric($v2) ? $v2 : '"' . addslashes($v2) . '"';
								$where .= ' ' . $and_or . ' `' . $_table . '`.`' . $f . '` BETWEEN ' . $v1 . ' AND ' . $v2;
							} else {
							    continue;
							}
						} elseif ($not_in) {
						    $where .= ' ' . $and_or . ' `' . $_table . '`.`' . $f . '` ' . $not_in . ' (' . $fields[$f] . ')';
						} elseif (in_array($f, $like)) {
						    $value  = addslashes($fields[$f]);
						    $where .= ' ' . $and_or . ' `' . $_table . '`.`' . $f . '` LIKE "' . $value. '"';
						} else {
						    $value  = is_numeric($fields[$f]) ? $fields[$f] : '"' . addslashes($fields[$f]) . '"';
						    $where .= ' ' . $and_or . ' `' . $_table . '`.`' . $f . '`=' . $value. '';
						}
					}
				}
			}
		}
		if ($where) {
		    if (substr($where, 0, 4) == ' AND') {
			    $where = 'WHERE' . substr($where, 4);
			} elseif (substr($where, 0, 3) == ' OR') {
			    $where = 'WHERE' . substr($where, 3);
			} else {
			    $where = 'WHERE' . $where;
			}
		}
		//FROM整合
		$from = 'FROM ' . $table;
		if ($table_data) {
		    $from .= ' LEFT JOIN ' . $table_data . ' ON `' . $table . '`.`' . $db->get_primary_key() . '`=`' . $table_data . '`.`' . $db_data->get_primary_key() . '`';
		}
		if ($table_join && $system['on']) {
		    $_join_name     = null;
		    if (in_array($system['on'], $_table_fields)) {
			    $_join_name = $table;
			} elseif (isset($_table_data_fields) && in_array($system['on'], $_table_data_fields)) {
			    $_join_name = $table_data;
			}
		    if ($_join_name) {
			    $from .= ' LEFT JOIN ' . $table_join . ' ON `' . $table_join . '`.`' . $db_join->get_primary_key() . '`=`' . $_join_name . '`.`' . $system['on'] . '`';
			}
		}
		//ORDER排序
		$order = 'ORDER BY';
		if ($system['order']) {
		    $orders = explode(',', $system['order']);
			foreach ($orders as $t) {
			    list($_field, $_order) = explode('_', $t);
			    $_name     = null;
			    if (in_array($_field, $_table_fields)) {
					$_name = $table;
				} elseif (isset($_table_data_fields) && in_array($_field, $_table_data_fields)) {
					$_name = $table_data;
				} elseif (isset($_table_join_fields) && in_array($_field, $_table_join_fields)) {
					$_name = $table_join;
				}
				$_orderby = isset($_order) && strtoupper($_order) == 'ASC' ? 'ASC' : 'DESC';
				if ($_name) $order .= ' `' . $_name . '`.`' . $_field . '` ' . $_orderby . ',';
			}
			if (substr($order,-1) == ',') $order = substr($order, 0, -1);
		} else {
		    $order = '';
		}
		//分页和limit
		$limit = '';
		if ($system['num']) {
		    $limit   = 'LIMIT ' . $system['num'];
		} elseif ($system['page']) {
		    $pageurl = '';
		    if ($system['urlrule']) {
			    $pageurl  = str_replace(array('_page_', '[page]'), '{page}', $system['urlrule']);
				$pagesize = $system['pagesize'] ? $system['pagesize'] : (isset($cat['pagesize']) ? $cat['pagesize'] : 10);
			} elseif ($cat) {
			    $pageurl  = getCaturl($cat, '{page}');
				$pagesize = $system['pagesize'] ? $system['pagesize'] : $cat['pagesize'];
			}
			if ($pageurl) {
				//统计数量
				$sql      = 'SELECT count(*) AS total ' . $from . ' ' . $where . ' ORDER BY `updatetime`';
				$count    = $db->execute($sql, false, $dbcache);
				$total    = $count['total'];
			    $pagelist = Controller::instance('pagelist');
				$pagelist->loadconfig();
		        $start_id = $pagesize * ($system['page'] - 1);
				$limit    = 'LIMIT ' . $start_id . ',' . $pagesize;
				$pagelist = $pagelist->total($total)->url($pageurl)->num($pagesize)->page($system['page'])->output();
			}
		}
		//查询结果
		$sql  = 'SELECT * ' . $from . ' ' . $where . ' ' . $order . ' ' . $limit;
		$data = $db->execute($sql, true, $dbcache);
		//释放变量
		unset($_param, $param, $par, $p, $fields, $_fields, $not, $in, $or, $between, $dbcache, $like);
		unset($table, $db, $table_join, $table_data, $table_fields, $table_join_fields, $table_data_fields, $arrchilds, $_table_fields);
		unset($fieldsAll, $_table_data_fields, $cache, $db_join, $cats, $cat, $models, $model, $db_data, $where, $order, $from);
		if (isset($system['return']) && $system['return'] && $system['return'] != 't') {
		    return array(
			    'pagelist_' . $system['return'] => $pagelist, 
				'return_' . $system['return']   => $data, 
				'sql_' . $system['return']      => $sql, 
				'total_' . $system['return']    => count($data)
		    );
		}
		return array('pagelist'=>$pagelist, 'return'=>$data, 'sql'=>$sql, 'total'=>count($data));
	}
	
    /**
     * 相关文章列表
     */
    protected function relation($id, $num=10) {
	    $db   = Controller::model('content');
	    $row  = $db->find($id);
	    if (empty($row) || empty($row['relation'])) return NULL;
	    $ids  = $row['relation'];
	    $data = $db->relation($ids, $num);
	    return $data;
	}
	
   /**
	 * 加载include视图
	 */
	protected function _include($file_name) {
		if (!$file_name) return false;
		$file_name    = $this->parse_file_name($file_name);
		$view_file	  = $this->get_view_file($file_name);
		$compile_file = $this->get_compile_file($file_name);
		if ($this->is_compile($view_file, $compile_file)) {			
			$view_content = $this->load_view_file($view_file);
			$this->create_compile_file($compile_file, $view_content);
		}
		return $compile_file;
	}		
	
	/**
	 * 显示视图文件
	 */
	public function display($file_name = null) {
		if (!empty($this->_options)) {
			extract($this->_options, EXTR_PREFIX_SAME, 'data');
			$this->_options = array();
		}
		$file_name    = $this->parse_file_name($file_name);
		$view_file	  = $this->get_view_file($file_name);
		$compile_file = $this->get_compile_file($file_name);
		if ($this->is_compile($view_file, $compile_file)) {
			$view_content = $this->load_view_file($view_file);
			$this->create_compile_file($compile_file, $view_content);
		}
		include $compile_file;
	}
	
	/**
	 * 设置风格
	 */
	public function setTheme($theme) {
	    $this->theme = $theme;
	}
	
	/**
	 * 析构函数
	 */
	public function __destruct() {
		$this->_options = array();
	}
	
	/**
	 * 单件模式调用方法
	 */
 	public static function getInstance(){
 		if (!self::$_instance instanceof self) {
 			self::$_instance = new self();
 		}
		return self::$_instance;
	}
}