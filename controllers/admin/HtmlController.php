<?php

class HtmlController extends Admin {
    
    protected $content;
    protected $category;
    protected $tree;
    
    public function __construct() {
		parent::__construct();
		$this->content  = $this->model('content');
		$this->category = $this->cache->get('category');
		$this->tree     = $this->instance('tree');
		$ck_ob_function = function_exists('ob_start') ? 0 : 1;
		$this->tree->config(array('id'=>'catid', 'parent_id'=>'parentid', 'name'=>'catname'));
	    $this->view->assign('check', $this->dir_mode_info());
	    $this->view->assign('ck_ob', $ck_ob_function);
	}
	
	/**
	 * 选项
	 */
	public function indexAction() {
	    $this->view->display('admin/html_list');
	}
	
    /**
	 * 栏目页生成静态
	 */
	public function categoryAction() {
	    if ($this->isPostForm()) {
			$this->cache->delete('html_cats');
		    $catid  = $this->post('catid');
		    $isall  = $catid ? 0 : 1;
			$submit = 1;
		}
		$submit = $submit ? $submit : (int)$this->get('submit');
		if ($submit) {
			$catid = isset($catid) ? $catid : (int)$this->get('catid');
			$isall = isset($isall) ? $isall : (int)$this->get('isall');
			$page  = $this->get('page') ? $this->get('page') : 1;
			if ($catid && $isall == 0) {
				$cats = $this->cache->get('html_cats');
				$key  = (int)$this->get('key');
				if ($cats == false) {
				    $cat  = $this->category[$catid];
					$cats = explode(',', $cat['arrchilds']);
					$this->cache->set('html_cats', $cats);
					$key  = 0;
				}
				if (isset($cats[$key]) && $this->category[$cats[$key]]) {
					$this->toCategory($cats[$key], $page, 0, $key);
				} else {
				    $this->cache->delete('html_cats');
				    $this->adminMsg(lang('a-con-107'), '', 0, 1, 1);
				}
			} else {
				if (empty($catid)) {
				    $cats  = $this->category;
					$fcat  = array_shift($cats);
					$catid = $fcat['catid'];
				}
				if (isset($this->category[$catid])) {
					$this->toCategory($catid, $page, 1, 0);
				} else {
				    $this->adminMsg(lang('a-con-107'), '', 0, 1, 1);
				}
			}
		} else {
	        $this->view->assign('category_select', $this->tree->get_tree($this->category, 0));
	        $this->view->display('admin/html_create');
		}
	}

    /**
	 * 内容页生成静态
	 */
	public function showAction() {
		if ($this->isPostForm()) {
			$this->cache->delete('html_cats');
		    $catid  = $this->post('catid');
		    $isall  = $catid ? 0 : 1;
			$submit = 1;
		}
		$submit = $submit ? $submit : (int)$this->get('submit');
		if ($submit) {
			$catid = isset($catid) ? $catid : (int)$this->get('catid');
			$isall = isset($isall) ? $isall : (int)$this->get('isall');
			$page  = $this->get('page') ? $this->get('page') : 1;
			if ($isall == 0) {
				$cats = $this->cache->get('html_cats');
				if ($cats == false) {
				    $cats = $this->category[$catid]['arrchilds'];
					$this->cache->set('html_cats', $cats);
				}
				$this->toContent($cats, $page, $isall);
			} else {
				$this->toContent(null, $page, $isall);
			}
		} else {
	        $this->view->assign('category_select', $this->tree->get_tree($this->category, 0));
	        $this->view->display('admin/html_create');
		}
	}
	
	/**
	 * 生成首页
	 */
	public function indexcAction() {
		ob_start();
		$this->view->assign(array(
	        'indexc'           => 1, 
	        'meta_title'       => $this->site['SITE_TITLE'],
	        'meta_keywords'    => $this->site['SITE_KEYWORDS'], 
	        'meta_description' => $this->site['SITE_DESCRIPTION'],
	    ));
		$this->view->setTheme(true);
		$this->view->display('index');
		$this->view->setTheme(false);
		$size = file_put_contents('index.html', ob_get_clean(), LOCK_EX);
		$this->adminMsg(lang('a-con-107') . '(' . formatFileSize($size) . ')', '', 3, 1, 1);
	}
	
	/**
	 * 清理所有静态文件
	 */
	public function clearAction() {
	    $submit = (int)$this->get('submit');
		if (empty($submit)) $this->adminMsg(lang('a-con-108'), url('admin/html/clear', array('submit'=>1)), 3, 1, 2);
	    @unlink('index.html');
		$htmlfiles = $this->cache->get('html_files');
		if (empty($htmlfiles)) $this->adminMsg(lang('a-con-109'), '', 3, 1, 1);
		$htmlfiles = array_unique($htmlfiles);
		$f = $d = 0;
		if (is_array($htmlfiles)) {
		    $dirs  = array();
		    foreach ($htmlfiles as $file) {
			    $dir = dirname($file);
			    $dirs[$dir] = 1;
				if (@unlink($file)) $f++;
			}
			foreach ($dirs as $dir=>$n) {
			    if (!in_array($dir, array('.', '/', '\\'))) {
				    $this->delDir($dir);
					$d++;
				}
			}
		}
		$this->cache->delete('html_files');
	    $this->adminMsg(lang('a-con-110', array('1'=>$d, '2'=>$f)), '', 3, 1, 1);
	}
	
	/**
	 * 生成栏目
	 */
	private function toCategory($catid, $page, $isall, $key) {
	    $cat       = $this->category[$catid];
	    $nextpage  = 1;
		$totalpage = 1;
		$nextcatid = $catid;
		if (($cat['child'] && $cat['categorytpl'] != $cat['listtpl']) || $cat['typeid'] == 2) {
			$this->createCat($cat);
		} else {
			$total     = $this->content->count('content', 'id', '`status`=1 AND `catid` IN (' . $cat['arrchilds'] . ')');
			$pagesize  = $cat['pagesize'];
			$totalpage = ceil($total/$pagesize); //该栏目的总页数
			$totalpage = $totalpage ? $totalpage : 1;
			$this->createCat($cat, $page);
			$nextpage  = $page + 1;
		}
		if ($page >= $totalpage) {
			$nextpage = 1;
			list($nextcatid, $key) = $this->nextCat($catid, $isall, $key); //跳转下一栏目
		}
		$url = url('admin/html/category', array('page'=>$nextpage, 'catid'=>$nextcatid, 'isall'=>$isall, 'key'=>$key, 'submit'=>1));
	    $this->adminMsg('【' . $cat['catname'] . '】(' . $page.'/' . $totalpage .')', $url, 0, 1, 1);
	}
	
	/**
	 * 下一栏目信息
	 */
	private function nextCat($catid, $isall, $key) {
	    if ($isall == 0) {
			$key++;
			$nextcatid = $catid;
		} else {
			$_selected = 0;
			$nextcatid = 99999999;
			foreach ($this->category as $id=>$t) {
				if ($_selected == 1) {
					$nextcatid = $id;
					break;
				}
				if ($id == $catid) $_selected = 1;
			}
		}
		return array($nextcatid, $key);
	}
	
	/**
	 * 生成内容
	 */
	private function toContent($cats, $page, $isall) {
		$total     = empty($cats) ? $this->content->count('content', 'id', '`status`=1') : $this->content->count('content', 'id', '`catid` IN(' . $cats . ') AND `status`=1');
		$pagesize  = 10;
	    $totalpage = ceil($total/$pagesize);
        if (empty($cats)) {
		    $data  = $this->content->where('`status`=1')->page_limit($page, $pagesize)->order('id ASC')->select();
		} else {
		    $data  = $this->content->page_limit($page, $pagesize)->where('`catid` IN(' . $cats . ') AND `status`=1')->order('id ASC')->select();
		}
		if (empty($data)) {
		    $this->adminMsg(lang('a-con-107'), '', 0, 1, 1);
		}
		foreach ($data as $t) {
		    $cat = $this->category[$t['catid']];
		    if ($cat['setting']['url']['use'] == 1 && $cat['setting']['url']['tohtml'] == 1 && $cat['setting']['url']['show'] != '') {
				$this->createShow($t['id']);
			}
        }
		$url = url('admin/html/show', array('page'=>$page+1, 'submit'=>1, 'isall'=>$isall));
        $this->adminMsg(lang('a-con-111') . " ($page/$totalpage)", $url, 0, 1, 1);
	}
	
	/**
	 * 目录权限检查函数
	 */
	private function dir_mode_info() {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            /* 测试文件 */
            $test_file = APP_ROOT . 'finecms_test.txt';
			/* 检查目录是否可读 */
			$dir = @opendir(APP_ROOT);
			if ($dir === false)           return lang('a-con-112'); 
			if (@readdir($dir) === false) return lang('a-con-113');
			@closedir($dir);
			/* 检查目录是否可写 */
			$fp = @fopen($test_file, 'w+');
			//如果目录中的文件创建失败，返回不可写。
			if (!file_exists($test_file) || $fp === false)           return lang('a-con-114'); 
			if (@fwrite($fp, 'directory access testing.') === false) return lang('a-con-114');
			@fclose($fp);
			@unlink($test_file);
			/* 检查目录是否可修改 */
			$fp = @fopen($test_file, 'ab+');
			if ($fp === false)                              return lang('a-con-115');
			if (@fwrite($fp, "modify test.\r\n") === false) return lang('a-con-115');
			@fclose($fp);
			@unlink($test_file);
        }
		foreach (glob(APP_ROOT . '*') as $dir) {
		   if (is_dir($dir)){
			   if (!@is_readable($dir)) return lang('a-con-113');
			   if (!@is_writable($dir)) return lang('a-con-114');
		   }
		}
        return false;
    }
	
}