<?php

class RegisterController extends Member {
    
    public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 注册
	 */
	public function indexAction() {
	    if (!$this->memberconfig['register']) $this->memberMsg(lang('m-reg-0'));
	    if (!$this->isLogin(1)) $this->msg(lang('m-reg-1'), url('member/'));
	    if ($this->isPostForm()) {
		    $data = $this->post('data');
			if ($this->memberconfig['regcode'] && !$this->checkCode($this->post('code'))) $this->memberMsg(lang('for-4'));
			$this->check($data);
			$uid  = $this->reg($data);
			if (empty($uid)) $this->memberMsg(lang('m-reg-2'));
			$this->regEmail($data); //注册邮件提示
			cookie::set('member_id', $uid, 24*3600); //登录cookie
			cookie::set('member_code', substr(md5(SITE_MEMBER_COOKIE . $uid), 5, 20), $time);
			$this->memberMsg(lang('m-reg-3'), url('member'), 1);
		}
		$this->view->assign(array(
		    'membermodel' => $this->membermodel,
			'meta_title'  => lang('m-reg-4') . '-' . $this->site['SITE_NAME'],
		));
		$this->view->display('member/register');
	}
	
	/**
	 * 一键登录绑定会员
	 */
	public function bangAction() {
	    $type = $this->post('type');
		$data = $this->post('data');
		if ($type == 'bang') {
		    //绑定会员
			$member = $this->member->where('username=?', $data['username'])->where('password=?', md5($data['password']))->select(false);
			if ($member) {
			    $config = $this->loadOauth();
		        $row    = get_user_info($config);
				$row['username'] = $member['username'];
				$row['nickname'] = $row['name'];
			    $this->bang($row);
				//登录cookie
			    cookie::set('member_id', $member['id'], 24*3600);
				cookie::set('member_code', substr(md5(SITE_MEMBER_COOKIE . $member['id']), 5, 20), $time);
				$this->memberMsg(lang('m-reg-5'), url('member'), 1);
			} else {
			    $this->memberMsg(lang('m-reg-6'));
			}
		} elseif ($type == 'reg') {
		    //注册会员
			$this->check($data);
			$uid = $this->reg($data);
			if (empty($uid)) $this->memberMsg(lang('m-reg-2'));
			$data['id'] = $uid;
			$this->bang($data);
			$this->regEmail($data); //注册邮件提示
			//登录cookie
			cookie::set('member_id', $uid, 24*3600);
			cookie::set('member_code', substr(md5(SITE_MEMBER_COOKIE . $uid), 5, 20), $time);
			$this->memberMsg(lang('m-reg-3'), url('member'), 1);
		} else {
		    $this->memberMsg(lang('m-pms-8'));
		}
	}
	
	/**
	 * 会员名验证
	 */
	public function checkuserAction() {
	    $username = $this->get('username');
		if (empty($username)) exit($this->ajaxMsg(lang('m-reg-7'), 0));
		if (!$this->is_username($username)) exit($this->ajaxMsg(lang('m-pms-12'), 0));
		$member = $this->member->from(null, 'id')->where('username=?', $username)->select(false);
		if ($member) exit($this->ajaxMsg(lang('m-reg-8'), 0));
		exit($this->ajaxMsg('√', 1));
	}
	
	/**
	 * Email验证
	 */
	public function checkemailAction() {
	    $email = $this->get('email');
		if (!check::is_email($email)) exit($this->ajaxMsg(lang('m-reg-9'), 0));
		$member = $this->member->from(null, 'id')->where('email=?', $email)->select(false);
		if ($member) exit($this->ajaxMsg(lang('m-reg-10'), 0));
		exit($this->ajaxMsg('√', 1));
	}
	
	private function ajaxMsg($msg, $id) {
	    $msg = $id == 0 ? '<span class="form-tip tip-error">' . $msg . '<br></span>' : '<span class="form-tip tip-success">' . $msg . '</span>';
		return json_encode(array('result'=>$id, 'msg'=>$msg));
	}
	
	/**
	 * 内部验证
	 */
	private function check($data) {
	    if (!$this->memberconfig['register']) $this->memberMsg(lang('m-reg-0'));
	    if (empty($data['username'])) $this->memberMsg(lang('m-reg-7'));
		if (!$this->is_username($data['username'])) $this->memberMsg(lang('m-pms-12'));
		if (empty($data['password'])) $this->memberMsg(lang('m-reg-11'));
		if ($data['password'] != $data['password2']) $this->memberMsg(lang('m-reg-12'));
		if (!check::is_email($data['email'])) $this->memberMsg(lang('m-reg-9'));
		if ($this->memberconfig['banuser']) {
		    $users = explode(',', $this->memberconfig['banuser']);
			if (in_array($data['username'], $users)) $this->memberMsg(lang('m-reg-13', array('1'=>$data['username'])));
		}
		if ($this->memberconfig['regiptime']) {
		    $mcfg  = $this->member->from(null, 'regdate,regip')->where('regip=?', client::get_user_ip())->order('regdate DESC')->select(false);
			if ($mcfg && time() - $mcfg['regdate'] <= $this->memberconfig['regiptime'] * 3600) {
			    $this->memberMsg(lang('m-reg-13', array('1'=>$this->memberconfig['regiptime'])));
			}
		}
		$member = $this->member->from(null, 'id')->where('email=?', $data['email'])->select(false);
		if ($member) $this->memberMsg(lang('m-reg-10'));
		$member = $this->member->from(null, 'id')->where('username=?', $data['username'])->select(false);
		if ($member) $this->memberMsg(lang('m-reg-8'));
	}
	
	/**
	 * 绑定
	 */
	private function bang($data) {
	    $oauth_openid = $this->session->get('oauth_openid');
		$oauth_name   = $this->session->get('oauth_name');
		if (empty($oauth_openid) || empty($oauth_name)) $this->memberMsg(lang('m-reg-15'), url('member/login'));
		$oauth  = $this->model('oauth');
	    $member = $oauth->where('oauth_openid=?', $oauth_openid)->where('oauth_name=?', $oauth_name)->select(false);
		if ($member) $this->memberMsg(lang('m-reg-16'));
		$data['oauth_openid'] = $oauth_openid;
		$data['oauth_name']   = $oauth_name;
		$data['logintime']    = $data['addtime'] = time();
		unset($data['id']);
		$oauth->insert($data);
	}
	
	/**
	 * 注册
	 */
	private function reg($data) {
	    if (empty($data)) return false;
		$data['groupid']  = 1;
		$data['regdate']  = time(); 
        $data['regip']    = client::get_user_ip();
        $data['status']	  = $this->memberconfig['status']  ? 0 : 1;
		$data['modelid']  = (!isset($data['modelid']) || empty($data['modelid'])) ? $this->memberconfig['modelid'] : $data['modelid'];
		if (!isset($this->membermodel[$data['modelid']])) $this->memberMsg(lang('m-reg-17'));
		if ($this->memberconfig['uc_use'] == 1) {
		    if (uc_get_user($data['username'])) {
				$this->memberMsg(lang('m-reg-18'), url('member/login'), 1);
			}
			$uid = uc_user_register($data['username'], $data['password'], $data['email']);
			if ($uid <= 0) {
				if ($uid == -1) {
					$this->memberMsg(lang('m-reg-20'));
				} elseif($uid == -2) {
					$this->memberMsg(lang('m-reg-19'));
				} elseif($uid == -3) {
					$this->memberMsg(lang('m-reg-8'));
				} elseif($uid == -4) {
					$this->memberMsg(lang('m-inf-8'));
				} elseif($uid == -5) {
					$this->memberMsg(lang('m-inf-9'));
				} elseif($uid == -6) {
					$this->memberMsg(lang('m-reg-10'));
				} else {
					$this->memberMsg(lang('m-log-7'));
				}
			} else {
				$username = $data['username'];
			}
		}
		$data['salt']     = substr(md5(time()), 0, 10);
	    $data['password'] = md5(md5($data['password']) . $data['salt'] . md5($data['password']));
		return $this->member->insert($data);
	}
	
	/**
	 * 激活Ucenter用户
	 */
	public function activeAction() {
	    list($username)       = explode("\t", uc_authcode($this->get('auth'), 'DECODE'));
		if (empty($username)) $this->memberMsg(lang('m-pms-13'));
		if ($this->isPostForm()) {
		    $data['username'] = $username;
		    $data['modelid']  = $this->post('modelid');
		    $data['groupid']  = 1;
			$data['regdate']  = time(); 
			$data['regip']    = client::get_user_ip();
			$data['status']	  = $this->memberconfig['status']  ? 0 : 1;
			$data['modelid']  = (!isset($data['modelid']) || empty($data['modelid'])) ? $this->memberconfig['modelid'] : $data['modelid'];
			$uc_user_info     = uc_get_user($username);
			$data['email']    = $uc_user_info[2];
			$data['avatar']   = UC_API . '/avatar.php?uid=' . $uc_user_info[0] . '&size=middle';
			if (!isset($this->membermodel[$data['modelid']])) $this->memberMsg(lang('m-reg-17'));
			if ($member = $this->member->getOne('username=?', $username, 'id')) {
			    $userid = $member['id'];
			} else {
			    $userid = $this->member->insert($data);
			}
			if ($userid) {
				cookie::set('member_id', $userid, 24*3600);
				cookie::set('member_code', substr(md5(SITE_MEMBER_COOKIE . $userid), 5, 20), $time);
				$this->memberMsg(lang('m-reg-21'), url('member/'), 1);
			} else {
			    $this->memberMsg(lang('m-reg-22'));
			}
		}
	    $this->view->assign(array(
		    'membermodel' => $this->membermodel,
			'username'    => $username,
			'meta_title'  => lang('m-reg-23') . '-' . $this->site['SITE_NAME'],
		));
		$this->view->display('member/active');
	}
	
	/**
	 * 检查会员名是否符合规定
	 */
	private function is_username($username) {
		$strlen = strlen($username);
		if(!preg_match('/^[a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/', $username)){
			return false;
		} elseif ( 20 < $strlen || $strlen < 2 ) {
			return false;
		}
		return true;
    }
	
}