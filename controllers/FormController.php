<?php

class FormController extends Common {

    protected $model;
	protected $form;
	protected $modelid;
	
	public function __construct() {
        parent::__construct();
		$model   = $this->cache->get('formmodel');
		$modelid = (int)$this->get('modelid');
		if (empty($modelid)) $this->msg(lang('for-0'));
		$this->model   = $model[$modelid];
		if (empty($this->model)) $this->msg(lang('for-1', array('1'=>$modelid)));
		$this->form    = $this->model($this->model['tablename']);
		$this->modelid = $modelid;
		$this->view->assign(array(
		    'form_name' => $this->model['modelname'],
			'table'     => $this->model['tablename'],
			'modelid'   => $this->modelid,
		));
	}
	
	/*
	 * 提交页面
	 */
	public function postAction() {
	    $cid       = (int)$this->get('cid');
		$joinmodel = $this->cache->get('joinmodel');
		$joindata  = isset($joinmodel[$this->model['joinid']]) ? $joinmodel[$this->model['joinid']] : null;
		$backurl   = null;
		if ($joindata && empty($cid)) $this->msg(lang('for-2', array('1'=>$joindata['modelname'])), '', 1);
		if ($joindata) { //关联内容数据
		    $cdata = null;
			if ($joindata['typeid'] == 1) {
			    $cdata   = $this->content->getOne('id=' . $cid . ' AND modelid=' . $this->model['joinid']);
				$backurl = isset($cdata['url']) ? $cdata['url'] : null;
			} elseif ($joindata['typeid'] == 2) {
			    $cdata = $this->member->getOne('id=' . $cid . ' AND modelid=' . $this->model['joinid']);
			}
			if (empty($cdata)) $this->msg(lang('for-3', array('1'=>$joindata['modelname'], '2'=>$cid)), '', 1);
		}
	    if ($this->isPostForm()) {
		    if ($this->model['setting']['form']['code'] && !$this->checkCode($this->post('code'))) $this->msg(lang('for-4'), '', 1);
			if ($this->model['setting']['form']['post'] && empty($this->memberinfo))               $this->msg(lang('for-5'), '', 1);
			if ($this->model['setting']['form']['num']  && $this->check_num($joindata, $cid))      $this->msg(lang('for-6'), '', 1);
			if ($this->model['setting']['form']['ip']   && $this->check_ip($joindata, $cid))       $this->msg(lang('for-7', array('1'=>$this->model['setting']['form']['ip'])), '', 1);
			$data = $this->post('data');
			$this->checkFields($this->model['fields'], $data, 3);
			$data['cid']      = $cid;
			$data['ip']       = client::get_user_ip();
			$data['userid']   = empty($this->memberinfo) ? 0  : $this->memberinfo['id'];
			$data['username'] = empty($this->memberinfo) ? '' : $this->memberinfo['username'];
			$data['inputtime']= $data['updatetime'] = time();
			$data['status']   = empty($this->model['setting']['form']['check']) ? 1 : 0;
			//数组转化为字符
			foreach ($data as $i=>$t) {
				if (is_array($t)) $data[$i] = array2string($t);
			}
			if ($this->form->insert($data)) {
			    $this->msg($data['status'] ? lang('for-8') : lang('for-9'), $backurl, 1);
			} else {
			    $this->msg(lang('for-10'), '', 1);
			}
		}
	    $this->view->assign(array(
	        'meta_title'       => $this->model['setting']['form']['meta_title'],
	        'meta_keywords'    => $this->model['setting']['form']['meta_keywords'], 
	        'meta_description' => $this->model['setting']['form']['meta_description'],
			'fields'           => $this->getFields($this->model['fields'], null, $this->model['setting']['form']['field']),
			'code'             => $this->model['setting']['form']['code'],
			'cdata'            => $cdata,
			'joindata'         => $joindata,
	    ));
		$this->view->display(substr($this->model['categorytpl'], 0, -5));
	}
	
	/*
	 * 列表页面
	 */
	public function listAction() {
	    $cid  = $this->get('cid')  ? (int)$this->get('cid')  : '';
		$page = $this->get('page') ? $this->get('page') : 1;
	    $this->view->assign(array(
	        'meta_title'       => $this->model['setting']['form']['meta_title'],
	        'meta_keywords'    => $this->model['setting']['form']['meta_keywords'], 
	        'meta_description' => $this->model['setting']['form']['meta_description'],
			'page'             => $page,
			'cid'              => $cid,
			'pagesize'         => $this->model['setting']['form']['pagesize'],
			'urlrule'          => url('form/list', array('modelid'=>$this->modelid, 'cid'=>$cid, 'page'=>'_page_')),
	    ));
		$this->view->display(substr($this->model['listtpl'], 0, -5));
	}
	
	/*
	 * 显示页面
	 */
	public function showAction() {
	    $id   = (int)$this->get('id');
		if (empty($id)) $this->msg(lang('for-11'));
		$data = $this->form->find($id);
		if (empty($data)) $this->msg(lang('for-12'));
		if (!$this->userShow($data)) $this->msg(lang('for-13', array('1'=>$id)));
	    if (isset($this->model['fields']) && $this->model['fields']) $data = $this->getFieldData($this->model, $data);
		$this->view->assign($data);
	    $this->view->assign(array(
	        'meta_title'       => $this->model['setting']['form']['meta_title'],
	        'meta_keywords'    => $this->model['setting']['form']['meta_keywords'], 
	        'meta_description' => $this->model['setting']['form']['meta_description'],
	    ));
		$this->view->display(substr($this->model['showtpl'], 0, -5));
	}
	
	/*
	 * 同一会员（游客）提交一次
	 */
	private function check_num($joindata, $cid) {
		if (empty($this->memberinfo)) {
		    $select = $this->form->from(null, 'id');
			$select->where('ip=?', client::get_user_ip());
			$select->where('userid=0 AND username=?', '');
			if ($joindata && $cid) $select->where('cid=' . $cid);
			$data   = $select->select(false);
			if ($data) return true;
		} else {
		    $select = $this->form->from(null, 'id');
			$select->where('userid=?', $this->memberinfo['id']);
			$select->where('username=?', $this->memberinfo['username']);
			if ($joindata && $cid) $select->where('cid=' . $cid);
			$data   = $select->select(false);
			if ($data) return true;
		}
		return false;
	}
	
	/*
	 * 同一IP提交间隔
	 */
	private function check_ip($joindata, $cid) {
	    $time   = $this->model['setting']['form']['ip'] * 60; //秒
		$select = $this->form->from(null, 'id,inputtime');
		$select->where('ip=?', client::get_user_ip());
		if ($joindata && $cid) $select->where('cid=' . $cid);
		$select->order('inputtime DESC');
		$data   = $select->select(false);
		if (empty($data)) return false;
		if (time() - $data['inputtime'] < $time) return true;
		return false;
	}
	
}