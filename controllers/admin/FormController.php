<?php

class FormController extends Admin {
    
	protected $model;
	protected $cid;
	protected $table;
	protected $form;
	protected $modelid;
	protected $join;
    
    public function __construct() {
		parent::__construct();
		if ($this->action == 'index') $this->redirect(url('admin/model/index', array('typeid'=>3)));
		$formmodel     = $this->cache->get('formmodel');
		$this->cid     = (int)$this->get('cid');
		$this->modelid = (int)$this->get('modelid');
		if (empty($this->modelid)) $this->adminMsg(lang('a-for-1'));
		$this->model   = $formmodel[$this->modelid];
		if (empty($this->model)) $this->adminMsg(lang('a-for-2', array('1'=>$this->modelid)));
		$this->table   = $this->model['tablename'];
		$this->form    = $this->model($this->table);
		$joinmodel     = $this->cache->get('joinmodel');
		$this->join    = isset($joinmodel[$this->model['joinid']]) ? $joinmodel[$this->model['joinid']] : null;
		$join_info     = lang('a-for-3');
		if ($this->join) {
		   $join_info  = lang('a-for-4', array('1'=>$this->join['modelname']));
		   if ($this->join['typeid'] == 1) $join_info  = '<a href="' . url('admin/content/', array('modelid'=>$this->join['modelid'])) . '">' . lang('a-for-4', array('1'=>$this->join['modelname'])) . '</a>';
		}
	    $this->view->assign(array(
	        'cid'       => $this->cid,
			'modelid'   => $this->modelid,
			'model'     => $this->model,
			'join_info' => $join_info,
	    ));
	}
	
	/**
	 * 表单内容管理
	 */
	public function listAction() {
	    if ($this->post('submit') && $this->post('form')=='search') {
	        $kw    = $this->post('kw');
			$stype = $this->post('stype');
	    } elseif ($this->post('submit_order') && $this->post('form')=='order') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'order_')!==false) {
	                $id = (int)str_replace('order_', '', $var);
	                $this->form->update(array('listorder'=>$value), 'id=' . $id);
	            }
	        }
	    } elseif ($this->post('submit_del') && $this->post('form')=='del') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $_id = (int)str_replace('del_', '', $var);
	                $this->delAction($_id, 1);
	            }
	        }
	    } elseif ($this->post('submit_status_0') && $this->post('form')=='status_0') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $_id = (int)str_replace('del_', '', $var);
	                $this->form->update(array('status'=>0), 'id=' . $_id);
	            }
	        }
	    } elseif ($this->post('submit_status_1') && $this->post('form')=='status_1') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $_id = (int)str_replace('del_', '', $var);
	                $this->form->update(array('status'=>1), 'id=' . $_id);
	            }
	        }
	    } elseif ($this->post('submit_status_3') && $this->post('form')=='status_3') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $_id = (int)str_replace('del_', '', $var);
	                $this->form->update(array('status'=>3), 'id=' . $_id);
	            }
	        }
	    } elseif ($this->post('submit_join') && $this->post('form')=='join') {
		    $_cid = $this->post('toid');
			if ($this->join && $_cid) {
			    $jdata = $this->content->from($this->join['tablename'], 'id')->where('id=' . $_cid)->select(false);
				if (empty($jdata)) $this->adminMsg(lang('a-for-5', array('1'=>$this->join['modelname'], '2'=>$_cid)));
				foreach ($_POST as $var=>$value) {
					if (strpos($var, 'del_')!==false) {
						$_id = (int)str_replace('del_', '', $var);
						$this->form->update(array('cid'=>$_cid), 'id=' . $_id);
					}
				}
			}
	    }
	    $kw       = $kw    ? $kw    : $this->get('kw');
	    $stype    = $stype ? $stype : (int)$this->get('stype');
		$page     = $this->get('page')     ? $this->get('page') : 1;
		$status   = isset($_GET['status']) ? (int)$this->get('status') : 1;
		$userid   = (int)$this->get('userid');
	    $pagelist = $this->instance('pagelist');
		$pagelist->loadconfig();
	    $where    = '`status`=' . $status;
		if ($userid) $where .= ' and userid=' . $userid;
		if ($this->cid) $where .= ' and cid=' . $this->cid;
		if ($kw && $stype && isset($this->model['fields']['data'][$stype])) $where .= ' and `' . $stype . '` like "%' . $kw . '%"';
	    $total    = $this->content->count($this->table, 'id', $where);
	    $pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
	    $urlparam = array(
		    'kw'     => $kw,
			'stype'  => $stype,
			'modelid'=> $this->modelid,
			'status' => $status,
			'userid' => $userid,
			'cid'    => $this->cid,
			'page'   => '{page}',
		);
	    $url      = url('admin/form/list', $urlparam);
	    $data     = $this->form->page_limit($page, $pagesize)->where($where)->order(array('listorder DESC', 'updatetime DESC', 'id DESC'))->select();
	    $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
		$count    = array();
		$count[1] = $this->content->count($this->table, null, 'status=1');
		$count[0] = $this->content->count($this->table, null, 'status=0');
		$count[3] = $this->content->count($this->table, null, 'status=3');
		$count[$status]= $total;
	    $this->view->assign(array(
	        'list'     => $data,
	        'kw'       => $kw,
	        'page'     => $page,
	        'pagelist' => $pagelist,
			'status'   => $status,
			'count'    => $count,
			'join'     => empty($this->join) ? 0 : 1,
	    ));
	    $this->view->display('admin/form_list');
	}
	
	/**
	 * 表单配置
	 */
	public function configAction() {
		if ($this->isPostForm()) {
		    $data = $this->post('data');
			$cfg  = $this->post('setting');
			$field= array();
			if ($cfg['form']['field']) {
			    foreach ($cfg['form']['field'] as $c=>$t) {
				    if ($t) $field[]  = $c;
				}
				$cfg['form']['field'] = $field;
			}
		    $set  = array(
			    'modelname'   => $data['modelname'],
				'categorytpl' => $data['categorytpl'],
				'listtpl'     => $data['listtpl'],
				'showtpl'     => $data['showtpl'],
				'setting'     => array2string($cfg),
			);
			$model= $this->model('model');
			$model->update($set, 'modelid=' . $this->modelid);
			$this->adminMsg($this->getCacheCode('model') . lang('success'), '', 3, 1, 1);
		}
		$count[1] = $this->content->count($this->table, null, 'status=1');
		$count[0] = $this->content->count($this->table, null, 'status=0');
		$count[3] = $this->content->count($this->table, null, 'status=3');
	    $form_code= '<!-- ' . lang('a-for-6') . ' -->
<link href="{ADMIN_THEME}images/table_form.css" rel="stylesheet" type="text/css" />
<link href="{ADMIN_THEME}images/dialog.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{ADMIN_THEME}js/dialog.js"></script>
<script type="text/javascript">var sitepath = "{SITE_PATH}{ENTRY_SCRIPT_NAME}";</script>
<script type="text/javascript" src="{LANG_PATH}lang.js"></script>
<script type="text/javascript" src="{ADMIN_THEME}js/core.js"></script>

<!-- ' . lang('a-for-7') . ' -->

<form action="{url(\'form/post\', array(\'modelid\'=>$modelid, \'cid\'=>$cid))}" method="post">
<table width="100%" class="table_form ">
<tr>
	<th width="200">{$form_name}</th>
	<td></td>
</tr>
{$fields}
{if $code}
<tr>
	<th>' . lang('a-for-8') . '：</th>
	<td><input name="code" type="text" class="input-text" size=10 /><img src="{url(\'api/captcha\', array(\'width\'=>80,\'height\'=>25))}"></td>
</tr>
{/if}
<tr>
	<th style="border:none"> </th>
	<td style="border:none"><input type="submit" class="button" value="' . lang('a-submit') . '" name="submit"></td>
</tr>
</table>
</form>';
        $join_code = '';
		$form_url  = '{url(\'form/post\', array(\'modelid\'=>' . $this->model['modelid'] . '))}';
        $list_code = '
{list table=' . $this->model['tablename'] . ' num=10}
' . lang('a-for-9') . '：{$t[\'id\']}，' . lang('a-for-10') . '：{url(\'form/show\', array(\'modelid\'=>' . $this->model['modelid'] . ', \'id\'=>$t[\'id\']))}，' . lang('a-for-11') . '<br>
{/list}
<!-- ' . lang('a-for-12') . ' -->';
        if ($this->join) {
		    $join_code = '
<!-- ' . lang('a-for-13') . ' -->
{list table=' . $this->model['tablename'] . ' cid=' . lang('a-for-14') . ' num=10}
' . lang('a-for-9') . '：{$t[\'id\']}，' . lang('a-for-10') . '：{url(\'form/show\', array(\'modelid\'=>' . $this->model['modelid'] . ', \'id\'=>$t[\'id\']))}，' . lang('a-for-11') . '<br>
{/list}
<!-- ' . lang('a-for-15') . ' -->';
            $form_url  = '{url(\'form/post\', array(\'modelid\'=>' . $this->model['modelid'] . ', \'cid\'=>$id))}   ' . lang('a-for-16');
        }
		$this->view->assign(array(
			'count'     => $count,
			'form_code' => $form_code,
			'list_code' => $list_code,
			'join_code' => $join_code,
			'form_url'  => $form_url,
			'join'      => empty($this->join) ? 0 : 1,
	    ));
	    $this->view->display('admin/form_config');
	}
	
	/**
	 * 添加内容
	 */
	public function addAction() {
		if ($this->isPostForm()) {
		    $data = $this->post('data');
			$cid  = $this->post('cid');
			if ($this->join && empty($cid)) $this->adminMsg(lang('a-for-17'), '', 1);
			if ($this->join) {
				$table = $this->model($this->join['tablename']);
				$cdata = $table->find($cid, 'id');
				if (empty($cdata)) $this->adminMsg(lang('a-for-5', array('1'=>$this->join['modelname'], '2'=>$cid)));
			}
			$this->checkFields($this->model['fields'], $data, 1);
			$data['cid']      = $cid;
			$data['ip']       = client::get_user_ip();
			$data['userid']   = 0;
			$data['username'] = $this->userinfo['username'];
			$data['inputtime']= $data['updatetime'] = time();
			//数组转化为字符
			foreach ($data as $i=>$t) {
				if (is_array($t)) $data[$i] = array2string($t);
			}
			if ($this->form->insert($data)) {
			    $this->adminMsg(lang('success'), url('admin/form/list', array('modelid'=>$this->modelid, 'cid'=>$this->cid)), 3, 1, 1);
			} else {
			    $this->adminMsg(lang('failure'));
			}
		}
		$count[1] = $this->content->count($this->table, null, 'status=1');
		$count[0] = $this->content->count($this->table, null, 'status=0');
		$count[3] = $this->content->count($this->table, null, 'status=3');
	    $this->view->assign(array(
			'count'    => $count,
			'join'     => empty($this->join) ? 0 : 1,
			'fields'   => $this->getFields($this->model['fields'], null, $this->model['setting']['form']['field']),
	    ));
	    $this->view->display('admin/form_add');
	}
	
	/**
	 * 修改内容
	 */
	public function editAction() {
		$id       = (int)$this->get('id');
		if (empty($id)) $this->adminMsg(lang('a-for-18'));
		if ($this->isPostForm()) {
		    $data = $this->post('data');
			$cid  = $this->post('cid');
			if ($this->join && empty($cid)) $this->adminMsg(lang('a-for-17'), '', 1);
			$this->checkFields($this->model['fields'], $data, 1);
			$data['cid']        = $cid;
			$data['updatetime'] = time();
			//数组转化为字符
			foreach ($data as $i=>$t) {
				if (is_array($t)) $data[$i] = array2string($t);
			}
			if ($this->form->update($data, 'id=' . $id)) {
			    $this->adminMsg(lang('success'), url('admin/form/list', array('modelid'=>$this->modelid, 'cid'=>$this->cid)), 3, 1, 1);
			} else {
			    $this->adminMsg(lang('failure'));
			}
		}
		$data     = $this->form->find($id);
		if (empty($data)) $this->adminMsg(lang('a-for-18'));
		$count[1] = $this->content->count($this->table, null, 'status=1');
		$count[0] = $this->content->count($this->table, null, 'status=0');
		$count[3] = $this->content->count($this->table, null, 'status=3');
	    $this->view->assign(array(
			'count'    => $count,
			'data'     => $data,
			'cid'      => $data['cid'],
			'join'     => empty($this->join) ? 0 : 1,
			'fields'   => $this->getFields($this->model['fields'], $data, $this->model['setting']['form']['field']),
	    ));
	    $this->view->display('admin/form_add');
	}
	
	/**
	 * 删除
	 */
	public function delAction($id=0, $all=0) {
        if (!auth::check($this->roleid, 'form-del', 'admin')) $this->adminMsg(lang('a-com-0', array('1'=>'form', '2'=>'del')));
	    $id  = $id  ? $id  : $this->get('id');
	    $all = $all ? $all : $this->get('all');
	    $this->form->delete('id=' . $id);
	    $all or $this->adminMsg(lang('success'), url('admin/form/list', array('modelid'=>$this->modelid, 'cid'=>$this->cid)), 3, 1, 1);
	}
	
}