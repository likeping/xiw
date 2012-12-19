<?php

class IndexController extends Admin {

    public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 首页
	 */
	public function indexAction() {
	    $this->view->assign('menu', $this->optionMenu());
		$this->view->display('admin/index');
	}
	
	/**
	 * 后台首页
	 */
	public function mainAction() {
	    $this->view->assign(array(
		    'sqlversion' => $this->user->get_server_info(),
			'model'      => $this->cache->get('model'),
		));
	    $this->view->display('admin/main');
	}
	
	/**
	 * 网站配置
	 */
	public function configAction() {
        //变量注释
	    $string = array(
            'ADMIN_NAMESPACE'         => lang('a-cfg-8'),
            'SYS_DEBUG'               => lang('a-cfg-9'),
	        'SYS_LOG'                 => lang('a-cfg-10'),
	        'SYS_DOMAIN'              => lang('a-cfg-11'),
            'SYS_LANGUAGE'            => lang('a-cfg-12'),
            'SYS_VAR_PREX'            => lang('a-cfg-13'),
			'SYS_GZIP'                => lang('a-cfg-14'),
			'SITE_MEMBER_COOKIE'      => lang('a-cfg-0'),
	    
	        'SYS_ILLEGAL_CHAR'        => lang('a-cfg-7'),
			'SYS_ATTACK_LOG'          => lang('a-cfg-1'),
			'SYS_ATTACK_MAIL'         => lang('a-cfg-2'),
			'SITE_SYSMAIL'            => lang('a-cfg-4'),
			'SITE_TIMEZONE'           => lang('a-cfg-15'),
	        'SITE_THEME'              => lang('a-cfg-16'),
	        'SITE_NAME'               => lang('a-cfg-17'),
            'SITE_TITLE'              => lang('a-cfg-18'),
            'SITE_KEYWORDS'           => lang('a-cfg-19'),
            'SITE_DESCRIPTION'        => lang('a-cfg-20'),
	        'SITE_WATERMARK'          => lang('a-cfg-21'),
	        'SITE_WATERMARK_ALPHA'    => lang('a-cfg-22'),
	        'SITE_WATERMARK_TEXT'     => lang('a-cfg-23'),
	        'SITE_ADMINLOG'           => lang('a-cfg-24'),
	        'SITE_MAIL_TYPE'          => lang('a-cfg-25'),
	        'SITE_MAIL_SERVER'        => lang('a-cfg-26'),
	        'SITE_MAIL_PORT'          => lang('a-cfg-27'),
	        'SITE_MAIL_FROM'          => lang('a-cfg-28'),
	        'SITE_MAIL_AUTH'          => lang('a-cfg-29'),
	        'SITE_MAIL_USER'          => lang('a-cfg-30'),
	        'SITE_MAIL_PASSWORD'      => lang('a-cfg-31'),
	        'SITE_MAP_TIME'           => lang('a-cfg-32'),
	        'SITE_MAP_NUM'            => lang('a-cfg-33'),
	        'SITE_MAP_UPDATE'         => lang('a-cfg-34'),
	        'SITE_MAP_AUTO'           => lang('a-cfg-35'),
	        'SITE_SEARCH_PAGE'        => lang('a-cfg-36'),
	        'SITE_SEARCH_TYPE'        => lang('a-cfg-37'),
			'SITE_SEARCH_INDEX_CACHE' => lang('a-cfg-38'),
			'SITE_SEARCH_DATA_CACHE'  => lang('a-cfg-39'),
			'SITE_SEARCH_SPHINX_HOST' => lang('a-cfg-40'),
			'SITE_SEARCH_SPHINX_PORT' => lang('a-cfg-41'),
			'SITE_SEARCH_SPHINX_NAME' => lang('a-cfg-42'),
			'SITE_ADMIN_CODE'         => lang('a-cfg-43'),
			'SITE_THUMB_WIDTH'        => lang('a-cfg-44'),
			'SITE_THUMB_HEIGHT'       => lang('a-cfg-45'),
			'SITE_ADMIN_PAGESIZE'     => lang('a-cfg-46'),
			'SITE_SEARCH_KW_FIELDS'   => lang('a-cfg-47'),
			'SITE_SEARCH_KW_OR'       => lang('a-cfg-48'),
			'SITE_SEARCH_URLRULE'     => lang('a-cfg-49'),
			'SITE_TAG_PAGE'           => lang('a-cfg-50'),
			'SITE_TAG_CACHE'          => lang('a-cfg-51'),
			'SITE_TAG_URLRULE'        => lang('a-cfg-52'),
			'SITE_TAG_LINK'           => lang('a-cfg-53'),
			'SITE_KEYWORD_NUMS'       => lang('a-cfg-54'),
			'SITE_KEYWORD_CACHE'      => lang('a-cfg-55'),
			'SITE_TAG_URL'            => lang('a-cfg-56'),
			'SITE_TIME_FORMAT'        => lang('a-cfg-57'),
			
        );
	    //加载应用程序配置文件.
	    $config         = self::load_config('config');
        $chunk          = array_chunk($string, 8, true);
        $config_core    = $chunk[0]; //系统核心文件
        if ($this->post('submit')) {
            $configdata = $this->post('data');
            $content    = "<?php" . PHP_EOL . "if (!defined('IN_FINECMS')) exit();" . PHP_EOL . PHP_EOL . "/**" . PHP_EOL . " * 应用程序配置信息" . PHP_EOL . " */" . PHP_EOL . "return array(" . PHP_EOL .
            PHP_EOL . "	/* 系统核心配置 */" . PHP_EOL . PHP_EOL;
			$system     = array();
            foreach ($config_core as $var=>$msg) {
                $value  = "'" . $config[$var] . "'";
                if (is_bool($config[$var])) {
				    $value = $config[$var] ? 'true' : 'false';
				} elseif ($var == 'ADMIN_NAMESPACE' && (!isset($config[$var]) || empty($config[$var]))) {
				    $value = "'admin'";
				} elseif ($var == 'SYS_VAR_PREX' && (!isset($config[$var]) || empty($config[$var]))) {
				    $value = "'finecms_" . substr(md5(time()), 0, 5) . "_'";
				} elseif ($var == 'SYS_LANGUAGE' && (!isset($config[$var]) || empty($config[$var]))) {
				    $value = "'zh-cn'";
				} elseif ($var == 'SITE_MEMBER_COOKIE' && (!isset($config[$var]) || empty($config[$var]))) {
				    $value = "'" . substr(md5(time()), 5, 15) . "'";
				} elseif ($config[$var] == 'true') {
				    $value = 'true';
				} elseif ($config[$var] == 'false') {
				    $value = 'false';
				}
                $content  .= "	'" . strtoupper($var) . "'" . $this->setspace($var) . " => " . $value . ",  //" . $msg . PHP_EOL;
				$system[]  = $var;
            }
            $content .= PHP_EOL . "	/* 网站相关配置 */" . PHP_EOL . PHP_EOL;
            foreach ($configdata as $var=>$val) {
			    if (!in_array($var, $system)) {
                    $value    = $val == 'false' || $val == 'true' ? $val : "'" . $val . "'";
                    $content .= "	'" . strtoupper($var) . "'" . $this->setspace($var) . " => " . $value . ",  //" . $string[$var] . PHP_EOL;
				}
            }
            $content .= PHP_EOL . ");";
            file_put_contents(CONFIG_DIR . 'config.ini.php', $content);
            $this->adminMsg(lang('success'), purl('index/config', array('type'=>$this->get('type'))), 3, 1, 1);
        }
        //模板风格
        $arr   = file_list::get_file_list(VIEW_DIR);
        $theme = array_diff($arr, array('error', 'layout', 'admin', 'index.html', 'install'));
        $this->view->assign(array(
            'theme'  => $theme,
            'string' => $string,
            'data'   => $config,
			'type'   => $this->get('type') ? $this->get('type') : 1,
        ));
        $this->view->display('admin/config');
	}
	
	/**
	 * 全站缓存
	 */
	public function cacheAction() {
	    $caches = array(
	        0   => array('20',  'plugin',      'cache'),
			1   => array('21',  'auth',        'cache'),
	        2   => array('22',  'model',       'cache'),
	        3   => array('23',  'category',    'cache'),
	        4   => array('24',  'position',    'cache'),
	        5   => array('25',  'relatedlink', 'cache'),
	        6   => array('26',  'block',       'cache'),
	        7   => array('27',  'theme',       'cache'),
			8   => array('28',  'linkage',     'cache'),
			9   => array('29',  'member',      'cache'),
			10  => array('30',  'tag',         'cache'),
			11  => array('103', 'ip',          'cache'),
	    );
	    if ($this->get('show')) {
	        $id    = $_GET['id'] ? intval($_GET['id']) : 0;
	        $cache = $caches[$id];
	        if (empty($cache)) {
			    $this->cache->delete('install');
	            echo '<script type="text/javascript">window.parent.addtext("<li style=\"color: red;\">' . lang('a-ind-31') . '</li>");</script>';
	        }
	        $c     = ucfirst($cache[1]) . 'Controller';
	        $a     = $cache[2] . 'Action';
	        App::load_file(CONTROLLER_DIR . 'admin' . DIRECTORY_SEPARATOR . $c . '.php');
	        $app   = new $c();
	        $id ++;
	        if (method_exists($c, $a)){				
				$app->$a(1);
				echo '<script type="text/javascript">window.parent.addtext("<li>' . lang('a-ind-' . $cache[0]) . '</li>");</script>';
				$this->adminMsg($msg, purl('index/cache/', array('show'=>1,'id'=>$id), 1), 0);		
			}
	    } else {
	        $this->view->display('admin/cache');
	    }
	}
	
	/**
	 * 后台日志
	 */
	public function logAction() {
	    $page     = (int)$this->get('page') ? (int)$this->get('page') : 1;
	    $pagelist = $this->instance('pagelist');
		$pagelist->loadconfig();
	    $logsdir  = APP_ROOT . 'cache' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
		$filedata = file_list::get_file_list($logsdir);
		$data     = array();
		$username = $this->post('submit') ? $this->post('kw') : $this->get('username');
		if ($filedata) {
		    $filedata      = array_reverse($filedata);
			foreach ($filedata as $file) {
				if (substr($file, -4) == '.log') {
					$fdata = file_get_contents($logsdir . $file);
					$fdata = explode(PHP_EOL, $fdata);
					foreach ($fdata as $v) {
						$t = unserialize($v);
						if (is_array($t) && $t) {
							if ($username) {
							   if ($t['username'] == $username) $data[] = $t;
							} else {
								$data[] = $t;
							}
						}
					}
				}
			}
		}
		$total    = count($data);
		$pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
		$list     = array();
		$count_pg = ceil($total/$pagesize);
        $offset   = ($page - 1) * $pagesize;		
		foreach ($data as $i=>$t) {
		    if ($i >= $offset && $i < $offset + $pagesize) $list[] = $t;
		}
		$url      = purl('index/log', array('page'=>'{page}', 'username'=>$username));
		$pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
		$this->view->assign(array(
	        'list'     => $list,
	        'pagelist' => $pagelist,
	    ));
	    $this->view->display('admin/log');
	}
	
	/**
	 * 攻击日志
	 */
	public function attackAction() {
	    $page     = (int)$this->get('page') ? (int)$this->get('page') : 1;
	    $pagelist = $this->instance('pagelist');
		$pagelist->loadconfig();
	    $logsdir  = APP_ROOT . 'cache' . DIRECTORY_SEPARATOR . 'attack' . DIRECTORY_SEPARATOR;
		$filedata = file_list::get_file_list($logsdir);
		$data     = array();
		$ip       = $this->post('submit') ? $this->post('kw') : $this->get('ip');
		if ($filedata) {
		    $filedata      = array_reverse($filedata);
			foreach ($filedata as $file) {
				if (substr($file, -4) == '.log') {
					$fdata = file_get_contents($logsdir . $file);
					$fdata = explode(PHP_EOL, $fdata);
					foreach ($fdata as $v) {
						$t = unserialize($v);
						if (is_array($t) && $t) {
							if ($ip) {
							   if ($t['ip'] == $ip) $data[] = $t;
							} else {
								$data[] = $t;
							}
						}
					}
				}
			}
		}
		$total    = count($data);
		$pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
		$list     = array();
		$count_pg = ceil($total/$pagesize);
        $offset   = ($page - 1) * $pagesize;		
		foreach ($data as $i=>$t) {
		    if ($i >= $offset && $i < $offset + $pagesize) $list[] = $t;
		}
		$url      = purl('index/attack', array('page'=>'{page}', 'ip'=>$ip));
		$pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
		$this->view->assign(array(
	        'list'     => $list,
	        'pagelist' => $pagelist,
	    ));
	    $this->view->display('admin/attacklog');
	}
	
	/**
	 * 清除日志
	 */
	public function clearlogAction() {
	    $time     = strtotime('-30 day');
	    $logsdir  = APP_ROOT . 'cache' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
		$filedata = file_list::get_file_list($logsdir);
		$count    = 0;
		if ($filedata) {
			foreach ($filedata as $file) {
				if (substr($file, -4) == '.log') {
					$name = substr($file, 0, 4) . '-' . substr($file, 4, 2) . '-' . substr($file, 6, 2);
					if ($time > strtotime($name)) {
						@unlink($logsdir . $file);
						$count ++;
					}
				}
			}
		}
	    $this->adminMsg(lang('a-ind-32') . '(#' . $count . ')', purl('index/log'), 3, 1, 1);
	}
	
	/**
	 * 清除攻击日志
	 */
	public function clearattackAction() {
	    $time     = strtotime('-30 day');
	    $logsdir  = APP_ROOT . 'cache' . DIRECTORY_SEPARATOR . 'attack' . DIRECTORY_SEPARATOR;
		$filedata = file_list::get_file_list($logsdir);
		$count    = 0;
		if ($filedata) {
			foreach ($filedata as $file) {
				if (substr($file, -4) == '.log') {
					$name = substr($file, 0, 4) . '-' . substr($file, 4, 2) . '-' . substr($file, 6, 2);
					if ($time > strtotime($name)) {
						@unlink($logsdir . $file);
						$count ++;
					}
				}
			}
		}
	    $this->adminMsg(lang('a-ind-32') . '(#' . $count . ')', purl('index/attack'), 3, 1, 1);
	}
	
	/**
	 * 验证Email
	 */
	public function ajaxmailAction() {
	    if ($this->get('submit')) {
	        $toemail = $this->get('mail_to');
	        if (empty($toemail)) exit(lang('a-ind-33'));
	        $config  = array(
	            'SITE_MAIL_TYPE'     => (int)$this->post('mail_type'),
	            'SITE_MAIL_SERVER'   => $this->post('mail_server'),
	            'SITE_MAIL_PORT'     => (int)$this->post('mail_port'),
	            'SITE_MAIL_FROM'     => $this->post('mail_from'),
	            'SITE_MAIL_AUTH'     => $this->post('mail_auth'),
	            'SITE_MAIL_USER'     => $this->post('mail_user'),
	            'SITE_MAIL_PASSWORD' => $this->post('mail_password'),
	        );
	        mail::set($config);
	        if (mail::sendmail($toemail, lang('a-ind-34'), lang('a-ind-35'))) {
	            echo lang('a-ind-36');
	        } else {
	            echo lang('a-ind-37');
	        }
	    } else {
	        exit(lang('a-ind-38'));
	    }
	}
	
	/**
	 * 更新地图
	 */
	public function updatemapAction() {
	    $fp = @fopen(APP_ROOT . 'finecms_test.txt', 'wb');
		if (!file_exists(APP_ROOT . 'finecms_test.txt') || $fp === false) $this->adminMsg(lang('app-9', array('1'=>APP_ROOT)));
		@fclose($fp);
		if (file_exists(APP_ROOT . 'finecms_test.txt')) unlink(APP_ROOT . 'finecms_test.txt');
	    $data = sitemap_xml();
	    $this->adminMsg(lang('a-ind-39') . '<br><a href="' . SITE_URL . 'baidunews.xml" target="_blank">' . lang('a-ind-40') . '</a>&nbsp;&nbsp;<a href="' . SITE_URL . 'sitemap.xml" target="_blank">SiteMap</a>', '', 3, 1, 1);
	}
	
	/**
	 * 更新指定缓存
	 */
	public function updatecacheAction() {
	    $appa = $this->get('ca') ? $this->get('ca') : 'cache';
	    $appc = $this->get('cc');
		$appc = ucfirst($appc) . 'Controller';
		$appa = $appa . 'Action';
		$file = CONTROLLER_DIR . 'admin' . DIRECTORY_SEPARATOR . $appc . '.php';
		if (!file_exists($file)) return false;
		App::load_file($file);
		$app  = new $appc();
		if (method_exists($appc, $appa)) $app->$appa(1);
	}
	
	/**
	 * 数据统计
	 */
	public function ajaxcountAction() {
		if ($this->get('type') == 'member') {
		    $c1 = $this->content->count('member', 'id', null);
			$c2 = $this->content->count('member', 'id', 'status=0');
			echo '$("#member_1").html("' . $c1 . '");$("#member_2").html("' . $c2 . '");';
		} elseif ($this->get('type') == 'size') {
		    $c1 = formatFileSize(count_dir_size(APP_ROOT), 2);
			$c2 = formatFileSize(count_dir_size(APP_ROOT . 'uploadfiles/'), 2);
			$c3 = formatFileSize(count_dir_size(APP_ROOT . 'cache/models/'), 2);
			$c4 = formatFileSize(count_dir_size(APP_ROOT . 'cache/data/'), 2);
			echo '$("#c_1").html("' . $c1 . '");$("#c_2").html("' . $c2 . '");$("#c_3").html("' . $c3 . '");$("#c_4").html("' . $c4 . '");';
		} elseif ($this->get('type') == 'install') {
		    $ck = $this->cache->get('install');
			echo empty($ck) ? '' : "window.top.art.dialog({title:'" . lang('a-ind-41') . "',fixed:true, content: '<a href=" . url('admin/index/cache') . " target=right>" . lang('a-ind-42') . "</a>'});";
		} else {
		    $id = (int)$this->get('modelid');
			$c1 = $this->content->count('content', 'id', 'modelid=' . $id);
			$c2 = $this->content->count('content', 'id', 'modelid=' . $id . ' AND status=0');
			echo '$("#m_' . $id . '_1").html("' . $c1 . '");$("#m_' . $id . '_2").html("' . $c2 . '");';
		}
		exit;
	}
	
	/**
	 * 空格填补
	 */
	private function setspace($var) {
	    $len = strlen($var) + 2;
	    $cha = 25 - $len;
	    $str = '';
	    for ($i = 0; $i < $cha; $i ++) $str .= ' ';
	    return $str;
	}
}