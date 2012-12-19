<?php
/**
 * 内容采集导入
 * @author Administrator
 */

class ImportController extends Common {

	public function __construct() {
        parent::__construct();
	}
	
	public function categoryAction() {
	    echo "<select name='catid'>";
		foreach ($this->cats as $cat) {
		    if ($cat['typeid']==1 && $cat['child']==0) echo "<option value='" . $cat['catid'] . "'>" . $cat['catname'] . "</option>";
		}
		echo "</select>";
	}
	
	public function contentAction() {
	    //验证权限
	    $username = $this->post('username');
		$password = $this->post('password');
		$user     = $this->model('user');
		$userid   = $user->check_login($username, $password);
		if (!$userid) exit('您没有此权限');
	    //参数
	    $catid    = $this->post('catid');
	    $title    = $this->post('title');
	    $content  = $this->post('content');
	    $modelid  = $this->cats[$catid]['modelid'];
	    $model    = $this->cache->get('model');
	    $table    = $model[$modelid]['tablename'];
		$content  = preg_replace('/<a (.*)>(.*)<\a>/iU', '$2', $content); //去掉A标签
	    $title   or exit('标题不能为空');
	    $catid   or exit('栏目不能为空');
	    $content or exit('内容不能为空');
	    $table   or exit('模型不存在');
	    //入库
	    $data     = array(
	        'title'      => $title,
	        'catid'      => $catid,
	        'content'    => $content,
	        'status'     => 0,
	        'inputtime'  => time(),
	        'updatetime' => time(),
	        'keywords'   => getKw($title),
	        'sysadd'     => 1,
	        'userid'     => $userid,
	        'username'   => $username,
	        'modelid'    => $modelid,
	    );
	    $content = $this->model('content');
		$row     = $content->getOne("title='" . $data['title'] . "'");
		if ($row) exit('已经存在');
	    $id      = $content->set(0, $table, $data);
	    if (!is_numeric($id)) exit('添加失败');
	    $data['id'] = $id;
	    $content->url($id, $this->getUrl($data));
	    exit('发布成功');
	}
	
}