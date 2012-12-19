<?php

class ModelController extends Admin {
    
    protected $_model;
	protected $modeltype; //模型类型
	protected $typeid;
    
    public function __construct() {
		parent::__construct();
		$this->modeltype = array(
		    1 => 'content', //内容表模型
			2 => 'member',  //会员表模型
			3 => 'form',    //表单表模型
		);
		$this->_model = $this->model('model');
	    $this->typeid = $this->get('typeid') ? $this->get('typeid') : 1;
		if (!isset($this->modeltype[$this->typeid])) $this->adminMsg(lang('a-mod-0'));
		$this->view->assign(array(
		    'typeid'    => $this->typeid,
			'modeltype' => $this->modeltype,
			'typename'  => array(
			    1 => lang('a-men-27'),
				2 => lang('a-men-40'),
				3 => lang('a-men-60'),
			),
		));
	}

	public function indexAction() {
	    $model  = $this->_model->where('typeid=' . $this->typeid)->select();
		$this->view->assign('list', $model);
		$this->view->display('admin/model_list');
	}
	
	/*
	 * 添加模型
	 */
	public function addAction() {
	    if ($this->post('submit')) {
	        $tablename = $this->post('tablename');
	        if (!$tablename) $this->adminMsg(lang('a-mod-1'));
	        if (!preg_match('/^[0-9a-z]+$/', $tablename)) $this->adminMsg(lang('a-mod-2'));
	        $category  = $this->post('categorytpl') ? $this->post('categorytpl') : ($this->typeid == 3 ? 'post_' : 'category_') . $tablename . '.html';
	        $list      = $this->post('listtpl')     ? $this->post('listtpl') : 'list_' . $tablename . '.html';
	        $show      = $this->post('showtpl')     ? $this->post('showtpl') : 'show_' . $tablename . '.html';
			$tablename = $this->modeltype[$this->typeid]. '_' . $tablename;
	        $data      = array(
	            'tablename'   => $tablename,
	            'modelname'   => $this->post('modelname'),
	            'categorytpl' => $category,
	            'listtpl'     => $list,
	            'showtpl'     => $show,
				'typeid'      => $this->typeid,
	        );
	        if ($this->_model->is_table_exists($tablename)) $this->adminMsg(lang('a-mod-2', array('1'=>$tablename)));
	        if ($modelid = $this->_model->set(0, $data)) {
			    if ($this->typeid != 3) {
					$join = $this->post('join');
					if (is_array($join) && $join) {
					    foreach ($join as $id) {
						    $this->_model->set($id, array('joinid'=>$modelid));
						}
					}
				}
			    $this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/index/', array('typeid'=>$this->typeid)), 3, 1, 1);
			} else {
			    $this->adminMsg(lang('failure'));
			}
	    }
		$fdata = $this->cache->get('formmodel');
		$jdata = array();
		if ($fdata) {
		    foreach ($fdata as $t) {
			    if (!empty($t['joinid'])) $jdata[] = $t['modelid'];
			}
		}
		$this->view->assign(array(
		    'formmodel' => $fdata,
			'joindata'  => $jdata,
			'join'      => array(),
		));
	    $this->view->display('admin/model_add');
	}
	
	/*
	 * 修改模型
	 */
    public function editAction() {
	    if ($this->post('submit')) {
	        $modelid  = $this->post('modelid');
	        $category = $this->post('categorytpl');
	        $list     = $this->post('listtpl');
	        $show     = $this->post('showtpl');
	        $data     = array(
	            'modelname'   => $this->post('modelname'),
	            'categorytpl' => $category,
	            'listtpl'     => $list,
	            'showtpl'     => $show,
				'joinid'      => $this->post('joinid'),
	        );
	        $this->_model->set($modelid, $data);
			if ($this->typeid != 3) {
				$join = $this->post('join');
				$this->_model->update(array('joinid'=>0), 'joinid=' . $modelid);
				if (is_array($join) && $join) {
					foreach ($join as $id) {
						$this->_model->set($id, array('joinid'=>$modelid));
					}
				}
			}
	        $this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/index/', array('typeid'=>$this->typeid)), 3, 1, 1);
	    }
	    $modelid = $this->get('modelid');
		$fdata   = $this->cache->get('formmodel');
		$jdata   = $join = array();
		if ($fdata) {
		    foreach ($fdata as $t) {
			    if (!empty($t['joinid']))     $jdata[] = $t['modelid'];
				if ($t['joinid'] == $modelid) $join[]  = $t['modelid'];
			}
		}
		$this->view->assign(array(
		    'formmodel' => $fdata,
			'joindata'  => $jdata,
			'join'      => $join,
			'data'      => $this->_model->find($modelid),
		));
	    $this->view->display('admin/model_add');
	}
	
	/*
	 * 删除模型
	 */
	public function delAction() {
	    $mid  = $this->get('modelid');
	    $data = $this->_model->find($mid);
	    if (!$data) $this->adminMsg(lang('a-mod-4'));
	    $this->_model->del($data);
		$name = $this->typeid == 1 ? 'model' : $this->modeltype[$this->typeid] . 'model';
		$data = $this->cache->get($name);
		unset($data[$mid]);
		$this->cache->set($name, $data);
	    $this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/index/', array('typeid'=>$this->typeid)), 3, 1, 1);
	}
	
	/**
	 * 字段管理
	 */
	public function fieldsAction() {
	    $modelid = $this->get('modelid');
	    $data    = $this->_model->find($modelid);
	    if (!$data) $this->adminMsg(lang('a-mod-4'));
	    $table   = $this->model($data['tablename']);
	    $field   = $this->model('model_field');
	    if ($this->post('submit')) {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'order_')!==false) {
	                $id = (int)str_replace('order_', '', $var);
	                $field->update(array('listorder'=>$value), 'fieldid=' . $id);
	            }
	        }
			$this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/fields/', array('modelid'=>$modelid, 'typeid'=>$this->typeid)), 3, 1, 1);
	    }
		$setting = string2array($data['setting']);
	    $this->view->assign(array(
		    'modelid' => $modelid,
			'list'    => $field->where('modelid=' . $modelid)->order('listorder ASC')->select(),
			'content' => $setting['default'],
		));
	    $this->view->display('admin/model_fields');
	}
	
	/**
	 * 添加字段
	 */
	public function addfieldAction() {
	    $modelid    = (int)$this->get('modelid');
	    $model_data = $this->_model->find($modelid);
	    $field      = $this->model('model_field');
	    if (!$model_data) $this->adminMsg(lang('a-mod-4'));
	    if ($this->post('submit')) {
	        if ($this->typeid != 3) $table = $this->model($this->modeltype[$this->typeid]);
	        $table_data = $this->model($model_data['tablename']);
	        //主表和附表字段集合
	        $t_fields   = $this->typeid == 3 ? array() : $table->get_fields();
	        $d_fields   = $table_data->get_fields();
	        $fields     = array_unique(array_merge($t_fields, $d_fields));
	        //判断新加字段是否存在
	        $fieldname  = $this->post('field');
	        if (empty($fieldname ) || !preg_match('/^[a-zA-Z]{1}[a-zA-Z0-9]{0,19}$/', $fieldname)) $this->adminMsg(lang('a-mod-5'));
	        if (in_array($fieldname, $fields)) $this->adminMsg(lang('a-mod-6'));
	        $name  = $this->post('name');
	        if (empty($name))  $this->adminMsg(lang('a-mod-7'));
	        $ftype = $this->post('formtype');
	        if (empty($ftype)) $this->adminMsg(lang('a-mod-8'));
	        $type  = $this->post('type');
			$merge = $this->post('merge');
	        if (empty($merge) && !in_array($ftype, array('editor', 'checkbox', 'files', 'merge', 'date', 'fields')) && empty($type)) {
			    $this->adminMsg(lang('a-mod-9'));
			}
	        $data  = array(
	            'modelid'   => $this->post('modelid'),
	            'field'     => $fieldname,
	            'name'      => $name,
	            'formtype'  => $ftype,
	            'tips'      => $this->post('tips'),
	            'pattern'   => $this->post('pattern'),
	            'errortips' => $this->post('errortips'),
	            'setting'   => addslashes(var_export($_POST['setting'], true)),
				'type'      => $type,
				'length'    => $this->post('length'),
				'indexkey'  => $this->post('indexkey'),
				'isshow'    => isset($_POST['isshow']) ? $this->post('isshow') : 1,
				'not_null'  => $this->post('not_null'),
				'pattern'   => $this->post('pattern'),
				'errortips' => $this->post('errortips'),
				'merge'     => $merge,
	        );
	        //添加字段入库
	        if ($field->set(0, $data)) {
	            $this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/fields/', array('modelid'=>$modelid, 'typeid'=>$this->typeid)), 3, 1, 1);
	        } else {
	            $this->adminMsg(lang('failure'));
	        }
	    }
	    //加载字段配置文件
	    App::auto_load('fields');
	    $formtype = formtype();
	    $this->view->assign(array(
	        'model_data' => $model_data,
	        'formtype'   => $formtype,
	        'modelid'    => $modelid,
			'merge'      => $field->where('modelid=' . $modelid)->where('formtype=?', 'fields')->select(),
	    ));
	    $this->view->display('admin/model_addfield');
	}
	
	/**
	 * 修改字段
	 */
	public function editfieldAction() {
	    $field   = $this->model('model_field');
	    $fieldid = (int)$this->get('fieldid');
	    $data    = $field->getOne('fieldid=' . $fieldid);
	    if (empty($data)) $this->adminMsg(lang('a-mod-10'));
	    $modelid    = $data['modelid'];
	    $model_data = $this->_model->find($modelid);
	    if (!$model_data) $this->adminMsg(lang('a-mod-4'));
	    if ($this->post('submit')) {
	        $fieldid = $this->post('fieldid');
	        if (empty($fieldid)) $this->adminMsg(lang('a-mod-10'));
	        $name    = $this->post('name');
	        if (empty($name)) $this->adminMsg(lang('a-mod-7'));
	        $setting = $_POST['setting'];
	        $data    = array(
	            'name'      => $name,
	            'tips'      => $this->post('tips'),
	            'pattern'   => $this->post('pattern'),
	            'errortips' => $this->post('errortips'),
	            'setting'   => addslashes(var_export($setting, true)),
				'isshow'    => isset($_POST['isshow']) ? $this->post('isshow') : 1,
				'not_null'  => $this->post('not_null'),
				'pattern'   => $this->post('pattern'),
				'errortips' => $this->post('errortips'),
				'merge'     => $this->post('merge'),
	        );
	        //字段入库
	        if ($field->set($fieldid, $data)) {
	            $this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/fields/', array('typeid'=>$this->typeid, 'modelid'=>$modelid)), 3, 1, 1);
	        } else {
	            $this->adminMsg(lang('failure'));
	        }
	    }
	    //加载字段配置文件
	    App::auto_load('fields');
	    $formtype = formtype();
	    $this->view->assign(array(
	        'model_data' => $model_data,
	        'formtype'   => $formtype,
	        'modelid'    => $modelid,
	        'data'       => $data,
			'merge'      => $field->where('modelid=' . $modelid)->where('formtype=?', 'fields')->select(),
	    ));
	    $this->view->display('admin/model_addfield');
	}
	
	/**
	 * 修改默认字段
	 */
	public function ajaxeditAction() {
	    $modelid = (int)$this->get('modelid');
		$name    = $this->get('name');
	    $data    = $this->_model->find($modelid);
	    if (empty($data)) $this->adminMsg(lang('a-mod-4'));
		$setting = string2array($data['setting']);
		if (!isset($setting['default'][$name])) $this->adminMsg(lang('a-mod-10'));
		$field   = $setting['default'][$name];
	    if ($this->post('submit')) {
			$setting['default'][$name] = $this->post('data');
			$this->_model->update(array('setting'=>array2string($setting)), 'modelid=' . $modelid);
			$this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/fields/', array('typeid'=>$this->typeid, 'modelid'=>$modelid)), 3, 1, 1);
	    }
	    $this->view->assign(array(
	        'modelid' => $modelid,
			'name'    => $data['modelname'],
	        'data'    => $field,
	    ));
	    $this->view->display('admin/model_ajaxedit');
	}
	
	/**
	 * 动态加载字段类型配置信息
	 */
	public function ajaxformtypeAction() {
	    $type = $this->get('type');
	    if (empty($type)) exit('');
	    //加载字段配置文件
	    App::auto_load('fields');
	    $func = 'form_' . $type;
	    if (!function_exists($func)) exit('');
	    eval('echo ' . $func . '();');
	    
	}
	
	/**
	 * 禁用/启用字段
	 */
	public function disableAction() {
	    $field   = $this->model('model_field');
	    $fieldid = $this->get('fieldid');
	    $data    = $field->getOne('fieldid=' . $fieldid);
	    if (empty($data)) $this->adminMsg(lang('a-mod-10'));
	    $disable = $data['disabled'] == 1 ? 0 : 1;
	    $field->update(array('disabled'=>$disable), 'fieldid=' . $fieldid);
	    $this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/fields/', array('typeid'=>$this->typeid, 'modelid'=>$data['modelid'])), 3, 1, 1);
	}
	
	/**
	 * 删除字段
	 */
	public function delfieldAction() {
	    $field   = $this->model('model_field');
	    $fieldid = $this->get('fieldid');
	    $data    = $field->getOne('fieldid=' . $fieldid);
	    if (empty($data)) $this->adminMsg(lang('a-mod-10'));
		if ($data['field'] == 'content') $this->adminMsg(lang('a-mod-11'));
	    if ($field->del($data)) {
	        $this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/fields/', array('typeid'=>$this->typeid, 'modelid'=>$data['modelid'])), 3, 1, 1);
	    } else {
	        $this->adminMsg(lang('failure'));
	    }
	}
	
	
	/**
	 * 更新模型缓存
	 * array(
	 *     '模型ID'=> array(
	 *                    ...模型字段
	 *                    'content' => array(
	 *                        内容模型默认字段
	 *                    ),
	 *                    'fields'=> array(
	 *                                   'data'  => array(
	 *                                             ...该模型的可用字段
	 *                                           ),
	 *                                   'merge' => array(
	 *                                             ...组合字段
	 *                                           ),
	 *                                   'mergefields' => array(
	 *                                             被组合过的字段（将不会被单独显示）
	 *                                           ),
	 *                               ),
	 *                ),
	 * );
	 */
	public function cacheAction($show=0) {
		$this->delDir($this->_model->cache_dir);
		if (!file_exists($this->_model->cache_dir)) mkdir($this->_model->cache_dir, 0777, true);
	    $field = $this->model('model_field');
		foreach ($this->modeltype as $typeid=>$c) {
	        $model = $this->_model->where('typeid=' . $typeid)->select();
	        $data  = array();
			foreach ($model as $t) {
			    $setting   = string2array($t['setting']);
				if ($setting['disable'] == 1) continue;
				$id        = $t['modelid'];
				$data[$id] = $t;
				$fields    = $field->where('modelid=' . $id)->where('disabled=0')->order('listorder ASC')->select();
				$_fields   = $merge  = array();
				foreach ($fields as $k=>$f) {
				    $_fields[$f['field']] = $f;
				    if ($f['formtype'] == 'merge') {
						$setting = string2array($f['setting']);
						if (preg_match_all('/\{([a-zA-Z]{1}[a-zA-Z]{0,19})\}/Ui', $setting['content'], $fs)) {
						    $mergefields = $fs[1];
					        $_fields[$f['field']]['data'] = $mergefields;
							$merge = array_merge($merge, $mergefields);
						}
					}
				}
				if ($typeid == 1 && !isset($setting['default'])) {
				    $setting['default'] = array(
					    'title'         => array('name'=>lang('a-con-26'), 'show'=>1),
					    'keywords'      => array('name'=>lang('a-con-43'), 'show'=>1),
					    'thumb'         => array('name'=>lang('a-con-45'), 'show'=>1),
					    'description'   => array('name'=>lang('a-desc'),   'show'=>1),
					);
					$this->_model->update(array('setting'=>array2string($setting)), 'modelid=' . $id);
				}
				$data[$id]['fields']['data']  = $_fields;
				$data[$id]['fields']['merge'] = $merge;
				$data[$id]['setting']         = $setting;
				$data[$id]['content']         = $setting['default'];
			}
	        //保存到缓存文件中
	        $name = $typeid == 1 ? 'model' : $c . 'model';
			$this->cache->set($name, $data);
		}
		//缓存关联表单被关联的模型
		$join = array();
		$data = $this->_model->where('typeid=3')->select();
		if ($data) {
		    foreach ($data as $t) {
			    if ($t['joinid'] && !isset($join[$t['joinid']])) {
				    $join[$t['joinid']] = $this->_model->where('modelid=' . $t['joinid'])->select(false);
				}
			}
		}
		$this->cache->set('joinmodel', $join);
	    $show or $this->adminMsg(lang('a-update'), '', 3, 1, 1);
	}
	
	/*
	 * 禁用/启用模型
	 */
	public function cdisabledAction() {
	    $modelid = $this->get('modelid');
	    $data    = $this->_model->find($modelid);
	    if (!$data) $this->adminMsg(lang('a-mod-4'));
		$setting = string2array($data['setting']);
	    $setting['disable'] = $setting['disable'] == 1 ? 0 : 1;
	    $this->_model->update(array('setting'=>array2string($setting)), 'modelid=' . $modelid);
	    $this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/index/', array('typeid'=>$this->typeid)), 3, 1, 1);
	}
	
	/*
	 * 导出模型
	 */
	public function exportAction() {
		$modelid = $this->get('modelid');
		$cache   = $this->typeid == 1 ? 'model' : $this->modeltype[$this->typeid] . 'model';
		$model   = $this->cache->get($cache);
	    if (!$model) $this->adminMsg(lang('a-mod-4'));
		if (!isset($model[$modelid])) $this->adminMsg(lang('a-mod-4'));
		$result  = array2string($model[$modelid]);
		header('Content-Disposition: attachment; filename="' . $model[$modelid]['tablename'] . '.mod"');
		echo $result;exit;
	}
	
	/*
	 * 导入模型
	 */
	public function importAction() {
	    if ($this->post('submit')) {
	        $tablename = $this->post('tablename');
	        if (!$tablename) $this->adminMsg(lang('a-mod-1'));
	        if (!preg_match('/^[0-9a-z]+$/', $tablename)) $this->adminMsg(lang('a-mod-2'));
			$tablename = $this->modeltype[$this->typeid]. '_' . $tablename;
	        if ($this->_model->is_table_exists($tablename)) $this->adminMsg(lang('a-mod-3', array('1'=>$tablename)));
			if(!empty($_FILES['import']['tmp_name'])) {
				$model = @file_get_contents($_FILES['import']['tmp_name']);
				if(!empty($model)) {
					$data = string2array($model);				
				} else {
				    $this->adminMsg(lang('a-mod-12'));
				}
			} else{
			    $this->adminMsg(lang('a-mod-13'));
			}
			if ($data['typeid'] != $this->typeid) $this->adminMsg(lang('a-mod-14', array('1'=>$this->modeltype[$data['typeid']])));
			$insert = array(
	            'tablename'   => $tablename,
	            'modelname'   => $this->post('modelname'),
	            'listtpl'     => $data['listtpl'],
	            'showtpl'     => $data['showtpl'],
				'typeid'      => $this->typeid,
	            'categorytpl' => $data['categorytpl'],
	        );
	        $modelid = $this->_model->set(0, $insert);
			if (empty($modelid)) $this->adminMsg(lang('a-mod-15'));
			$field   = $this->model('model_field');
			$content = $data['fields']['data']['content'];
			unset($data['fields']['data']['content']);
			if (isset($data['fields']['data']) && $data['fields']['data']) {
			    foreach ($data['fields']['data'] as $t) {
				    unset($t['fieldid']);
					$t['modelid'] = $modelid;
					$t['setting'] = var_export($t['setting'],true);
					if (substr($t['setting'], 0, 1) == "'") $t['setting'] = substr($t['setting'], 1);
					if (substr($t['setting'], -1) == "'")   $t['setting'] = substr($t['setting'], 0, -1);
					$field->set(0, $t);
				}
			}
			unset($content['fieldid']);
			$content['modelid'] = $modelid;
			$content['setting'] = var_export($content['setting'],true);
			if (substr($content['setting'], 0, 1) == "'") $content['setting'] = substr($content['setting'], 1);
			if (substr($content['setting'], -1) == "'")   $content['setting'] = substr($content['setting'], 0, -1);
			$field->update($content, 'modelid=' . $modelid . ' and field="content"');
			$this->adminMsg($this->getCacheCode('model') . lang('success'), url('admin/model/index/', array('typeid'=>$this->typeid)), 3, 1, 1);
	    }
	    $this->view->display('admin/model_import');
	}
	
}