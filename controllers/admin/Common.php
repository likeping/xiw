<?php

class Admin extends Common {
	
	protected $roleid;
	protected $user;
	protected $userinfo;

	public function __construct() {
		parent::__construct();
		$this->user = $this->model('user');
		$this->isAdminLogin();
        if (!auth::check($this->roleid, $this->controller . '-' . $this->action, $this->namespace)) {
            $this->adminMsg(lang('a-com-0', array('1'=>$this->controller, '2'=>$this->action)));
        }
        $this->view->assign('userinfo', $this->userinfo);
        $this->adminLog();
	}
	
	/**
     * 系统默认菜单
     */
    protected function sysMenu() {
		$menu = $this->load_config('admin.menu');
		$data = $this->cache->get('plugin');
		if ($data) {
			foreach ($data as $t) {
				$id   = $t['pluginid'];
				$url  = $t['typeid'] ? url($t['dir'].'/admin/index/') : url('admin/plugin/set', array('pluginid'=>$id));
				$menu['list'][5]['a-men-61']['5' . $id] = array('name'=>$t['name'], 'url'=> $url, 'sys'=>1);
			}
		}
		$model = $this->cache->get('model');
		if ($model) {
		    foreach ($model as $t) {
				$id   = $t['modelid'];
				$url  = url('admin/content/', array('modelid'=>$id));
				$menu['list'][2]['a-men-29']['28' . $id] = array('name'=>$t['modelname'] . lang('a-com-1'), 'url'=> $url, 'clz'=>1, 'sys'=>1);
			}
			krsort($menu['list'][2]['a-men-29']);
		}
		$form  = $this->cache->get('formmodel');
		if ($form) {
		    foreach ($form as $t) {
				$id   = $t['modelid'];
				$url  = url('admin/form/list', array('modelid'=>$id));
				$menu['list'][7]['a-men-59']['7' . $id] = array('name'=>$t['modelname'] . lang('a-com-1'), 'url'=>$url, 'sys'=>1);
			}
		}
		return $menu;
    }
	
	/**
     * 后台登陆检查
     */
    protected function isAdminLogin($namespace='admin', $controller=null) {
	    if ($this->namespace != $namespace) return false;
        if ($controller && $this->controller != $controller) return false;
        if ($this->namespace == 'admin' && $this->controller == 'login') return false;
        if ($this->session->is_set('user_id')) {
            $userid = $this->session->get('user_id');
            $this->userinfo = $this->user->userinfo($userid);
            if ($this->userinfo) {
               $this->roleid = $this->userinfo['roleid'];
               return false;
            }
        }
		$url = $this->namespace == 'admin' && isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != 's=' . ADMIN_NAMESPACE ? url('admin/login', array('url'=> urlencode(SITE_PATH . ENTRY_SCRIPT_NAME . '?' . $_SERVER['QUERY_STRING']))) : url('admin/login');
        $this->redirect($url);
    }
    
    /**
     * 指定用户组的操作菜单
     */
    protected function optionMenu($roleid=0) {
        $roleid   = $roleid ? $roleid : $this->roleid;
        $menu     = $this->sysMenu();
		//加载用户自定义菜单
		$usermenu = string2array($this->userinfo['usermenu']);
        if ($roleid == 1) {
			if (!empty($usermenu)) {
				foreach ($usermenu as $k=>$t) {
					$menu['list'][0]['a-men-9']['19' . $k] = $t;
				}
			}
			return $menu;
		}
        $data     = array();
        $menu['list'][0] = array(
	        'a-men-62' => array(
	            01 => array('name'=>'a-men-8',  'url'=> url('admin/index/main'), 'option'=>''),
	            02 => array('name'=>'a-men-63', 'url'=> url('admin/user/ajaxedit')),
	        ),
	        'a-men-10' => array(
	            05 => array('name'=>CMS_NAME . ' ' . CMS_VERSION, 'sys'=>1)
	        ),
	    );
		if (!empty($usermenu)) {
			foreach ($usermenu as $k=>$t) {
				$menu['list'][0]['a-men-62']['19' . $k] = $t;
			}
		}
	    foreach ($menu['top'] as $id=>$t) {
	        if ($id == 0) continue;
	        if (!$this->checkUserAuth($t['option'], $roleid)) unset($menu['top'][$id]);
	    }
	    foreach ($menu['list'] as $id=>$t) {
	        if ($id == 0) continue;
	        foreach ($t as $oid=>$v) {
	            foreach ($v as $iid=>$r) {
	                //内菜单控制
	                if ($r['option'] && !$this->checkUserAuth(array($r['option']), $roleid)) {
	                    if ($r['url']==$menu['top'][$id]['url']) $menu['top'][$id]['url'] = url('admin/index/main');
	                    unset($menu['list'][$id][$oid][$iid]);
	                }
	            }
	            //如果子菜单全部被删除
	            if (empty($menu['list'][$id][$oid])) unset($menu['list'][$id][$oid]);
	        }
	    }
        return $menu;
    }
    
    /**
     * 验证角色是否对指定菜单有操作权限
     */
    protected function checkUserAuth($option, $roleid=0) {
        $roleid    = $roleid ? $roleid : $this->roleid;
        $data_role = require CONFIG_DIR . 'auth.role.ini.php';
        $role      = $data_role[$roleid];
        if (!$role) return false;
        if (!is_array($option)) $option = array($option);
        foreach ($role as $t) {
            if (in_array($t, $option)) return true;
        }
        return false;
    }
	
	/**
     * 后台操作日志记录
     */
    protected function adminLog() {
        if ($this->namespace != 'admin') return false;
		if (!isset($_POST) || empty($_POST)) return false;
        //跳过不要记录的操作
        if ($this->site['SITE_ADMINLOG'] == false) return false;
        $skip    = require CONFIG_DIR . 'auth.skip.ini.php';
	    if (stripos($this->action, 'ajax') !== false) return false;
	    $skip    = $skip['admin'];
	    $skip[]  = 'index-log';
	    if (in_array($this->controller, $skip)) {
	        return false;
	    } elseif (in_array($this->controller . '-' . $this->action, $skip)) {
	        return false;
	    }
	    //记录操作日志
	    $options = require CONFIG_DIR . 'auth.option.ini.php';
	    $option  = $options[$this->controller];
	    if (empty($option)) return false;
	    $now     = $option['option'][$this->action];
	    $ip      = client::get_user_ip();
        if (SYS_DOMAIN) $_SERVER['REQUEST_URI'] = str_replace('/' . SYS_DOMAIN, '', $_SERVER['REQUEST_URI']);
		$pathurl = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : $_SERVER['REQUEST_URI'];
		$options = lang($option['name']) . ' - ' . lang($option['option'][$this->action]);
		if ($this->post('submit')) {
		    $options  .= ' - ' . lang('a-com-2');
		} elseif (($this->post('submit_order'))) {
		     $options .= ' - ' . lang('a-com-3');
		} elseif (($this->post('submit_del'))) {
		     $options .= ' - ' . lang('a-com-4');
		} elseif (($this->post('submit_status_1'))) {
		     $options .= ' - ' . lang('a-com-5');
		} elseif (($this->post('submit_status_0'))) {
		     $options .= ' - ' . lang('a-com-6');
		} elseif (($this->post('submit_status_2'))) {
		     $options .= ' - ' . lang('a-com-7');
		} elseif (($this->post('submit_status_3'))) {
		     $options .= ' - ' . lang('a-com-8');
		} elseif (($this->post('submit_move'))) {
		     $options .= ' - ' . lang('a-com-9');
		} elseif (($this->post('delete'))) {
		     $options .= ' - ' . lang('a-com-10');
		}
	    $data = array(
	        'controller' => $this->controller,
	        'action'     => $this->action,
	        'options'    => $options,
	        'param'      => $pathurl,
	        'ip'         => $ip,
	        'userid'     => $this->userinfo['userid'],
	        'username'   => $this->userinfo['username'],
	        'optiontime' => time(),
	    );
		$dir     = APP_ROOT . 'cache' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
		$file    = $dir . date('Ymd') . '.log';
		if (!is_dir($dir)) mkdir($dir, 0777);
		$content = file_exists($file) ? file_get_contents($file) : '';
		$content = serialize($data) . PHP_EOL . $content;
		file_put_contents($file, $content, LOCK_EX);
    }
	
    /**
     * 删除目录及文件
     */
    protected function delDir($filename) {
        if (empty($filename)) return false;
        if (is_file($filename) && file_exists($filename)) {
            unlink($filename);
        } else if ($filename != '.' && $filename!='..' && is_dir($filename)) {
            $dirs = scandir($filename);
            foreach ($dirs as $file) {
                if ($file != '.' && $file != '..') $this->delDir($filename . '/' . $file);
            }
            rmdir($filename);
        }
    }
	
	/**
	 * 生成栏目html
	 */
	protected function createCat($cat, $page=0) {
	    if ($cat['typeid'] == 3) return false;
		if ($cat['setting']['url']['use'] == 0 || $cat['setting']['url']['tohtml'] == 0 || $cat['setting']['url']['list'] == '') return false;
	    $url       = substr($this->getCaturl($cat, $page), strlen(self::get_base_url())); //去掉域名部分
	    if (substr($url, -5) != '.html') { 
			$file  = 'index.html'; //文件名 
			$dir   = $url; //目录
		} else {
			$file  = basename($url);
			$dir   = str_replace($file, '', $url);
		}
		$this->mkdirs($dir);
		$dir       = substr($dir, -1) == '/' ? substr($dir, 0, -1) : $dir;
		$htmlfile  = $dir ? $dir . '/' . $file : $file;
		ob_start();
		$this->view->setTheme(true);
		$_GET['catid'] = $cat['catid'];
		$_GET['page']  = $page;
	    $class    = 'ContentController';
	    $action   = 'listAction';
	    App::load_file(CONTROLLER_DIR . $class . '.php');
	    $app      = new $class();
		$app->$action();
		$this->view->setTheme(false);
		if (!file_put_contents($htmlfile, ob_get_clean(), LOCK_EX)) $this->adminMsg(lang('a-com-11', array('1'=>$htmlfile)));
		$htmlfiles   = $this->cache->get('html_files');
		$htmlfiles[] = $htmlfile;
		if (empty($page) || $page == 1) {
		    $onefile = str_replace('{page}', 1, substr($this->getCaturl($cat, '{page}'), strlen(self::get_base_url())));
			@copy($htmlfile, $onefile);
			$htmlfiles[] = $onefile;
		}
		$this->cache->set('html_files', $htmlfiles);
		return true;
	}
	
	/**
	 * 生成内容html
	 */
	protected function createShow($id, $page=1) {
	    $data  = $this->content->find($id);
	    if (empty($data)) return false;
		ob_start();
	    $catid = $data['catid'];
	    $cat   = $this->cats[$catid];
		if ($cat['setting']['url']['use'] == 0 || $cat['setting']['url']['tohtml'] == 0  || $cat['setting']['url']['show'] == '') return false;
	    $table = $this->model($cat['tablename']);
	    $_data = $table->find($id);
	    $data  = array_merge($data, $_data);
	    $model = $this->cache->get('model');
	    $data  = $this->getFieldData($model[$cat['modelid']], $data);
	    if (isset($data['content']) && strpos($data['content'], '{-page-}') !== false) {
			$content  = explode('{-page-}', $data['content']);
			$pageid   = count($content) >= $page ? ($page - 1) : (count($content) - 1);
			$data['content'] = $content[$pageid];
			$page_id  = 1;
			$pagelist = array();
			foreach ($content as $t) {
				$pagelist[$page_id] = getUrl($data, $page_id);
				$page_id ++ ;
			}
			$this->view->assign('contentpage', $pagelist);
		}
	    $url       = substr($this->getUrl($data, $page), strlen(self::get_base_url())); //去掉域名部分
	    if (substr($url, -5) != '.html') { 
			$file  = 'index.html'; //文件名 
			$dir   = $url; //目录
		} else {
			$file  = basename($url);
			$dir   = str_replace($file, '', $url);
		}
		$this->mkdirs($dir);
		$dir       = substr($dir, -1) == '/' ? substr($dir, 0, -1) : $dir;
		$htmlfile  = $dir ? $dir . '/' . $file : $file;
		if ($data['status'] != 1) {
		    @unlink($htmlfile);
			if (isset($pagelist) && is_array($pagelist)) {
		        foreach ($pagelist as $p=>$u) {
				    $file = str_replace(self::get_base_url(), '', $u);
				    @unlink($file);
				}
			}
			return false;
		}
	    $data['content'] = relatedlink($data['content']);
		$prev_page = $this->content->getOne("`catid` =$catid AND `id`<$id AND `status`=1 ORDER BY `updatetime` DESC");
		$next_page = $this->content->getOne("`catid` =$catid AND `id`>$id AND `status`=1 ORDER BY `updatetime` DESC");
	    $seo       = showSeo($data, $page);
	    $this->view->assign(array(
		    'cats'      => $this->cats,
	        'cat'       => $cat,
	        'page'      => $page,
	        'prev_page' => $prev_page,
	        'next_page' => $next_page,
	        'pageurl'   => urlencode(getUrl($data, '{page}'))
	    ));
	    $this->view->assign($data);
	    $this->view->assign($seo);
		$this->view->setTheme(true);
	    $this->view->display(substr($cat['showtpl'], 0, -5));
		$this->view->setTheme(false);
		if (!file_put_contents($htmlfile, ob_get_clean(), LOCK_EX)) $this->adminMsg(lang('a-com-11', array('1'=>$htmlfile)));
		$htmlfiles   = $this->cache->get('html_files');
		$htmlfiles[] = $htmlfile;
		$this->cache->set('html_files', $htmlfiles);
		if (isset($pagelist) && is_array($pagelist) && isset($pagelist[$page+1])) $this->createShow($id, $page+1);
		return true;
	}
	
	/**
	 * 获取更新缓存JS代码
	 */
	protected function getCacheCode($c, $a='cache') {
		return '<script type="text/javascript" src="' . url('admin/index/updatecache', array('cc'=>$c, 'ca'=>$a)) . '"></script>';
	}
	
}