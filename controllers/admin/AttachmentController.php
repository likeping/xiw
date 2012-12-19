<?php

class AttachmentController extends Admin {
    
    protected $dir;
    
    public function __construct() {
		parent::__construct();
		$this->dir = 'uploadfiles/';
	}
    
    public function indexAction() {
        $iframe = $this->get('iframe') ? 1 : 0;
        $dir    = $this->get('dir') ? base64_decode($this->get('dir')) : '';
        $dir    = substr($dir, 0, 1) == '/' ? substr($dir, 1) : $dir;
        $dir    = str_replace('//', '/', $dir);
		if (strpos($dir, '../') !== false) $this->adminMsg(lang('m-con-20'));
        $data   = file_list::get_file_list($this->dir . $dir);
        $list   = array();
        if ($data) {
            foreach ($data as $t) {
                if ($t == 'index.html') continue;
                $path = $dir . $t . '/';
                $ext  = is_dir($this->dir . $path) ? 'dir' : strtolower(trim(substr(strrchr($t, '.'), 1, 10)));
                $ico  = file_exists(basename(VIEW_DIR) . '/admin/images/ext/' . $ext . '.gif') ? $ext . '.gif' : $ext . '.png';
                $fileinfo = array();
                if (is_file($this->dir . $dir . $t)) {
                    $file = $this->dir . $dir . $t;
                    $fileinfo = array(
                        'path' => $file,
                        'time' => date(TIME_FORMAT, filemtime($file)),
                        'size' => round(filesize($file)/1024/1024, 2) . 'MB',
                        'ext'  => $ext,
                    );
                }
                $list[] = array(
                    'name'     => $t, 
                    'dir'      => base64_encode($path), 
                    'path'     => $this->dir . $path, 
                    'ico'      => $ico,
                    'isimg'    => in_array($ext, array('gif','jpg','png','jpeg','bmp')) ? 1 : 0,
                    'isdir'    => is_dir($this->dir . $path) ? 1 : 0, 
                    'fileinfo' => $fileinfo,
                    'url'      => is_dir($this->dir . $path) ? url('admin/attachment/index', array('dir'=>base64_encode($path), 'iframe'=>$iframe)) : '',
                );
            }
        }
        $this->view->assign(array(
            'dir'    => $this->dir . $dir,
            'istop'  => $dir ? 1 : 0,
            'pdir'   => url('admin/attachment/index', array('dir'=>base64_encode(str_replace(basename($dir), '', $dir)), 'iframe'=>$iframe)),
            'list'   => $list,
            'iframe' => $iframe,
        ));
        $this->view->display('admin/attachment_list');
    }
    
    public function delAction() {
        $dir  = base64_decode($this->get('name'));
        $name = $this->dir . $dir;
        $name = substr($name, -1) == '/' ? substr($name, 0, -1) : $name;
		if (strpos($name, '../') !== false) $this->adminMsg(lang('m-con-20'));
        if ($this->dir == $name) $this->adminMsg(lang('a-att-0'));
        if ($this->dir == $name . '/') $this->adminMsg(lang('a-att-0'));
        if (!file_exists($name)) $this->adminMsg(lang('a-att-1', array('1'=>$name)));
        if (is_dir($name)) {
            //删除目录
            $this->delDir($name);
            $this->adminMsg(lang('a-att-2'), url('admin/attachment/index', array('dir'=>base64_encode(str_replace(basename($dir), '', $dir)))), 3, 1, 1);
        }
        if (is_file($name)) {
            //删除文件
            unlink($name);
            $this->adminMsg(lang('a-att-3'), url('admin/attachment/index', array('dir'=>base64_encode(str_replace(basename($dir), '', $dir)))), 3, 1, 1);
        }
    }
}