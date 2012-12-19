<?php

class ContentController extends Common {
    
    public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 栏目列表页
	 */
	public function listAction() {
	    $catid  = (int)$this->get('catid');
	    $catdir = $this->get('catdir');
	    $page   = (int)$this->get('page');
	    $page   = $page ? $page : 1;
	    if ($catdir && empty($catid)) $catid = $this->cats_dir[$catdir];
	    $cat    = $this->cats[$catid];
	    if (empty($cat)) {
		    header('HTTP/1.1 404 Not Found');
			$this->msg(lang('con-0', array('1'=>($catdir && empty($catid) ? $catdir : $catid))));
		}
	    $seo    = listSeo($cat, $page);
	    $this->view->assign($cat);
	    $this->view->assign($seo);
	    if ($cat['typeid'] == 1) {
	        //内部栏目
	        $this->view->assign(array(
	            'page'    => $page,
	            'catid'   => $catid,
	            'pageurl' => urlencode($this->getCaturl($cat, '{page}'))
	        ));
	        $this->view->display(substr(($cat['child'] == 1 ? $cat['categorytpl'] : $cat['listtpl']), 0, -5));
	    } elseif ($cat['typeid'] == 2) {
	        //单网页
	        $cat['content'] = relatedlink($cat['content']);
	        $this->view->display(substr($cat['showtpl'], 0, -5));
	    } else {
	        //外部链接
	        header('Location: ' . $cat['url']);
	    }
	}
	
	/**
	 * 内容详细页
	 */
	public function showAction() {
	    $page  = (int)$this->get('page');
	    $page  = $page ? $page : 1;
	    $id    = (int)$this->get('id');
	    $data  = $this->content->find($id);
	    $model = $this->cache->get('model');
	    if (empty($data)) {
		    header('HTTP/1.1 404 Not Found');
		    $this->msg(lang('con-1', array('1'=>$id)));
		}
	    if (!$this->userShow($data)) $this->msg(lang('con-2', array('1'=>$id)));
		if (!isset($model[$data['modelid']]) || empty($model[$data['modelid']])) $this->msg(lang('con-3', array('1'=>$id)));
	    $catid = $data['catid'];
	    $cat   = $this->cats[$catid];
	    $table = $this->model($cat['tablename']);
	    $_data = $table->find($id);
	    $data  = array_merge($data, $_data); //合并主表和附表
		$data  = $this->getFieldData($model[$cat['modelid']], $data);
		if (isset($data['content']) && strpos($data['content'], '{-page-}') !== false) {
			$content  = explode('{-page-}', $data['content']);
			$pageid   = count($content) >= $page ? ($page - 1) : (count($content) - 1);
			$data['content'] = $content[$pageid];
			$page_id  = 1;
			$pagelist = array();
			foreach ($content as $t) {
				$pagelist[$page_id] = getUrl($data, $page_id);
				$page_id ++ ;
			}
			$this->view->assign('contentpage', $pagelist);
		}
	    $data['content'] = relatedlink($data['content']);
		$prev_page = $this->content->getOne("`catid`=$catid AND `id`<$id AND `status`=1 ORDER BY `updatetime` DESC", null, 'title,url,catid');
		$next_page = $this->content->getOne("`catid`=$catid AND `id`>$id AND `status`=1 ORDER BY `updatetime` DESC", null, 'title,url,catid');
	    $seo = showSeo($data, $page);
	    $this->view->assign(array(
	        'cat'       => $cat,
	        'page'      => $page,
	        'prev_page' => $prev_page,
	        'next_page' => $next_page,
	        'pageurl'   => urlencode(getUrl($data, '{page}'))
	    ));
		if ($data['status']==0) $seo['meta_title'] = '[' . lang('con-4') . '] ' . $seo['meta_title'];
	    $this->view->assign($data);
	    $this->view->assign($seo);
	    $this->view->display(substr($cat['showtpl'], 0, -5));
	}
	
	/**
	 * 内容搜索
	 */
	public function searchAction() {
	    $param = $this->getParam();
		$kw    = $this->get('kw');
		$kw    = urldecode($kw);
		$page  = (int)$this->get('page') > 0 ? (int)$this->get('page') : 1;
		$sql   = null;
		if ($this->site['SITE_SEARCH_TYPE'] == 2) {
		    //Sphinx
			if (empty($kw)) $this->msg(lang('con-5'));
		    App::auto_load('sphinxapi');
            $cl   = new SphinxClient ();
			$host = $this->site['SITE_SEARCH_SPHINX_HOST'];
			$prot = $this->site['SITE_SEARCH_SPHINX_PORT'];
			$name = $this->site['SITE_SEARCH_SPHINX_NAME'];
			$start= ($page - 1) * (int)$this->site['SITE_SEARCH_PAGE'];
			$limit= (int)$this->site['SITE_SEARCH_PAGE'];
            $cl->SetServer($host, 9312);
            $cl->SetMatchMode(SPH_MATCH_ALL);
            $cl->SetSortMode(SPH_SORT_EXTENDED, 'updatetime DESC');
            $cl->SetLimits($start, $limit);
			$res = $cl->Query($kw, $this->site['SITE_SEARCH_SPHINX_NAME']);
			if ($res['total']) {
			    $ids     = '';
				foreach ($res['matches'] as $cid=>$val) {
				    $ids .= $cid . ',';
				}
				$ids     = substr($ids, -1) == ',' ? substr($ids, 0, -1) : $ids;
			    $total   = $res['total'];
				$pageurl = $this->site['SITE_SEARCH_URLRULE'] ? str_replace('{id}', urlencode($kw), $this->site['SITE_SEARCH_URLRULE']) : url('content/search', array('kw'=>urlencode($kw), 'page'=>'{page}'));
				$sql     = 'SELECT id,modelid,catid,url,thumb,title,keywords,description,username,updatetime,inputtime from ' . $this->content->prefix . 'content WHERE id IN (' . $ids . ') ORDER BY updatetime DESC LIMIT ' . $limit;
			}
		} else {
		    //普通搜索
		    $search  = $this->model('search');
			$start   = ($page - 1) * (int)$this->site['SITE_SEARCH_PAGE'];
			$limit   = $this->site['SITE_SEARCH_PAGE'] ? (int)$this->site['SITE_SEARCH_PAGE'] : 10;
			$cache   = (int)$this->site['SITE_SEARCH_INDEX_CACHE'];
		    $result  = $search->getData((int)$this->get('id'), $cache, $param, $start, $limit, $this->site['SITE_SEARCH_KW_FIELDS'], $this->site['SITE_SEARCH_KW_OR']);
			$total   = $result['total'];
			$sql     = $result['sql'];
			$kw      = $result['keywords'];
			$pageurl = $this->site['SITE_SEARCH_URLRULE'] ? str_replace('{id}', $result['id'], $this->site['SITE_SEARCH_URLRULE']) : url('content/search', array('id'=>$result['id'], 'page'=>'{page}'));
		}
		if ($sql) {
			$pagelist = $this->instance('pagelist');
			$pagelist->loadconfig();
			$data     = $this->content->execute($sql, true, $this->site['SITE_SEARCH_DATA_CACHE']);
			$pagelist = $pagelist->total($total)->url($pageurl)->num($this->site['SITE_SEARCH_PAGE'])->page($page)->output();
	    } else {
		    $data     = array();
			$total    = 0;
			$pagelist = '';
		}
	    $seo = listSeo($cat, $page, $kw);
	    $this->view->assign($seo);
	    $this->view->assign(array(
	        'searchpage' => $pagelist,
	        'searchdata' => $data,
			'searchnums' => $total,
			'kw'         => $kw,
			'model'      => $this->cache->get('model'),
	    ));
	    $this->view->display('search');
	}
	
}