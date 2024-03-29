<?php

class TagController extends Admin {
    
    protected $tag;
    
    public function __construct() {
		parent::__construct();
		$this->tag = $this->model('tag');
	}
	
	public function indexAction() {
	    if ($this->post('submit_del') && $this->post('form') == 'del') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_') !== false) {
	                $id = (int)str_replace('del_', '', $var);
	                $this->delAction($id, 1);
	            }
	        }
			$this->adminMsg($this->getCacheCode('tag') . lang('success'), url('admin/tag/'), 3, 1, 1);
	    } elseif ($this->post('submit_update') && $this->post('form') == 'update') {
	        $data = $this->post('data');
			if (empty($data)) $this->adminMsg(lang('a-tag-0'));
			foreach ($data as $id=>$t) {
			    $this->tag->update($t, 'id=' . $id);
			}
			$this->adminMsg($this->getCacheCode('tag') . lang('success'), url('admin/tag/'), 3, 1, 1);
	    }
	    $page     = (int)$this->get('page');
		$page     = (!$page) ? 1 : $page;
	    $pagelist = $this->instance('pagelist');
		$pagelist->loadconfig();
	    if ($this->post('submit')) $kw = $this->post('kw');
	    $where    = null;
	    $kw       = $kw ? $kw : $this->get('kw');
	    if ($kw) $where = "name like '%" . $kw . "%'";
	    $total    = $this->tag->count('tag', null, $where);
	    $pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
	    $url      = url('admin/tag/index', array('page'=>'{page}', 'kw'=>$kw));
	    $select   = $this->tag->page_limit($page, $pagesize)->order(array('listorder DESC','id DESC'));
	    if ($where) $select->where($where);
	    $data     = $select->select();
	    $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
	    $this->view->assign(array(
	        'list'     => $data,
	        'pagelist' => $pagelist,
	    ));
	    $this->view->display('admin/tag_list');
	}
	
	public function addAction() {
	    if ($this->post('submit')) {
	        $data = $this->post('data');
	        if (empty($data['name']))   $this->adminMsg(lang('a-tag-1'));
			if (empty($data['letter'])) $data['letter'] = word2pinyin($data['letter']);
	        $this->tag->insert($data);
	        $this->adminMsg($this->getCacheCode('tag') . lang('success'), url('admin/tag'), 3, 1, 1);
	    }
	    $this->view->display('admin/tag_add');
	}
	
    public function editAction() {
        $id   = $this->get('id');
        $data = $this->tag->find($id);
        if (empty($data)) $this->adminMsg(lang('a-tag-2'));
	    if ($this->post('submit')) {
	        $data = $this->post('data');
	        if (empty($data['name'])) $this->adminMsg(lang('a-tag-1'));
	        if (empty($data['letter'])) $data['letter'] = word2pinyin($data['letter']);
	        $this->tag->update($data, 'id=' . $id);
	        $this->adminMsg($this->getCacheCode('tag') . lang('success'), url('admin/tag'), 3, 1, 1);
	    }
	    $this->view->assign('data', $data);
	    $this->view->display('admin/tag_add');
	}
	
    public function delAction($id=0, $all=0) {
        if (!auth::check($this->roleid, 'tag-del', 'admin')) $this->adminMsg(lang('a-com-0', array('1'=>'tag', '2'=>'del')));
	    $all = $all ? $all : $this->get('all');
		$id  = $id  ? $id  : $this->get('id');
	    $this->tag->delete('id=' . $id);
	    $all or $this->adminMsg($this->getCacheCode('tag') . lang('success'), url('admin/tag/index'), 3, 1, 1);
	}
	
	public function importAction() {
	    if ($this->post('submit')) {
	        $i    = $j = $k = 0;
	        $file = $_FILES['txt'];
	        if ($file['type'] != 'text/plain') $this->adminMsg(lang('a-tag-3', array('1'=>$file['type'])));
	        if ($file['error']) $this->adminMsg(lang('a-tag-4'));
	        if (!file_exists($file['tmp_name'])) $this->adminMsg(lang('a-tag-5'));
	        $data = file_get_contents($file['tmp_name']);
	        $data = explode(PHP_EOL, $data);
	        foreach ($data as $t) {
	            $name = trim($t);
	            if ($name) {
	                $row = $this->tag->getOne('name=?', $name);
	                if (empty($row)) {
	                    $id = $this->tag->insert(array('name'=>$name, 'letter'=>word2pinyin($name)));
	                    if ($id) $i++;
	                } else {
	                    $j ++;
	                }
	            } else {
	                $k ++;
	            }
	        }
	        $this->adminMsg($this->getCacheCode('tag') . lang('a-tag-6', array('1'=>$i, '2'=>$k, '3'=>$j)), url('admin/tag/index'), 3, 1, 1);
	    }
	    $this->view->display('admin/tag_import');
	}
	
	public function cacheAction($show=0) {
	    $data = $this->tag->from(null, 'name,letter')->order('listorder DESC, id DESC')->select();
		$list = array();
		if ($data) {
		    $cfg = Controller::load_config('config');
		    foreach ($data as $t) {
			    $tagurl = SITE_PATH . $cfg['SITE_TAG_URL'];
                $tagurl = $cfg['SITE_TAG_URL'] ? str_replace('{tag}', $t['letter'], $tagurl) : url('tag/list', array('kw'=>$t['letter']));
			    $list[$t['name']] = array('name'=>$t['name'], 'url'=>$tagurl);
			}
		}
	    $this->cache->set('tag', $list);
	    $show or $this->adminMsg(lang('a-update'), url('admin/tag'), 3, 1, 1);
	}
}