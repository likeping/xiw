<?php

class ContentController extends Admin {
    
    protected $category;
    protected $content;
    protected $tree;
    
    public function __construct() {
		parent::__construct();
		$this->category = $this->model('category');
		$this->content  = $this->model('content');
		$this->tree     = $this->instance('tree');
		$this->tree->config(array('id'=>'catid', 'parent_id'=>'parentid', 'name'=>'catname'));
	}
	
	public function indexAction() {
	    if ($this->post('submit') && $this->post('form')=='search') {
	        $kw    = $this->post('kw');
	        $catid = $this->post('catid');
			$stype = $this->post('stype');
	    } elseif ($this->post('submit_order') && $this->post('form')=='order') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'order_')!==false) {
	                $id = (int)str_replace('order_', '', $var);
	                $this->content->update(array('listorder'=>$value), 'id=' . $id);
	            }
	        }
	    } elseif ($this->post('submit_del') && $this->post('form')=='del') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $ids = str_replace('del_', '', $var);
	                list($_id, $_catid) = explode('_', $ids);
	                $this->delAction($_id, $_catid, 1);
	            }
	        }
	    } elseif ($this->post('submit_status_1') && $this->post('form')=='status_1') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $ids = str_replace('del_', '', $var);
	                list($_id, $_catid) = explode('_', $ids);
	                $this->content->verify($_id);
			        $this->toHtml($_id);
	            }
	        }
	    } elseif ($this->post('submit_status_0') && $this->post('form')=='status_0') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $ids = str_replace('del_', '', $var);
	                list($_id, $_catid) = explode('_', $ids);
	                $this->content->update(array('status'=>0), 'id=' . $_id);
			        $this->toHtml($_id);
	            }
	        }
	    } elseif ($this->post('submit_status_2') && $this->post('form')=='status_2') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $ids = str_replace('del_', '', $var);
	                list($_id, $_catid) = explode('_', $ids);
	                $this->content->update(array('status'=>2), 'id=' . $_id);
			        $this->toHtml($_id);
	            }
	        }
	    } elseif ($this->post('submit_status_3') && $this->post('form')=='status_3') {
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $ids = str_replace('del_', '', $var);
	                list($_id, $_catid) = explode('_', $ids);
	                $this->content->update(array('status'=>3), 'id=' . $_id);
			        $this->toHtml($_id);
	            }
	        }
	    } elseif ($this->post('submit_move') && $this->post('form')=='move') {
		    $mcatid = $this->post('movecatid');
			if (empty($mcatid)) $this->adminMsg(lang('a-con-0'));
			$mcat   = $this->cats[$mcatid];
			$mtable = $this->model($mcat['tablename']);
	        foreach ($_POST as $var=>$value) {
	            if (strpos($var, 'del_')!==false) {
	                $ids = str_replace('del_', '', $var);
	                list($_id, $_catid) = explode('_', $ids);
	                $cat = $this->cats[$_catid];
					if ($cat['modelid'] == $mcat['modelid']) { //执行移动
						$this->content->update(array('catid'=>$mcatid), 'id=' . $_id);
						$mtable->update(array('catid'=>$mcatid), 'id=' . $_id);
			            $this->toHtml($_id);
					}
	            }
	        }
	    }
	    $kw       = $kw    ? $kw : $this->get('kw');
	    $catid    = $catid ? $catid : (int)$this->get('catid');
	    $stype    = isset($stype) ? $stype : (int)$this->get('stype');
	    $page     = (int)$this->get('page');
		$page     = (!$page) ? 1 : $page;
		$modelid  = (int)$this->get('modelid');
		$status   = (int)$this->get('status');
	    $pagelist = $this->instance('pagelist');
		$pagelist->loadconfig();
		if (empty($modelid)) $this->adminMsg(lang('a-con-1'));
		$model    = $this->cache->get('model');
		if (!isset($model[$modelid])) $this->adminMsg(lang('a-con-2', array('1'=>$modelid)));
	    $where    = null;
		if ($catid) $where .= 'catid=' . $catid;
	    $where   .= (is_null($where) ? '' : ' AND ') . 'modelid=' . $modelid;
	    if ($status == 1) {
		    $where .= ' and status=1';
		} elseif ($status ==2) {
		    $where .= ' and status=0';
		} elseif ($status ==3) {
		    $where .= ' and status=2';
		} elseif ($status ==4) {
		    $where .= ' and status=3';
		}
		if ($kw && $stype == 0) {
		    $where .= " and title like '%" . $kw . "%'";
		} elseif ($kw && $stype == 1) {
		    $where .= " and username='" . $kw . "' and sysadd=0";
		} elseif ($kw && $stype == 2) {
		    $where .= " and username='" . $kw . "' and sysadd=1";
		}
	    $total    = $this->content->count('content', null, $where);
	    $pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
	    $urlparam = array();
	    if ($kw)      $urlparam['kw']      = $kw;
	    if ($stype)   $urlparam['stype']   = $stype;
	    if ($catid)   $urlparam['catid']   = $catid;
	    if ($modelid) $urlparam['modelid'] = $modelid;
		$urlparam['status'] = $status;
	    $urlparam['page']   = '{page}';
	    $url      = url('admin/content/index', $urlparam);
	    $select   = $this->content->page_limit($page, $pagesize)->order(array('listorder DESC', 'updatetime DESC'));
		if ($catid)   $select->where('catid=' . $catid);
		if ($modelid) $select->where('modelid=' . $modelid);
		if ($status == 1) {
		    $select->where('status=1');
		} elseif ($status ==2) {
		    $select->where('status=0');
		} elseif ($status ==3) {
			$select->where('status=2');
		} elseif ($status ==4) {
			$select->where('status=3');
		}
		if ($kw && $stype == 0) {
		    $select->where("title like '%" . $kw . "%'");
		} elseif ($kw && $stype == 1) {
			$select->where("username=? AND sysadd=0", $kw);
		} elseif ($kw && $stype == 2) {
			$select->where("username=? AND sysadd=1", $kw);
		}
	    $data     = $select->select();
	    $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
		$count    = array();
		$count[0] = $this->content->count('content', null, 'modelid=' . $modelid);
		$count[1] = $this->content->count('content', null, 'modelid=' . $modelid . ' AND status=1');
		$count[2] = $this->content->count('content', null, 'modelid=' . $modelid . ' AND status=0');
		$count[3] = $this->content->count('content', null, 'modelid=' . $modelid . ' AND status=2');
		$count[4] = $this->content->count('content', null, 'modelid=' . $modelid . ' AND status=3');
	    $this->view->assign(array(
	        'category' => $this->tree->get_model_tree($this->cats, 0, null, '|-', $modelid),
	        'list'     => $data,
	        'catid'    => $catid,
	        'kw'       => $kw,
	        'page'     => $page,
	        'pagelist' => $pagelist,
			'status'   => $status,
			'count'    => $count,
			'modelid'  => $modelid,
			'total'    => $total,
			'model'    => $model[$modelid],
			'join'     => $this->getModelJoin($modelid),
	    ));
	    $this->view->display('admin/content_list');
	}
	
	/**
	 * 发布
	 */
	public function addAction() {
	    $model    = $this->cache->get('model');
	    $modelid  = $this->get('modelid');
	    if (!isset($model[$modelid])) $this->adminMsg(lang('a-con-3'));
	    $fields   = $model[$modelid]['fields'];
	    if ($this->post('submit')) {
	        $data = $this->post('data');
		    if (empty($data['catid'])) $this->adminMsg(lang('a-con-4'));
	        if (empty($data['title'])) $this->adminMsg(lang('a-con-5'));
	        if ($this->cats[$data['catid']]['modelid'] != $modelid) $this->adminMsg(lang('a-con-6'));
			$this->checkFields($fields, $data, 1);
			if ($this->post('updatetime') == 2 || $this->post('updatetime') == 1) {
			    $data['updatetime'] = time();
			} elseif ($this->post('updatetime') == 3) {
			    $data['updatetime'] = $data['select_time'];
			}
	        $data['username']  = $this->userinfo['username'];
	        $data['inputtime'] = time();
	        $data['sysadd']    = 1;
	        $data['modelid']   = $modelid;
	        $data['relation']  = formatStr($data['relation']);
			$data['position']  = @implode(',', $data['position']);
	        $result            = $this->content->set(0, $model[$modelid]['tablename'], $data);
	        if (!is_numeric($result)) $this->adminMsg($result);
	        $data['id']        = $result;
	        $this->content->url($result, $this->getUrl($data));
	        if ($this->site['SITE_MAP_AUTO'] == true) $this->sitemap();
			$this->toHtml($data['id']);
			$this->setPosition($data['position'], $result, $data);
			$msg = '<a href="' . url('admin/content/add', array('catid'=>$data['catid'], 'modelid'=>$modelid)) . '" style="font-size:14px;">' . lang('a-con-7') . '</a>&nbsp;&nbsp;<a href="' . url('admin/content/index', array('modelid'=>$modelid)) . '" style="font-size:14px;">' . lang('a-con-8') . '</a>';
	        $this->adminMsg(lang('a-con-9') . '<div style="padding-top:10px;">' . $msg . '</div>', '', 3, 0, 1);
	    }
	    $data_fields      = $this->getFields($fields, $data);
		$position         = $this->model('position');
	    $this->view->assign(array(
	        'category'    => $this->tree->get_model_tree($this->cats, 0, $this->get('catid'), '|-', $modelid),
	        'data_fields' => $data_fields,
			'position'    => $position->findAll(),
			'modelid'     => $modelid,
			'data'        => array('catid'=>$this->get('catid')),
			'model'       => $model[$modelid],
	    ));
	    $this->view->display('admin/content_add');
	}
	
	/**
	 * 修改
	 */
    public function editAction() {
	    $id       = $this->get('id');
	    $data     = $this->content->find($id);
	    if (empty($data)) $this->adminMsg(lang('a-con-10'));
	    $catid    = $data['catid'];
	    $model    = $this->cache->get('model');
	    $modelid  = $data['modelid'];
	    if (!isset($model[$modelid])) $this->adminMsg(lang('a-con-3'));
	    $fields   = $model[$modelid]['fields'];
	    $url      = $this->getUrl($data);
	    if ($this->post('submit')) {
		    $posi = $data['position'];
	        unset($data);
	        $data = $this->post('data');
	        if (empty($data['title'])) $this->adminMsg(lang('a-con-5'));
	        if ($data['catid'] != $catid && $modelid != $this->cats[$data['catid']]['modelid']) $this->adminMsg(lang('a-con-6'));
			$this->checkFields($fields, $data, 1);
			if ($this->post('updatetime') == 2) {
			    $data['updatetime'] = time();
			} elseif ($this->post('updatetime') == 3) {
			    $data['updatetime'] = $data['select_time'];
			}
	        $data['url']        = $url;
	        $data['modelid']    = (int)$modelid;
	        $data['relation']   = formatStr($data['relation']);
			$data['position']   = @implode(',', $data['position']);
	        $result             = $this->content->set($id, $model[$modelid]['tablename'], $data);
	        if (!is_numeric($result)) $this->adminMsg($result);
	        if ($this->site['SITE_MAP_AUTO'] == true) $this->sitemap();
			$this->toHtml($id);
			$this->setPosition($data['position'], $result, $data, $posi);
	        $this->adminMsg(lang('success'), ($this->post('backurl') ? $this->post('backurl') : url('admin/content/index', array('modelid'=>$modelid))), 3, 1, 1);
	    }
	    //附表内容
	    $table       = $this->model($model[$modelid]['tablename']);
	    $table_data  = $table->find($id);
	    if ($table_data) $data = array_merge($data, $table_data); //合并主表和附表
	    //自定义字段
	    $data_fields = $this->getFields($fields, $data);
		$position    = $this->model('position');
	    $this->view->assign(array(
	        'data'         => $data,
	        'category'     => $this->tree->get_model_tree($this->cats, 0, $catid, '|-', $modelid),
	        'data_fields'  => $data_fields,
	        'relation_ids' => ',' . $data['relation'],
			'position'     => $position->findAll(),
			'modelid'      => $modelid,
			'backurl'      => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
			'model'        => $model[$modelid],
	    ));
	    $this->view->display('admin/content_add');
	}
	
	/**
	 * 删除
	 */
	public function delAction($id=0, $catid=0, $all=0) {
        if (!auth::check($this->roleid, 'content-del', 'admin')) $this->adminMsg(lang('a-com-0', array('1'=>'content', '2'=>'del')));
	    $id    = $id    ? $id    : $this->get('id');
	    $catid = $catid ? $catid : $this->get('catid');
	    $all   = $all   ? $all   : $this->get('all');
		$back  = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('admin/content/', array('modelid'=>$this->cats[$catid]['modelid']));
	    $this->content->del($id, $catid);
		$pos   = $this->model('position_data');
		$pos->delete('contentid=' . $id);
	    $all or $this->adminMsg(lang('success'), $back, 3, 1, 1);
	}
	
	/**
	 * 获取关键字
	 */
	public function ajaxkwAction() {
	    $data = $this->post('data');
	    if (empty($data)) exit('');
	    echo getKw($data);
	}
	
    /**
	 * 标题是否重复检查
	 */
	public function ajaxtitleAction() {
	    $title = $this->post('title');
	    $id    = $this->post('id');
	    if (empty($title)) exit(lang('a-con-11'));
	    $where = $id ? "title='" . $title . "' and id<>" . $id : "title='" . $title . "'";
	    $data  = $this->content->getOne($where); 
	    if ($data) exit(lang('a-con-12'));
	    exit(lang('a-con-13'));
	}
	
	/**
	 * 加载内容表中的信息
	 */
	public function ajaxloadinfoAction() {
	    $kw     = urldecode($this->get('kw'));
	    $select = $this->content->order('updatetime DESC')->limit(0, 20);
	    $title  = $this->post('title');
		$catid  = $this->post('catid');
		$select->where('`status`=1');
	    if ($title) $select->where('title like "%' . $title . '%"');
		if ($catid && $this->cats[$catid]['arrchilds']) $select->where('catid IN (' . $this->cats[$catid]['arrchilds'] . ')');
		if (empty($title) && $kw) {
		    $kw = explode(',', $kw);
			$i  = 1;
			foreach ($kw as $keyword) {
			    $i ?  $select->where('title like "%' . $keyword . '%"') : $select->orwhere('title like "%' . $keyword . '%"');
				$i = 0;
			}
		}
	    $this->view->assign(array(
			'list'     => $select->select(),
		    'category' => $this->tree->get_tree($this->cats, 0, null, '&nbsp;|-', 0),
		));
	    $this->view->display('admin/content_data_load');
	}
	
	/**
	 * 更新url地址
	 */
	public function updateurlAction() {
	    if ($this->isPostForm()) {
			$catids = $this->post('catids');
			$cats   = null;
			if ($catids && !in_array(0, $catids)) {
			    $cats = @implode(',', $catids);
			} else {
			    foreach ($this->cats as $c) {
				    if ($c['typeid'] == 1) $cats[$c['catid']] = $c['catid'];
				}
				$cats = @implode(',', $cats);
			}
			if (empty($cats)) {
			    echo '
				<style type="text/css">div, a { color: #777777;}</style>
			    <div style="font-size:12px;padding-top:0px;">
				<font color=red><b>' . lang('a-con-14') . '<b></font>
				</div>
				';
				exit;
			}
			$url = url('admin/content/updateurl', array('submit'=>1, 'catids'=>$cats, 'nums'=>$this->post('nums')));
			echo '
			<style type="text/css">div, a { color: #777777;}</style>
			<div style="font-size:12px;padding-top:0px;">
			<a href="' . $url . '">' . lang('a-con-15') . '</a>
			<meta http-equiv="refresh" content="0; url=' . $url . '">
			</div>
			</div>
			';
			exit;
		}
		if ($this->get('submit')) {
			$mark   = 0;
			$cats   = array();
			$catids = $this->get('catids');
			$cats   = @explode(',', $catids);
			$catid  = $this->get('catid') ? $this->get('catid')  : $cats[0];
			$cat    = isset($this->cats[$catid]) ? $this->cats[$catid] : null;
			if (!$cat) {
			    echo '
				<style type="text/css">div, a { color: #777777;}</style>
			    <div style="font-size:12px;padding-top:0px;">
				<font color=green><b>' . lang('a-con-16') . '<b></font>
				</div>
				';
				exit;
			}
		    $page  = $this->get('page') ? $this->get('page') : 1;
			$nums  = $this->get('nums') ? $this->get('nums') : 100;
			$where = 'catid IN (' . $cat['arrchilds'] . ')';
			$count = $this->content->count('content', 'id', $where);
	        $total = ceil($count/$nums);
			$list  = $this->content->from('content', 'id,inputtime,catid')->where($where)->page_limit($page, $nums)->select();
			if (empty($list)) {
			    $mark = $_catid = 0;
				foreach ($cats as $c) {
					if ($catid == $c) {
						$mark = 1;
						continue;
					}
					if ($mark == 1) {
					    $_catid = $c;
						break;
					}
				}
			    if (!isset($this->cats[$_catid])) {
				    echo '
					<style type="text/css">div, a { color: #777777;}</style>
			        <div style="font-size:12px;padding-top:0px;">
					<font color=green><b>' . lang('a-con-16') . '<b></font>
					</div>
					';
					exit;
				}
				$url = url('admin/content/updateurl', array('submit'=>1, 'nums'=>$nums, 'page'=>1, 'catid'=>$_catid, 'catids'=>$catids));
				echo '
				<style type="text/css">div, a { color: #777777;}</style>
			    <div style="font-size:12px;padding-top:0px;">
				<a href="' . $url . '">' . lang('a-con-17', array('1'=>$this->cats[$_catid]['catname'])) . '</a>
				<meta http-equiv="refresh" content="0; url=' . $url . '">
				</div>
				';
				exit;
			} else {
			    foreach ($list as $t) {
                    $this->content->update(array('url'=>$this->getUrl($t)), 'id=' . $t['id']);
				}
				$url = url('admin/content/updateurl', array('submit'=>1, 'nums'=>$nums, 'page'=>$page+1, 'catid'=>$catid, 'catids'=>$catids));
				echo '
				<style type="text/css">div, a { color: #777777;}</style>
			    <div style="font-size:12px;padding-top:0px;">
				<a href="' . $url . '">' . lang('a-con-18', array('1'=>$this->cats[$catid]['catname'], '2'=>$page, '3'=>$total)) . '</a>
				<meta http-equiv="refresh" content="0; url=' . $url . '">
				</div>
				';
				exit;
			}
		} else {
		    $this->view->assign('category',$this->tree->get_tree($this->cats, 0, null, '&nbsp;|-', true));
			$this->view->display('admin/content_url');
		}
	}
	
	/**
	 * 删除/生成HTML
	 */
	private function toHtml($id) {
	    if (empty($id)) return false;
		$this->createShow($id);
	}
	
	/**
	 * 增加/删除推荐位
	 */
	private function setPosition($insert_ids, $cid, $data, $position=null) {
	    if (empty($cid)) return false;
	    $arrid = @explode(',', $insert_ids);
		$pos   = $this->model('position_data');
		//增加推荐位
		if (is_array($arrid)) {
			foreach ($arrid as $sid) {
				if ($sid) {
					$row = $pos->from(null, 'id')->where('posid=' . $sid . ' and contentid=' . $cid)->select(false);
					if ($row) {
						$set = array(
							'catid'       => $data['catid'],
							'title'       => $data['title'],
							'url'         => $data['url'],
							'thumb'       => $data['thumb'],
							'description' => $data['description'],
						);
						$pos->update($set, 'id=' . $row['id']);
					} else {
						$set = array(
							'catid'       => $data['catid'],
							'title'       => $data['title'],
							'url'         => $data['url'],
							'thumb'       => $data['thumb'],
							'description' => $data['description'],
							'contentid'   => $cid,
							'posid'       => $sid,
						);
						$pos->insert($set);
					}
				}
			}
		}
		//删除推荐位
		$old_ids  = @explode(',', $position);
		if (is_array($old_ids)) {
		    foreach ($old_ids as $sid) {
			    if (!in_array($sid, $arrid) && $sid) {
				    $pos->delete('posid=' . $sid . ' AND contentid=' . $cid);
				}
			}
		}
		//更新缓存
		$pmodel   = $this->model('position');
		$position = $pmodel->findAll();
	    $data     = array();
	    foreach ($position as $t) {
	        $posid        = $t['posid'];
	        $data[$posid] = $pos->where('posid=' . $posid)->order('listorder ASC, id DESC')->select();
	        $data[$posid]['maxnum'] = $t['maxnum'];
	        $data[$posid]['catid']  = $t['catid'];
	    }
	    //写入缓存文件中
	    $this->cache->set('position', $data);
	}
}