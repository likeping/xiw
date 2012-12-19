<?php

class ContentController extends Member {
    
    private $tree;
	private $group;
	private $form;
    
    public function __construct() {
		parent::__construct();
		$this->isLogin(); //登录验证
		if (!$this->memberinfo['status']) $this->memberMsg(lang('m-con-0')); //判断审核
		$this->tree  = $this->instance('tree');
		$this->group = $this->membergroup[$this->memberinfo['groupid']];
		$this->form  = $this->getFormMember();
		$this->tree->config(array('id'=>'catid', 'parent_id'=>'parentid', 'name'=>'catname'));
		$navigation  = array(
		    'add'        => array('name'=> lang('m-con-1'), 'url'=> 'javascript:setCategory();'),
		    '1'          => array('name'=> lang('m-con-2'), 'url'=> url('member/content/list', array('status'=>1))),
		    '0'          => array('name'=> lang('m-con-3'), 'url'=> url('member/content/list', array('status'=>0))),
		    '2'          => array('name'=> lang('m-con-4'), 'url'=> url('member/content/list', array('status'=>2))),
		    'attachment' => array('name'=> lang('m-con-5'), 'url'=> url('member/content/attachment')),
		);
		if ($this->form) {
		    foreach ($this->form as $t) {
			    $navigation[$t['tablename']] = array('name'=>$t['joinname'] . $t['modelname'], 'url'=>url('member/content/form', array('modelid'=>$t['modelid'])));
			}
		}
		$this->view->assign('navigation', $navigation);
	}
	
	/*
	 * 内容管理
	 */
	public function listAction() {
	    if ($this->isPostForm()) { //刷新
	        $ids  = @implode(',', $this->post('ids'));
			if (empty($ids)) $this->memberMsg(lang('m-con-6'));
			$this->content->update(array('updatetime'=>time()), "userid=" . $this->memberinfo['id'] . " and username='" . $this->memberinfo['username'] . "' and sysadd=0 and id in(" . $ids . ")");
	    }
	    $kw       = $kw ? $kw : $this->get('kw');
	    $catid    = $catid ? $catid : (int)$this->get('catid');
	    $page     = (int)$this->get('page');
		$page     = (!$page) ? 1 : $page;
		$modelid  = (int)$this->get('modelid');
		$status   = isset($_GET['status']) ? (int)$this->get('status') : 1;
	    $pagelist = $this->instance('pagelist');
		$pagelist->loadconfig();
	    $where    = 'userid=' . $this->memberinfo['id'] . ' and sysadd=0';
	    if ($catid && $this->cats[$catid]['arrchilds']) $where .= ' and catid in (' . $this->cats[$catid]['arrchilds'] . ')';
	    if ($modelid) $where .= ' and modelid=' . $modelid;
		if ($status == 1) {
		    $where .= ' and status=1';
		} elseif ($status ==0) {
		    $where .= ' and status=0';
		} elseif ($status ==2) {
		    $where .= ' and status=2';
		}
	    $total    = $this->content->count('content', 'id', $where);
	    $pagesize = isset($this->memberconfig['pagesize']) && $this->memberconfig['pagesize'] ? $this->memberconfig['pagesize'] : 8;
	    $urlparam = array();
	    if ($catid)   $urlparam['catid']   = $catid;
		$urlparam['status'] = $status;
	    $urlparam['page']   = '{page}';
	    $url      = url('member/content/list', $urlparam);
	    $select   = $this->content->page_limit($page, $pagesize)->order(array('updatetime DESC'));
	    $select->where('userid=' . $this->memberinfo['id'] . ' and sysadd=0');
	    if ($catid && $this->cats[$catid]['arrchilds']) $select->where('catid in (' . $this->cats[$catid]['arrchilds'] . ')');
		if ($status == 1) {
			$select->where('status=1');
			$name = lang('m-con-2');
		} elseif ($status ==0) {
			$select->where('status=0');
			$name = lang('m-con-3');
		} elseif ($status ==2) {
			$select->where('status=2');
			$name = lang('m-con-4');
		}
	    $data     = $select->select();
	    $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
	    $this->view->assign(array(
	        'category'   => $this->cats,
	        'list'       => $data,
	        'catid'      => $catid,
	        'page'       => $page,
	        'pagelist'   => $pagelist,
			'meta_title' => $name . '-' . lang('member') . '-' . $this->site['SITE_NAME'],
			'status'     => $status,
			'countinfo'  => $this->getPosts(),
	    ));
	    $this->view->display('member/list');
	}
	
	/*
	 * 栏目选择
	 */
	public function categoryAction() {
	    echo "<div style='text-align:center;padding-top:10px;'>";
	    echo "<select id='catid_post' name='catid_post'>";
	    echo "<option value='0'>" . lang('m-con-7') . "</option>";
	    echo $this->tree->get_tree($this->cats, 0, null, '&nbsp;|-', true, $this->memberinfo['modelid'], $this->memberinfo['groupid']);
	    echo "</select>";
	    echo "</div>";
	}
	
	/*
	 * 发布
	 */
	public function addAction() {
	    $catid    = (int)$this->get('catid');
	    if (empty($catid)) $this->memberMsg(lang('m-con-8'));
	    $model    = $this->cache->get('model');
	    if (!isset($this->cats[$catid])) $this->memberMsg(lang('m-con-9', array('1'=>$catid)));
	    $modelid  = $this->cats[$catid]['modelid'];
	    if (!isset($model[$modelid])) $this->memberMsg(lang('m-con-10'));
	    $fields   = $model[$modelid]['fields'];
		if ($this->cats[$catid]['child']) $this->adminMsg(lang('m-con-11'));
		//投稿权限验证
		if (isset($this->cats[$catid]['setting']['memberpost']) && $this->cats[$catid]['setting']['memberpost']) {
		    if (isset($this->cats[$catid]['setting']['modelpost']) && in_array($this->memberinfo['modelid'], $this->cats[$catid]['setting']['modelpost'])) {
			    $this->memberMsg(lang('m-con-12'));
			}
			if (isset($this->cats[$catid]['setting']['grouppost']) && in_array($this->memberinfo['groupid'], $this->cats[$catid]['setting']['grouppost'])) {
			    $this->memberMsg(lang('m-con-12'));
			}
		}
	    if ($this->post('submit')) {
		    //用户组验证
			$this->postCheck();
	        $data  = $this->post('data');
	        if (empty($data['title'])) $this->memberMsg(lang('m-con-13'));
			$this->checkFields($fields, $data, 2);
	        $data['username']  = $this->memberinfo['username'];
			$data['userid']    = $this->memberinfo['id'];
	        $data['inputtime'] = $data['updatetime'] = time();
	        $data['sysadd']    = 0;
			$data['status']    = 0;
	        $data['modelid']   = (int)$modelid;
	        $result            = $this->content->set(0, $model[$modelid]['tablename'], $data);
	        $data['id']        = $result;
	        if (!is_numeric($result)) $this->memberMsg($result);
	        $this->content->url($result, $this->getUrl($data));
			$msg = '<a href="' . url('member/content/add', array('catid'=>$data['catid'])) . '" style="font-size:14px;">' . lang('m-con-14') . '</a>&nbsp;&nbsp;<a href="' . url('member/content/list', array('status'=>$data['status'])) . '" style="font-size:14px;">' . lang('m-con-15') . '</a>';
	        $this->memberMsg($msg, url('member/content/list', array('status'=>$data['status'])), 1, 20);
	    }
	    //自定义字段
	    $data_fields      = $this->getFields($fields, $data);
	    $this->view->assign(array(
	        'data'        => array('catid'=>$catid),
	        'category'    => $this->cats[$catid],
	        'data_fields' => $data_fields,
			'meta_title'  => lang('m-con-16') . '-' . lang('member') . '-' . $this->site['SITE_NAME'],
			'status'      => -1,
			'model'       => $model[$modelid],
	    ));
	    $this->view->display('member/add');
	}
	
    public function editAction() {
	    $id       = (int)$this->get('id');
	    $data     = $this->content->where('sysadd=0 AND userid=' . $this->memberinfo['id'])->where('username=?', $this->memberinfo['username'])->where('id=' . $id)->select(false);
	    $catid    = $data['catid'];
	    if (empty($data))  $this->memberMsg(lang('m-con-17'));
	    if (empty($catid)) $this->memberMsg(lang('m-con-18'));
	    $model    = $this->cache->get('model');
	    $modelid  = $this->cats[$catid]['modelid'];
	    $fields   = $model[$modelid]['fields'];
		//投稿权限验证
		if (isset($this->cats[$catid]['setting']['memberpost']) && $this->cats[$catid]['setting']['memberpost']) {
		    if (isset($this->cats[$catid]['setting']['modelpost']) && in_array($this->memberinfo['modelid'], $this->cats[$catid]['setting']['modelpost'])) {
			    $this->memberMsg(lang('m-con-12'));
			}
			if (isset($this->cats[$catid]['setting']['grouppost']) && in_array($this->memberinfo['groupid'], $this->cats[$catid]['setting']['grouppost'])) {
			    $this->memberMsg(lang('m-con-12'));
			}
		}
	    $url      = $this->getUrl($data);
	    if ($this->post('submit')) {
	        unset($data);
	        $data = $this->post('data');
	        if (empty($data['title'])) $this->memberMsg(lang('m-con-13'));
	        if ($data['catid'] != $catid && $modelid != $this->cats[$data['catid']]['modelid']) $this->memberMsg(lang('m-con-35'));
			$this->checkFields($fields, $data, 2);
	        $data['updatetime'] = time();
	        $data['url']        = $url;
	        $data['modelid']    = (int)$modelid;
	        $data['status']     = 0;
			unset($data['username'], $data['userid'], $data['sysadd']);
	        $result             = $this->content->set($id, $model[$modelid]['tablename'], $data);
	        if (!is_numeric($result)) $this->memberMsg($result);
	        $this->memberMsg(lang('success'), url('member/content/list', array('status'=>$data['status'])), 1);
	    }
	    //附表内容
	    $table       = $this->model($model[$modelid]['tablename']);
	    $table_data  = $table->find($id);
	    if ($table_data) $data = array_merge($data, $table_data); //合并主表和附表
	    //自定义字段
	    $data_fields = $this->getFields($fields, $data);
	    $select      = $this->tree->get_tree($this->cats, 0, $catid, '&nbsp;|-', true);
	    $this->view->assign(array(
	        'data'            => $data,
	        'category'        => $this->cats[$catid],
	        'data_fields'     => $data_fields,
			'meta_title'      => lang('m-con-19') . '-' . lang('member') . '-' . $this->site['SITE_NAME'],
			'status'          => -1,
	        'category_select' => $select,
			'model'           => $model[$modelid],
	    ));
	    $this->view->display('member/add');
	}
	
	/**
	 * 附件管理
	 */
	public function attachmentAction() {
	    $type = $this->get('type');
		$mdir = 'uploadfiles/member/' . $this->memberinfo['id'] . '/'; //会员附件目录
		if (!file_exists($mdir)) mkdir($mdir);
		$mdir = $type == 1 ? $mdir . 'file/' : $mdir . 'image/';
		if (!file_exists($mdir)) mkdir($mdir);
		$dir  = urldecode($this->get('dir'));
		if (strpos($dir, '../') !== false) $this->memberMsg(lang('m-con-20'), url('member/content/attachment', array('type'=>$type)));
		$dir  = substr($dir, 0, 1) == '/' ? substr($dir, 1) : $dir;
        $data = file_list::get_file_list($mdir . $dir . '/');
        $list = array();
        if ($data) {
            foreach ($data as $t) {
                $path = $mdir . $dir . '/' . $t;
                $ext  = is_dir($path) ? 'dir' : strtolower(trim(substr(strrchr($t, '.'), 1, 10)));
                $ico  = file_exists(basename(VIEW_DIR) . '/admin/images/ext/' . $ext . '.gif') ? $ext . '.gif' : $ext . '.png';
                $info = array();
                if (is_file($path)) {
				    if (strpos($t, '.thumb.') !== false) continue;
                    $info = array(
                        'path' => $path,
                        'time' => date('Y-m-d H:i:s', filemtime($path)),
                        'size' => formatFileSize(filesize($path), 2),
                        'ext'  => $ext,
                    );
                }
                $list[] = array(
                    'name'  => $t,
					'path'  => $path,
                    'dir'   => urlencode($dir . '/' . $t),
                    'ico'   => $ico,
                    'isimg' => in_array($ext, array('gif','jpg','png','jpeg','bmp')) ? 1 : 0,
                    'isdir' => is_dir($path) ? 1 : 0, 
                    'info'  => $info,
                    'url'   => is_dir($path) ? url('member/content/attachment', array('dir'=>urlencode($dir . '/' . $t), 'type'=>$type)) : '',
                );
            }
        }
        $this->view->assign(array(
            'dir'        => $dir,
            'istop'      => $dir ? 1 : 0,
            'pdir'       => url('member/content/attachment', array('dir'=>urlencode(str_replace(basename($dir), '', $dir)), 'type'=>$type)),
            'list'       => $list,
			'meta_title' => lang('m-con-5') . '-' . lang('member') . '-' . $this->site['SITE_NAME'],
			'type'       => $type,
			'countsize'  => formatFileSize(count_member_size($this->memberinfo['id'], ($type == 1 ? 'file' : 'image')), 2),
	    ));
	    $this->view->display('member/attachment');
	}
	
	/**
	 * 删除附件
	 */
	public function delattachmentAction() {
	    $type = $this->get('type');
		$mdir = 'uploadfiles/member/' . $this->memberinfo['id'] . '/'; //会员附件目录
		if (!file_exists($mdir)) mkdir($mdir);
		$mdir = $type == 1 ? $mdir . 'file/' : $mdir . 'image/';
		if (!file_exists($mdir)) mkdir($mdir);
		$dir  = urldecode($this->get('dir'));
		$dir  = substr($dir, 0, 1) == '/' ? substr($dir, 1) : $dir;
		if (realpath($mdir . $dir) == false || strpos($dir, '../') !== false) $this->memberMsg(lang('m-con-21'));
		if (file_exists($mdir . $dir)) {
		    if (is_dir($mdir . $dir)) {
			    $this->delDir($mdir . $dir);
				$this->memberMsg(lang('success'), url('member/content/attachment', array('type'=>$type)), 1);
			} else {
			    unlink($mdir . $dir);
				$this->memberMsg(lang('success'), url('member/content/attachment', array('type'=>$type, 'dir'=>urlencode(dirname($dir)))), 1);
			}
		} else {
		    $this->memberMsg(lang('m-con-22', array('1'=>$dir)));
		}
	}
	
	/**
	 * 删除文章
	 */
	public function delAction(){
	    $id    = (int)$this->get('id');
		$data  = $this->content->find($id, 'sysadd,username,userid,url');
		if ($data['sysadd'] == 0 && $data['username'] == $this->memberinfo['username'] && $data['userid'] == $this->memberinfo['id']) {
		    $this->content->update(array('status'=>3), 'id=' . $id);
			$file = str_replace(self::get_base_url(), '', $data['url']);
			if (file_exists($file)) @unlink($file);
			$this->memberMsg(lang('success'), url('member/content/list', array('status'=>$data['status'])), 1);
		} else {
		    $this->memberMsg(lang('m-con-23'));
		}
	}
	
	/*
	 * 表单管理
	 */
	public function formAction() {
	    $type     = (int)$this->get('type');
		$cid      = (int)$this->get('cid');
		$page     = (int)$this->get('page');
		$page     = (!$page) ? 1 : $page;
		$modelid  = (int)$this->get('modelid');
		if (!isset($this->form[$modelid]) || empty($this->form[$modelid])) $this->memberMsg(lang('m-con-24'));
	    $table    = $this->model($this->form[$modelid]['tablename']);
		if ($this->isPostForm()) { //删除
	        $ids  = @implode(',', $this->post('ids'));
			if (empty($ids)) $this->memberMsg(lang('m-con-25'));
			$table->update(array('status'=>3), "userid=" . $this->memberinfo['id'] . " and username='" . $this->memberinfo['username'] . "' and id in(" . $ids . ")");
	    }
		$showme   = isset($this->form[$modelid]['joinid']) && isset($this->form[$modelid]['setting']['form']['showme']) ? $this->form[$modelid]['setting']['form']['showme'] : 0;
	    $pagelist = $this->instance('pagelist');
		$pagelist->loadconfig();
	    $pagesize = isset($this->memberconfig['pagesize']) && $this->memberconfig['pagesize'] ? $this->memberconfig['pagesize'] : 8;
	    $url      = url('member/content/form', array('modelid'=>$modelid, 'type'=>$type, 'page'=>'{page}'));
		if ($this->form[$modelid]['joinid'] && $type && $showme) {
		    $where = empty($cid) ? '`status`=1 AND `cid` IN (SELECT `id` FROM `' . $this->content->prefix . 'content` WHERE `modelid`=' . $this->form[$modelid]['joinid'] . ' AND `sysadd`=0 AND `status`=1 AND `userid`=' . $this->memberinfo['id'] . ' AND `username`="' . $this->memberinfo['username'] . '")' : '`status`=1 AND `cid` IN (SELECT `id` FROM `' . $this->content->prefix . 'content` WHERE `modelid`=' . $this->form[$modelid]['joinid'] . ' AND `sysadd`=0 AND `status`=1 AND `userid`=' . $this->memberinfo['id'] . ' AND `username`="' . $this->memberinfo['username'] . '" AND id=' . $cid . ')';
		} else {
		    $where = (empty($cid) ? '`status`=1 AND ' : '`status`=1 AND `cid`=' . $cid . ' AND ') . '`userid`=' . $this->memberinfo['id'] . ' AND `username`="' . $this->memberinfo['username'] . '"';
		}
		$total    = $table->count($this->form[$modelid]['tablename'], 'id', $where);
	    $data     = $table->page_limit($page, $pagesize)->order('updatetime DESC')->where($where)->select();
	    $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
	    $this->view->assign(array(
	        'listdata'   => $data,
	        'page'       => $page,
	        'pagelist'   => $pagelist,
			'meta_title' => $this->form[$modelid]['joinname'] . $this->form[$modelid]['modelname'] . '-' . lang('member') . '-' . $this->site['SITE_NAME'],
			'showfields' => isset($this->form[$modelid]['setting']['form']['membershow']) ? $this->form[$modelid]['setting']['form']['membershow'] : array(),
			'showme'     => $showme,
			'type'       => $type,
			'form'       => $this->form[$modelid],
			'modelid'    => $modelid,
			'join'       => $this->form[$modelid]['joinid'] ? $this->form[$modelid]['joinname'] : 0,
	    ));
	    $this->view->display('member/form_list');
	}
	
	/*
	 * 查看表单内容
	 */
	public function formshowAction() {
		$modelid = (int)$this->get('modelid');
		if (empty($modelid)) $this->memberMsg(lang('m-con-26'));
	    $fmodel  = $this->cache->get('formmodel');
		$model   = $fmodel[$modelid];
		if (empty($model)) $this->memberMsg(lang('m-con-27', array('1'=>$modelid)));
	    $id      = (int)$this->get('id');
		if (empty($id)) $this->memberMsg(lang('m-con-28'));
		$form    = $this->model($model['tablename']);
		$data    = $form->find($id);
		if (empty($data)) $this->memberMsg(lang('m-con-29'));
		if (($data['username'] ==  $this->memberinfo['username'] && $data['userid'] == $this->memberinfo['id']) || $form->count($model['tablename'], 'id', '`status`=1 AND `id`=' . $id . ' AND `cid` IN (SELECT `id` FROM `' . $this->content->prefix . 'content` WHERE `modelid`=' . $this->form[$modelid]['joinid'] . ' AND `sysadd`=0 AND `status`=1 AND `userid`=' . $this->memberinfo['id'] . ' AND `username`="' . $this->memberinfo['username'] . '")')) {
		    //判断发布人名称和id，或者判断该信息是否是当前登陆用户发布文档相关联的内容
			$this->view->assign(array(
				'data'        => $data,
				'form'        => $model,
				'burl'        => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/content/form/', array('modelid'=>$modelid)),
				'modelid'     => $modelid,
				'meta_title'  => $model['joinname'] . $model['modelname'] . '-' . lang('member') . '-' . $this->site['SITE_NAME'],
				'data_fields' => $this->getFields($model['fields'], $data),
			));
			$this->view->display('member/form_show');
		} else {
		    $this->memberMsg(lang('m-con-29'));
		}
	}
	
    /**
	 * 标题是否重复检查
	 */
	public function ajaxtitleAction() {
	    $title = $this->post('title');
	    $id    = $this->post('id');
	    if (empty($title)) exit(lang('m-con-31'));
	    $where = $id ? "title='" . $title . "' and id<>" . $id : "title='" . $title . "'";
	    $data  = $this->content->getOne($where); 
	    if ($data) exit(lang('m-con-32'));
	    exit(lang('m-con-33'));
	}
	
	/**
	 * 发布数量检测
	 */
	private function postCheck() {
		$count = $this->model('member_count');
		$data  = $count->find($this->memberinfo['id']);
		if (empty($data)) return true;
		if (date('Y-m-d') != date('Y-m-d', $data['updatetime'])) {
		    //重置统计数据
			$data['post']       = 0;
			$data['updatetime'] = time();
			$count->update($data, 'id=' . $this->memberinfo['id']);
		}
		if ($data['post'] >= $this->group['allowpost']) $this->memberMsg(lang('m-con-34', array('1'=>$this->group['allowpost'])));
	}
	
	/**
	 * 获取发布数量
	 */
	private function getPosts() {
		$count = $this->model('member_count');
		$data  = $count->find($this->memberinfo['id']);
		if (empty($data)) $count->insert(array('id'=>$this->memberinfo['id']));
		if (date('Y-m-d') != date('Y-m-d', $data['updatetime'])) {
		    //重置统计数据
			$data['post']       = 0;
			$data['updatetime'] = time();
			$count->update($data, 'id=' . $this->memberinfo['id']);
		}
		return array('post'=>$data['post'], 'posts'=>$this->group['allowpost']);
	}
	
}