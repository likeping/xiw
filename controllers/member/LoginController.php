<?php

class LoginController extends Member {
    
    public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 登录
	 */
	public function indexAction() {
	    if (!$this->isLogin(1)) $this->memberMsg(lang('m-log-0', array('1'=>$this->memberinfo['username'])), url('member/'));
	    if ($this->isPostForm()) {
		    $data   = $this->post('data');
			if ($this->memberconfig['logincode'] && !$this->checkCode($this->post('code'))) $this->memberMsg(lang('m-log-1'));
			if (empty($data['username']) || empty($data['password'])) $this->memberMsg(lang('m-log-2'));
			$member = $this->member->where('username=?', $data['username'])->select(false);
			$time   = empty($data['cookie']) ? 24*3600 : 360*24*3600; //会话保存时间。
			$backurl= $data['back'] ? urldecode($data['back']) : url('member/');
			if ($this->memberconfig['uc_use'] == 1) { 
				list($uid, $username, $password, $email) = uc_user_login($data['username'], $data['password']);
				if ($uid > 0) {
					if (empty($member)) {
						$auth = rawurlencode(uc_authcode("$username\t" . time(), 'ENCODE'));
						$this->memberMsg(lang('m-log-3'), url('member/register/active', array('auth'=>$auth)), 1);
					}
					$ucsynlogin = uc_user_synlogin($uid);
					$nickname   = $member['nickname']    ? $member['nickname'] : $member['username'];
					cookie::set('member_id', $member['id'], $time);
					cookie::set('member_code', substr(md5(SITE_MEMBER_COOKIE . $member['id']), 5, 20), $time);
					$this->memberMsg(lang('m-log-4') . $ucsynlogin, $backurl, 1);
				} elseif ($uid == -1) {
				    if ($member) {
					    //注册Ucenter
						$uid = uc_user_register($member['username'], $data['password'], $member['email']);
						if ($uid > 0) {
						    $ucsynlogin = uc_user_synlogin($uid);
						    $nickname   = $member['nickname']    ? $member['nickname'] : $member['username'];
						    cookie::set('member_id', $member['id'], $time);
							cookie::set('member_code', substr(md5(SITE_MEMBER_COOKIE . $member['id']), 5, 20), $time);
					        $this->memberMsg(lang('m-log-4') . $ucsynlogin, $backurl, 1);
						} elseif ($uid == -1) {
							$this->memberMsg(lang('m-log-5'));
						} elseif ($uid == -2) {
							$this->memberMsg(lang('m-log-6'));
						} else {
							$this->memberMsg(lang('m-log-7'));
						}
					}
					$this->memberMsg(lang('m-log-5'));
				} elseif ($uid == -2) {
					$this->memberMsg(lang('m-log-6'));
				} else {
					$this->memberMsg(lang('m-log-7'));
				}
			}
			if (empty($member)) $this->memberMsg(lang('m-log-8'));
			if ($member['password'] != md5(md5($data['password']) . $member['salt'] . md5($data['password']))) $this->memberMsg(lang('m-log-6'));
			cookie::set('member_id', $member['id'], $time);
			cookie::set('member_code', substr(md5(SITE_MEMBER_COOKIE . $member['id']), 5, 20), $time);
			$this->memberMsg(lang('m-log-4'), $backurl, 1);
		}
		$backurl = $this->get('back') ? $this->get('back') : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/'));
	    $this->view->assign(array(
			'meta_title' => lang('m-log-9') . '-' . $this->site['SITE_NAME'],
			'backurl'    => urlencode($backurl),
		));
		$this->view->display('member/login');
	}
	
	/**
	 * 一键登录
	 */
	public function oauthAction() {
	    $oauthname   = $this->get('name');
		if ($this->memberconfig['isoauth'] && $oauthname) {
		    $config  = $this->loadOauth($oauthname);
		    oauth_login($config);
		} else {
		    $this->memberMsg(lang('m-log-10'), url('member/login'));
		}
	}
	
	/**
	 * 退出登录
	 */
	public function outAction() {
		$oauthconfig = $this->loadOauth();
		if ($oauthconfig) oauth_logout();
		if ($this->session->is_set('member_id'))    $this->session->delete('member_id');
		if ($this->session->is_set('oauth_openid')) $this->session->delete('oauth_openid');
		if ($this->session->is_set('oauth_name'))   $this->session->delete('oauth_name');
		if (cookie::is_set('member_id'))            cookie::delete('member_id');
		if (cookie::is_set('member_code'))          cookie::delete('member_code');
		$this->memberMsg(lang('m-log-11') . ($this->memberconfig['uc_use'] == 1 ? uc_user_synlogout() : ''), SITE_URL, 1);
	}
	
	/**
	 * 一键登录返回
	 */
	public function callbackAction() {
	    $oauthconfig  = $this->loadOauth();
	    oauth_callback($oauthconfig);
		$memberinfo   = get_user_info($oauthconfig);
		$oauth_openid = $this->session->get('oauth_openid');
		$oauth_name   = $this->session->get('oauth_name');
		if (empty($oauth_openid) || empty($oauth_name)) $this->memberMsg(lang('m-log-12'));
		//查询是否已经绑定
		$omember     = $this->member->from('oauth')->where("oauth_openid = '" . $oauth_openid . "' AND oauth_name = '" . $oauth_name . "'")->select(false);
		if (empty($omember)) {  //绑定用户
			$this->view->assign(array(
			    'data'        => $memberinfo,
				'membermodel' => $this->membermodel,
			    'meta_title'  => lang('m-log-13') . '-' . $this->site['SITE_NAME'],
			));
			$this->view->display('member/bang');
		} else { //验证成功,判断用户表
			$member = $this->member->where('username=?', $omember['username'])->select(false);;
			if (empty($member)) $this->memberMsg(lang('m-log-14'), url('member/login'));
			$oauth = $this->model('oauth');
			$oauth->update(array('logintime'=>time(), 'logintimes'=>$omember['logintime']), 'id=' . $omember['id']);//更新登录时间
		    cookie::set('member_id', $member['id'], 24*3600); //保存会话24小时。
			cookie::set('member_code', substr(md5(SITE_MEMBER_COOKIE . $member['id']), 5, 20), 24*3600);
			if ($this->memberconfig['uc_use'] == 1) {
			    list($uid, $username, $password, $email) = uc_user_login($member['username'], $member['password']);
				if ($uid > 0) {
				    $ucsynlogin = uc_user_synlogin($uid);
					$this->memberMsg(lang('m-log-4') . $ucsynlogin, url('member/'), 1);
				}
			}
		    $this->memberMsg(lang('m-log-4'), url('member/'), 1);
		}
	}
	
}