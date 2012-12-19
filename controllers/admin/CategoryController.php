<?php

class CategoryController extends Admin {
    
    protected $category;
    protected $tree;
    
    public function __construct() {
		parent::__construct();
		$this->category = $this->model('category');
		$this->tree     = $this->instance('tree');
		$this->tree->config(array('id'=>'catid', 'parent_id'=>'parentid', 'name'=>'catname'));
	}

	public function indexAction() {
	    if ($this->post('submit')) {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'order_')!==false) {
	                $id = (int)str_replace('order_', '', $var);
	                $this->category->update(array('listorder'=>$value), 'catid=' . $id);
	            }
	        }
	    }
		if ($this->post('delete')) {
			$ids = $this->post('ids');
			if ($ids) {
			    foreach($ids as $catid) {
				    $this->delAction($catid, 1);
				}
			}
	    }
		$catdata = $this->category->order('listorder ASC')->select();
	    $model   = $this->cache->get('model');
	    $this->view->assign('model', $model);
	    $this->view->assign('list', $this->tree->get_tree_data($catdata));
		$this->view->display('admin/category_list');
	}
	
	/**
	 * 添加栏目
	 */
	public function addAction() {
	    if ($this->post('submit')) {
	        $data = $this->post('data');
	        if ($data['typeid'] == 1) {
	            if (empty($data['modelid'])) $this->adminMsg(lang('a-cat-0'));
	        } elseif ($data['typeid'] == 2) {
	            if (empty($data['content'])) $this->adminMsg(lang('a-cat-1'));
	        } elseif ($data['typeid'] == 3) {
	            if (empty($data['urlpath'])) $this->adminMsg(lang('a-cat-2'));
	        } else {
	            $this->adminMsg(lang('a-cat-3'));
	        }
	        if ($this->post('addall')) {
			    $names  = $this->post('names');
				if (empty($names)) $this->adminMsg(lang('a-cat-4'));
				$names  = explode(chr(13), $names);
				$y = $n = 0;
				foreach ($names as $val) {
				    list($catname, $catdir) = explode('|', $val);
					$catdir = $catdir ? $catdir : word2pinyin($catname);
					if ($data['typeid'] != 3 && $this->category->check_catdir(0, $catdir)) $catdir .= rand(0, 9);
					$data['catname'] = $catname;
					$data['catdir']  = $catdir;
					$setting = $this->post('setting');
				    if ($setting) $data['setting'] = array2string($setting);
				    $catid = $this->category->set(0, $data);
					if (!is_numeric($catid)) {
					    $n++;
					} else {
					    $this->category->url($catid, $this->getCaturl($data));
						$y++;
					}
				}
				$this->adminMsg($this->getCacheCode('category') . lang('a-cat-5', array('1'=>$y, '2'=>$n)), url('admin/category/index'), 3, 1, 1);
			} else {
				if (empty($data['catname'])) $this->adminMsg(lang('a-cat-4'));
				if ($data['typeid'] != 3 && $this->category->check_catdir(0, $data['catdir']))  $this->adminMsg(lang('a-cat-6'));
				$setting = $this->post('setting');
				if ($setting) $data['setting'] = array2string($setting);
				$result = $this->category->set(0, $data);
				if (!is_numeric($result)) $this->adminMsg($result);
				$data['catid'] = $result;
				$this->category->url($result, $this->getCaturl($data));
				$this->adminMsg($this->getCacheCode('category') . lang('success'), url('admin/category/index'), 3, 1, 1);
			}
	    }
	    $model   = $this->cache->get('model');
	    $catdata = $this->category->order('listorder ASC')->select();
	    $catid   = (int)$this->get('catid');
		$json_m  = json_encode($model);
	    $this->view->assign(array(
	        'category_select' => $this->tree->get_tree($catdata, 0, $catid),
	        'model'           => $model,
	        'json_model'      => $json_m ? $json_m : '""',
			'membergroup'     => $this->cache->get('membergroup'),
			'membermodel'     => $this->cache->get('membermodel'),
			'add'             => 1,
	    ));
	    $this->view->display('admin/category_add');
	}
	
	/**
	 * 修改栏目
	 */
    public function editAction() {
	    if ($this->post('submit')) {
	        $catid = $this->post('catid');
            if (empty($catid)) $this->adminMsg(lang('a-cat-7'));
	        $data  = $this->post('data');
	        if (empty($data['catname'])) $this->adminMsg(lang('a-cat-4'));
	        if ($this->post('typeid')==1 && $this->category->check_catdir($catid, $data['catdir'])) $this->adminMsg(lang('a-cat-6'));
	        $data['typeid'] = $this->post('typeid');
			$setting        = $this->post('setting');
			if ($setting) $data['setting'] = array2string($setting);
	        $result = $this->category->set($catid, $data);
	        if (is_numeric($result)) {
	            $this->adminMsg($this->getCacheCode('category') . lang('success'), url('admin/category/index'), 3, 1, 1);
	        } else {
	            $this->adminMsg(lang('a-cat-8'));
	        }
	    }
        $catid   = $this->get('catid');
        if (empty($catid)) $this->adminMsg(lang('a-cat-7'));
        $data    = $this->category->find($catid);
		$setting = string2array($data['setting']);
	    $model   = $this->cache->get('model');
	    $catdata = $this->category->order('listorder ASC')->select();
		$json_m  = json_encode($model);
	    $this->view->assign(array(
	        'catid'           => $catid,
	        'data'            => $data,
	        'category_select' => $this->tree->get_tree($catdata, 0, $data['parentid']),
	        'model'           => $model,
	        'json_model'      => $json_m ? $json_m : '""',
			'membergroup'     => $this->cache->get('membergroup'),
			'membermodel'     => $this->cache->get('membermodel'),
			'setting'         => $setting,
	    ));
	    $this->view->display('admin/category_add');
	}
	
	/**
	 * 删除栏目
	 */
	public function delAction($catid=0, $all=0) {
	    //重新权限验证
        if (!auth::check($this->roleid, 'category-del', 'admin')) $this->adminMsg(lang('a-com-0', array('1'=>'category', '2'=>'del')));
	    $catid = $catid ? $catid : $this->get('catid');
	    $all   = $all   ? $all   : $this->get('all');
        if (empty($catid)) $this->adminMsg(lang('a-cat-7'));
        $result = $this->category->del($catid);
	    if ($result) {
	        $all or $this->adminMsg($this->getCacheCode('category') . lang('success'), url('admin/category/index'), 3, 1, 1);
	    } else {
	        $all or $this->adminMsg(lang('a-cat-8'));
	    }
	}
	
	/**
	 * 批量URL规则
	 */
	public function urlAction() {
	    if ($this->post('submit')) {
	        $catids = $this->post('catids');
	        $url    = $this->post('url');
			$count  = 0;
            if (empty($catids)) $this->adminMsg(lang('a-cat-9'));
	        foreach ($catids as $catid) {
			    if ($catid && isset($this->cats[$catid])) {
				    $setting = $this->cats[$catid]['setting'];
					$setting['url'] = $url;
					$setting = array2string($setting);
					$this->category->update(array('setting'=>$setting), 'catid=' . $catid);
					$count ++;
				}
			}
			$this->adminMsg($this->getCacheCode('category') . lang('a-cat-10', array('1'=>$count)), url('admin/category'), 3, 1, 1);
	    }
	    $this->view->assign('category',$this->tree->get_tree($this->cats));
	    $this->view->display('admin/category_url');
	}
	
	/**
	 * 调用父级栏目url规则
	 */
	public function ajaximportAction() {
	    $catid = (int)$this->get('catid');
		if (empty($catid)) exit(json_encode(array('status'=>0)));
		$data  = $this->category->find($catid);
		if (empty($data))  exit(json_encode(array('status'=>0)));
		$setting = string2array($data['setting']);
		$return  = array(
		    'status'    => 1,
			'list'      => isset($setting['url']['list'])      ? $setting['url']['list']      : '', 
			'list_page' => isset($setting['url']['list_page']) ? $setting['url']['list_page'] : '', 
			'show'      => isset($setting['url']['show'])      ? $setting['url']['show']      : '', 
			'show_page' => isset($setting['url']['show_page']) ? $setting['url']['show_page'] : '', 
			'catjoin'   => isset($setting['url']['catjoin'])   ? $setting['url']['catjoin']   : '/', 
		);
		exit(json_encode($return));
	}
	
	/**
	 * 更新栏目缓存
	 * array(
	 *     '栏目ID' => array(
	 *                     ...栏目信息
	 *                     ...模型表名称
	 *                 ),
	 * );
	 */
	public function cacheAction($show=0) {
	    $this->category->repair(); //递归修复栏目数据
	    $model     = $this->cache->get('model');
	    $data      = $this->category->order('listorder ASC')->select(); //数据库查询最新数据
	    $category  = $category_dir = array();
	    foreach ($data as $t) {
	        $catid = $t['catid'];
	        $category[$catid] = $t;
	        if ($t['typeid'] == 1) {
	            $category[$catid]['tablename'] = $tablename = $model[$t['modelid']]['tablename'];
	            $category[$catid]['modelname'] = $model[$t['modelid']]['modelname'];
	        }
			$category[$catid]['arrchilds'] = $catid; //所有子栏目集,默认当前栏目ID
	        if ($t['typeid'] !=3) {
	            $category[$catid]['arrchilds'] = $t['child'] ? $catid . ',' . $category[$catid]['arrchildid'] : $catid; //该栏目下的所有子栏目集
	            $total_num = $this->category->count('content', 'id', 'catid IN (' . $category[$catid]['arrchilds'] . ')');
	            $category[$catid]['items'] = $total_num;
	            $this->category->update(array('items'=>$total_num), 'catid=' . $catid);
	        }
	        //把预定义的 HTML 实体转换为字符
	        $category[$catid]['content'] = htmlspecialchars_decode($category[$catid]['content']);
			//转换setting
			$category[$catid]['setting'] = string2array($category[$catid]['setting']);
			//更新分页数量
			if (empty($t['pagesize'])) {
			    $pcat = $this->category->getParentData($catid);
			    $category[$catid]['pagesize'] = $pcat['pagesize'] ? $pcat['pagesize'] : $this->site['SITE_SEARCH_PAGE'];
				$this->category->update(array('pagesize'=>$category[$catid]['pagesize']), 'catid=' . $catid);
			}
	    }
	    $this->cache->set('category', $category);
		$category = $this->cache->get('category');
		//更新URL
		foreach ($data as $t) {
			$category[$t['catid']]['url'] = $url = $this->getCaturl($t);
			$this->category->update(array('url'=>$url), 'catid=' . $t['catid']);
			$category_dir[$t['catdir']]   = $t['catid'];
	    }
	    //保存到缓存文件
	    $this->cache->set('category', $category);
	    $this->cache->set('category_dir', $category_dir);
	    $show or $this->adminMsg(lang('a-update'), url('admin/category/index'), 3, 1, 1);
	}
}