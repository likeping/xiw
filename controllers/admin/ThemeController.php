<?php

class ThemeController extends Admin {
    
    protected $dir;
    
    public function __construct() {
		parent::__construct();
		$this->dir = VIEW_DIR . basename(SITE_THEME) . DIRECTORY_SEPARATOR;
	}
    
    public function indexAction() {
        $iframe = $this->get('iframe') ? 1 : 0;
        $dir    = $this->get('dir') ? base64_decode($this->get('dir')) : '';
		if (strpos($dir, '../') !== false) $this->adminMsg(lang('m-con-20'));
        $dir    = substr($dir, 0, 1) == DIRECTORY_SEPARATOR ? substr($dir, 1) : $dir;
        $dir    = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $dir);
        $data   = file_list::get_file_list($this->dir . $dir);
        $dlist  = $flist = array();
        if ($data) {
            foreach ($data as $t) {
                $path = $dir . $t . DIRECTORY_SEPARATOR;
				if (is_dir($this->dir . $path)) {  //目录
				    $ext = 'dir';
					$dlist[] = array('name'=>$t, 'dir'=>base64_encode($path), 'ico'=>ADMIN_THEME . 'images/ext/dir.gif', 'isdir'=>1, 'url'=>url('admin/theme/index', array('dir'=>base64_encode($path), 'iframe'=>$iframe)));
				} else { //文件
				    $ext = strtolower(trim(substr(strrchr($t, '.'), 1, 10)));
					if (in_array($ext, array('html', 'js', 'css'))) {
					    $ico  = ADMIN_THEME . 'images/ext/' . $ext . '.gif';
					    $flist[] = array('name'=>$t, 'dir'=>base64_encode($path), 'ico'=>$ico);
					}
				}
            }
        }
		sort($flist);
        $this->view->assign(array(
            'dir'    => $this->dir . $dir,
            'istop'  => $dir ? 1 : 0,
            'pdir'   => url('admin/theme/index', array('dir'=>base64_encode(str_replace(basename($dir), '', $dir)), 'iframe'=>$iframe)),
            'dlist'  => $dlist,
			'flist'  => $flist,
            'iframe' => $iframe,
			'cpath'  => base64_encode($dir),
			'iswrite'=> is_writable($this->dir), 
        ));
        $this->view->display('admin/theme_list');
    }
    
    public function editAction() {
        $dir  = base64_decode($this->get('dir'));
		$dir  = substr($dir, -1) == DIRECTORY_SEPARATOR ? substr($dir, 0, -1) : $dir;
		if (strpos($dir, '../') !== false) $this->adminMsg(lang('m-con-20'));
        $name = $this->dir . $dir;
		if (!is_file($name)) $this->adminMsg(lang('a-con-123', array('1'=>$name)));
		if ($this->isPostForm()) {
		    $Pdir = $this->dir == dirname($name) . DIRECTORY_SEPARATOR ? '' : str_replace($this->dir, '', dirname($name));
		    file_put_contents($name, stripslashes($_POST['file_content']), LOCK_EX);
			$this->adminMsg(lang('success'), url('admin/theme/index', array('dir'=>base64_encode($Pdir . DIRECTORY_SEPARATOR))), 3, 1, 1);
		}
        $file = file_get_contents($name);
		$this->view->assign(array(
		    'name'   => str_replace($this->dir, '', $name),
		    'file'   => $file,
			'syntax' => strtolower(trim(substr(strrchr($name, '.'), 1, 10))),
			'action' => 'edit',
			'iswrite'=> is_writable($this->dir), 
		));
		$this->view->display('admin/theme_add');
    }
	
	public function addAction() {
        $dir  = base64_decode($this->get('cpath'));
		if (strpos($dir, '../') !== false) $this->adminMsg(lang('m-con-20'));
        $name = $this->dir . $dir;
		$path = str_replace($this->dir, '', $name);
		if ($this->isPostForm()) {
		    $file = $this->post('file');
			if (strpos($file, '../') !== false) $this->adminMsg(lang('m-con-20'));
			$ext  = strtolower(trim(substr(strrchr($file, '.'), 1, 10)));
			if (!in_array($ext, array('html', 'css', 'js'))) $this->adminMsg(lang('a-con-124'));
			file_put_contents($name . $file, stripslashes($_POST['file_content']), LOCK_EX);
			$this->adminMsg(lang('success'),url('admin/theme/index', array('dir'=>base64_encode($path))), 3, 1, 1);
		}
		$this->view->assign(array(
		    'path'   => $path,
			'syntax' => 'html',
			'action' => 'add',
			'iswrite'=> is_writable($this->dir),
		));
		$this->view->display('admin/theme_add');
    }
	
	public function delAction() {
	    $dir  = base64_decode($this->get('name'));
		if (strpos($dir, '../') !== false) $this->adminMsg(lang('m-con-20'));
		$dir  = substr($dir, -1) == DIRECTORY_SEPARATOR ? substr($dir, 0, -1) : $dir;
		$name = $this->dir . $dir;
		if (!is_file($name)) $this->adminMsg(lang('a-con-123', array('1'=>$name)));
		@unlink($name);
		$Pdir = $this->dir == dirname($name) . DIRECTORY_SEPARATOR ? '' : str_replace($this->dir, '', dirname($name));
		$this->adminMsg(lang('success'),url('admin/theme/index', array('dir'=>base64_encode($Pdir . DIRECTORY_SEPARATOR))), 3, 1, 1);
	}
	
	public function cacheAction($show=0) {
	    $list = file_list::get_file_list(VIEW_DIR);
		foreach ($list as $path) {
		    $dir = APP_ROOT . 'cache/views/' . $path . '/';
		    $this->delDir($dir);
			if (!file_exists($dir)) mkdir($dir, 0777, true);
		}
	    $show or $this->adminMsg(lang('a-update'), url('admin/theme/index'), 3, 1, 1);
	}
}