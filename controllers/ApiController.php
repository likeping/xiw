<?php

class ApiController extends Common {

	public function __construct() {
        parent::__construct();
	}
	
	/**
	  * Jquery-autocomplete插件搜索提示
	  */
	public function searchAction() {
	    $kw  = str_replace(' ', '%', urldecode($this->get('q')));
		$mid = (int)$this->get('modelid');
		if ($kw) {
			$query = $this->content->where('title like "%' . $kw . '%"');
			$query->where('status=1');
			if ($mid) $query->where('modelid=' . $mid);
			$data  = $query->order('updatetime desc')->limit(10)->select();
			if ($data) {
			    foreach ($data as $t) {
				    echo $t['title'] . PHP_EOL;
				}
			}
		}
	}

	/**
	  * 会员登录信息JS调用
	  */
	public function userAction() {
	    ob_start();
		$this->view->display('user');
		$html = ob_get_contents();
		ob_clean();
		$html = addslashes(str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $html));
	    echo 'document.write("' . $html . '");';
	}
	
	/**
	  * 更新浏览数
	  */
	public function hitsAction() {
	    $id   = (int)$this->get('id');
		if (empty($id))   exit('0');
		$data = $this->content->find($id, 'hits');
		if (empty($data)) exit('0');
		$hits = $data['hits'] + 1;
		$this->content->update(array('hits'=>'hits+1'), 'id=' . $id);
		echo "document.write('$hits');";
	}
	
	/**
	  * 验证码
	  */
	public function captchaAction() {
	    $width  = $this->get('width');
	    $height = $this->get('height');
	    $api    = $this->instance('captcha');
	    if ($width)  $api->width  = $width;
	    if ($height) $api->height = $height;
	    $api->doimage();
        $this->session->set('captcha', $api->get_code());
	}
	
	/**
	 * 生成拼音
	 */
	public function pinyinAction() {
		echo word2pinyin($this->post('name'));
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
	 * 联动菜单数据
	 */
	public function linkageAction() {
	    $parentid = (int)$this->get('parent_id');
	    $keyid    = (int)$this->get('id');
	    $linkage  = $this->cache->get('linkage');
	    $infos    = $linkage[$keyid]['data'];
	    $json     = array();
		foreach ($infos as $k=>$v) {
			if ($v['parentid'] == $parentid) {
				$json[] = array('region_id' => $v['id'], 'region_name' => $v['name']);
			}
		}
		echo json_encode($json);	
	}
	
	/*
	 * 百度地图调用
	 */
	public function baidumapAction() {
		$apikey = $this->get('apikey');
		$name   = $this->get('name');
		$city   = $this->get('city');
		$value  = $this->get('value');
		$this->view->assign(array(
		    'apikey' => $apikey,
			'city'   => $city,
			'name'   => $name,
			'value'  => $value,
		));
		$this->view->display('../admin/baidumap');
	}
	
	/*
	 * 加入收藏夹
	 */
	public function addfavoriteAction() {
		$id = $this->post('id');
		if (empty($id)) exit(lang('api-0'));
		if (!$this->memberinfo) exit(lang('api-1'));
		$db   = $this->model('favorite');
		$row  = $db->getOne('userid=' . $this->memberinfo['id'] . ' and contentid=' . $id, null, array('id'));
		if ($row) exit(lang('api-2'));
		$data = $this->content->find($id, 'title,url');
		if (empty($data)) exit(lang('api-3'));
		$db->insert(array('title'=>$data['title'], 'url'=>$data['url'], 'contentid'=>$id, 'userid'=>$this->memberinfo['id'], 'adddate'=>time()));
		exit(lang('api-4'));
	}
	
	/*
	 * CMS信息
	 */
	public function indexAction() {
	    echo '版本：' . CMS_NAME . ' v' . CMS_VERSION . '<br>';
		echo '更新：' . CMS_UPDATE . '<br>';
		echo '校正：' . date('Y-m-d H:i:s', @filemtime(APP_ROOT . 'config/version.ini.php'));
	}
	
}