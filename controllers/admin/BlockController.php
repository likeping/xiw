<?php

class BlockController extends Admin {
    
    private $block;
    private $type;
    
    public function __construct() {
		parent::__construct();
		$this->block = $this->model('block');
		$this->type  = array(1=>lang('a-blo-0'), 2=>lang('a-blo-1'), 3=>lang('a-blo-2'));
		$this->view->assign('type', $this->type);
	}
    
    public function indexAction() {
		if ($this->post('submit_del')) {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $id = (int)str_replace('del_', '', $var);
	                $this->delAction($id, 1);
	            }
	        }
			$this->adminMsg($this->getCacheCode('block') . lang('success'), url('admin/block/'), 3, 1, 1);
	    }
	    $page     = (int)$this->get('page');
		$page     = (!$page) ? 1 : $page;
		//分页配置
	    $pagelist = $this->instance('pagelist');
		$pagelist->loadconfig();
	    $total    = $this->block->count('block');
	    $pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
	    $url      = url('admin/block/index', array('page'=>'{page}'));
	    $data     = $this->block->page_limit($page, $pagesize)->order(array('id DESC'))->select();
	    $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
	    $this->view->assign(array(
	        'list'     => $data,
	        'pagelist' => $pagelist,
	    ));
	    $this->view->display('admin/block_list');
    }
    
    public function addAction() {
        if ($this->post('submit')) {
            $data = $this->post('data');
            if (empty($data['type'])) $this->adminMsg(lang('a-blo-3'));
            $data['content'] = $this->post('content_' . $data['type']);
            if (empty($data['name']) || empty($data['content'])) $this->adminMsg(lang('a-blo-4'));
            $this->block->insert($data);
            $this->adminMsg($this->getCacheCode('block') . lang('success'), url('admin/block'), 3, 1, 1);
        }
        $this->view->display('admin/block_add');
    }
    
    public function editAction() {
        $id   = $this->get('id');
        $data = $this->block->find($id);
        if (empty($data)) $this->adminMsg(lang('a-blo-5'));
        if ($this->post('submit')) {
            unset($data);
            $data = $this->post('data');
            if (empty($data['type'])) $this->adminMsg(lang('a-blo-3'));
            $data['content'] = $this->post('content_' . $data['type']);
            if (empty($data['name']) || empty($data['content'])) $this->adminMsg(lang('a-blo-4'));
            $this->block->update($data, 'id=' . $id);
            $this->adminMsg($this->getCacheCode('block') . lang('success'), url('admin/block'), 3, 1, 1);
        }
        $this->view->assign('data', $data);
        $this->view->display('admin/block_add');
    }
    
    public function delAction($id=0, $all=0) {
        if (!auth::check($this->roleid, 'block-del', 'admin')) $this->adminMsg(lang('a-com-0', array('1'=>'block', '2'=>'del')));
	    $id  = $id  ? $id  : $this->get('id');
	    $all = $all ? $all : $this->get('all');
	    $this->block->delete('id=' . $id);
	    $all or $this->adminMsg($this->getCacheCode('block') . lang('success'), url('admin/block/index'), 3, 1, 1);
	}
    
    public function cacheAction($show=0) {
	    $list = $this->block->findAll();
	    $data = array();
	    foreach ($list as $t) {
	        $data[$t['id']] = $t;
	    }
	    $this->cache->set('block', $data);
	    $show or $this->adminMsg(lang('a-update'), '', 3, 1, 1);
	}
    
    /**
	 * 加载调用代码
	 */
	public function ajaxviewAction() {
	    $id   = $this->get('id');
	    $data = $this->block->find($id);
	    if (empty($data)) exit(lang('a-blo-5'));
	    $msg  = "<textarea id='block_" . $id . "' style='font-size:12px;width:100%;height:50px;overflow:hidden;'>";
	    $msg .= "<!--" . $data['name'] . "-->{block(" . $id . ")}<!--" . $data['name'] . "-->";
	    $msg .= "</textarea>";
	    echo $msg;
	}
}