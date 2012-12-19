<?php

/**
 * Common.php
 * 控制器公共类 
 */

if (!defined('IN_FINECMS')) exit();

class Common extends Controller {
    
	protected $namespace;
	protected $controller;
	protected $action;
	protected $cache;
	protected $session;
	
	protected $member;
	protected $memberinfo;
	protected $memberconfig;
	protected $membergroup;
	protected $membermodel;
	protected $memberedit;
	
	protected $cats;
	protected $cats_dir;
	protected $category;
	protected $content;
	protected $site;
    
    public function __construct() {
        parent::__construct();
		if (!file_exists(APP_ROOT . './cache/install.lock')) Controller::redirect(url('install/'));
        $this->session    = $this->instance('session');
        $this->cache      = new cache_file();
        $this->namespace  = App::get_namespace_id();
        $this->controller = App::get_controller_id();
        $this->action     = App::get_action_id();
        $this->site       = App::get_config();
        $system_cms       = $this->load_config('version');
		$this->category   = $this->model('category');
		$this->content    = $this->model('content');
		$this->cats       = $this->cache->get('category');
		$this->cats_dir   = $this->cache->get('category_dir');
		//定义网站常量
        define('SITE_PATH',   self::get_base_url());
		define('SITE_URL',    self::get_server_name() . self::get_base_url());
        define('CMS_NAME',    $system_cms['name']);
        define('CMS_VERSION', $system_cms['version']);
        define('CMS_UPDATE',  $system_cms['update']);
        define('SITE_THEME',  self::get_theme_url());
		define('ADMIN_THEME', SITE_PATH . basename(VIEW_DIR) . '/admin/');
		define('EXT_PATH',    SITE_PATH . EXTENSION_PATH . '/');
		define('LANG_PATH',   SITE_PATH . EXTENSION_PATH . '/language/' . SYS_LANGUAGE . '/');
		//禁止访问
		$ips = $this->cache->get('ip');
		$uip = client::get_user_ip();
		if ($ips && is_array($ips) && isset($ips[$uip]) && (empty($ips[$uip]['endtime']) || ($ips[$uip]['endtime'] - $ips[$uip]['addtime']) >= 0)) $this->adminMsg(lang('a-aip-6'));
		//载入会员系统缓存
		if (is_dir(CONTROLLER_DIR . 'member')) {
			$this->member       = $this->model('member');
			$this->membergroup  = $this->cache->get('membergroup');
			$this->membermodel  = $this->cache->get('membermodel');
			$this->memberconfig = $this->cache->get('member');
			if ($this->memberconfig['uc_use'] == 1 && $this->namespace != 'admin') {
				include EXTENSION_DIR . 'ucenter' . DIRECTORY_SEPARATOR . 'config.inc.php';
				include EXTENSION_DIR . 'ucenter' . DIRECTORY_SEPARATOR . 'uc_client' . DIRECTORY_SEPARATOR . 'client.php';
			}
			$this->memberinfo   = $this->getMember();
		}
		$this->view->assign($this->site);
		$this->view->assign(array(
			'membergroup'  => $this->membergroup,
			'membermodel'  => $this->membermodel,
			'cats'         => $this->cats,
			'memberinfo'   => $this->memberinfo,
			'memberconfig' => $this->memberconfig,
			's'            => $this->namespace,
			'c'            => $this->controller,
			'a'            => $this->action,
			'param'        => $this->getParam(),
		));
		//加载系统函数库和自定义函数库
        App::auto_load('function');
		App::auto_load('custom');
		date_default_timezone_set(SYS_TIME_ZONE);
    }
	
	/**
	 * 获取会员信息
	 */
	protected function getMember() {
	    if (cookie::is_set('member_id') && cookie::is_set('member_code')) {
            $uid  = cookie::get('member_id');
			$code = cookie::get('member_code');
		    if (!empty($uid) && $code == substr(md5(SITE_MEMBER_COOKIE . $uid), 5, 20)) {
			    $_memberinfo    = $this->member->find($uid);
				$member_table   = $this->membermodel[$_memberinfo['modelid']]['tablename'];
				if ($_memberinfo && $member_table) {
				    $_member    = $this->model($member_table);
				    $memberdata = $_member->find($uid);
					if ($memberdata) {
					    $_memberinfo      = array_merge($_memberinfo, $memberdata);
						$this->memberedit = 1; //不需要完善会员资料
					}
					if ($this->memberconfig['uc_use'] == 1 && function_exists('uc_api_mysql')) {
					    $uc = uc_api_mysql('user', 'get_user', array('username'=> $_memberinfo['username']));
						if ($uc != 0) $_memberinfo['uid'] = $uc[0];
					}
					return $_memberinfo;
				}
			}
        }
		return false;
	}
    
    /**
     * 后台提示信息
	 * msg    消息名称
	 * url    返回地址
	 * time   等待时间
	 * i      是否显示返回文字
	 * result 返回结果是否成功
     */
    protected function adminMsg($msg, $url='', $time=3, $i=1, $result=0) {
	    $this->view->assign(array(
		    'msg'    => $msg,
			'url'    => $url,
			'time'   => $time,
			'i'      => $i,
			'result' => $result,
		));
		$tpl = 'admin/msg';
		if ($this->namespace !='admin') $tpl = '../' . $tpl;
        $this->view->display($tpl);
        exit;
    }
	
	/**
     * 会员提示信息
	 * msg    消息名称
	 * url    返回地址
	 * result 返回结果是否成功
	 * time   等待时间
     */
    protected function memberMsg($msg, $url='', $result=0, $time=3) {
        $this->view->assign(array(
		    'msg'    => $msg,
			'url'    => $url,
			'result' => $result,
			'time'   => $time,
		));
        $this->view->display('member/msg');
        exit;
    }
    
    /**
     * 前台提示信息
	 * msg    消息名称
	 * url    返回地址
	 * result 返回结果是否成功
	 * time   等待时间
     */
    protected function msg($msg, $url='', $result=0, $time=3) {
        $this->view->assign(array(
		    'msg'    => $msg,
			'url'    => $url,
			'result' => $result,
			'time'   => $time,
		));
        $this->view->display('msg');
        exit;
    }
    
    /**
     * 栏目URL
     */
    protected function getCaturl($data, $page=0) {
         return getCaturl($data, $page);
    }
    
    /**
     * 内容页URL
     */
    protected function getUrl($data, $page=0) {
        return getUrl($data, $page);
    }
    
    /**
     * 递归创建目录
     */
    protected function mkdirs($dir) {
	    if (empty($dir)) return false;
        if (!is_dir($dir)) {
            $this->mkdirs(dirname($dir));
            mkdir($dir);
        }
    }
     
	/**
	* 加载自定义字段
	* fields 字段数组
	* data   字段默认值
	* auth   字段权限（是否必填）
	*/
    protected function getFields($fields, $data=array()) {
	    App::auto_load('fields');
	    $data_fields = '';
	    if (empty($fields['data'])) return false;
	    foreach ($fields['data'] as $t) {
		    if ($this->namespace != 'admin' && !$t['isshow']) continue;
			if (!@in_array($t['field'], $fields['merge']) && !in_array($t['formtype'], array('merge', 'fields')) && empty($t['merge'])) {
			    //单独显示的字段。
			    $data_fields .= '<tr>';
				$data_fields .= isset($t['not_null']) && $t['not_null'] ? '<th><font color="red">*</font> ' . $t['name'] . '：</th>' : '<th>' . $t['name'] . '：</th>';
				$data_fields .= '<td>';
				$func         = 'content_' . $t['formtype'];
				$t['setting'] = $t['setting'] ? $t['setting'] : 0;
				//防止出错，把字段内容转换成数组格式
				$content      = array($data[$t['field']]);
				$content      = var_export($content, true);
				if (function_exists($func)) eval("\$data_fields .= " . $func . "(" . $t['field'] . ", " . $content . ", " . $t['setting'] . ");");
				$data_fields .= $t['tips'] ? '<div class="onShow">' . $t['tips'] . '</div>' : '';
				$data_fields .= '<span id="ck_' . $t['field'] . '"></span>';
				$data_fields .= '</td>';
				$data_fields .= '</tr>';
			} elseif ($t['formtype'] == 'merge') {
			    $data_fields .= '<tr>';
				$data_fields .= '<th>' . $t['name'] . '：</th>';
				$data_fields .= '<td>' ;
				$setting      = string2array($t['setting']);
				$string       = $setting['content'];
				$regex_array  = $replace_array = array();
				foreach ($t['data'] as $field) {
				    $zhiduan  = $fields['data'][$field];
				    $str      = '';
					$func     = 'content_' . $zhiduan['formtype'];
					$zhiduan['setting']  = $zhiduan['setting'] ? $zhiduan['setting'] : 0;
					//防止出错，把字段内容转换成数组格式
					$content             = array($data[$field]);
					$content             = var_export($content, true);
					if (function_exists($func)) eval("\$str = " . $func . "(" . $field . ", " . $content . ", " . $zhiduan['setting'] . ");");
					$regex_array[]       = '{' . $field . '}';
					$replace_array[]     = $str;
				}
				$data_fields .= str_replace($regex_array, $replace_array, $string);
				$data_fields .= '</td>';
				$data_fields .= '</tr>';
			} elseif ($t['formtype'] == 'fields') {
			    $data_fields .= '<tr>';
				$data_fields .= '<th>' . $t['name'] . '：</th><td>';
				$data_fields .= '<script type="text/javascript" src="' . ADMIN_THEME . 'js/jquery-ui.min.js"></script>';
				$data_fields .= '<div class="fields-list" id="list_' . $t['field'] . '_fields"><ul id="' . $t['field'] . '-sort-items">';
				$merge_string = null;
				$contentdata  = empty($data[$t['field']]) ? array(0=>array()) : string2array($data[$t['field']]);
				$setting      = string2array($t['setting']);
				$string       = $setting['content'];
				foreach ($contentdata as $i=>$cdata) {
				    $data_fields .= '<li id="li_' . $t['field'] . '_' . $i . '_fields">';
				    $regex_array  = $replace_array = $o_replace_array = array();
					foreach ($fields['data'] as $field=>$value) {
						if ($value['merge'] == $t['fieldid']) {
							$str  = $o_str    = '';
							$func = 'content_' . $value['formtype'];
							$value['setting'] = $value['setting'] ? $value['setting'] : 0;
							//防止出错，把字段内容转换成数组格式
							$content          = array($cdata[$field]);
							$content          = var_export($content, true);
							if (function_exists($func)) eval("\$str = " . $func . "(" . $field . ", " . $content . ", " . $value['setting'] . ");");
							if (empty($merge_string) && function_exists($func)) eval("\$o_str = " . $func . "(" . $field . ", null, " . $value['setting'] . ");");
							$regex_array[]    = '{' . $field . '}';
							$replace_array[]  = str_replace('data[' . $field . ']', 'data[' . $t['field'] . '][' . $i . '][' . $field . ']', $str);
							$o_replace_array[]= str_replace('data[' . $field . ']', 'data[' . $t['field'] . '][{finecms_block_id}][' . $field . ']', $o_str);
						}
					}
					if (empty($merge_string)) {
					    $merge_string = '<li id="li_' . $t['field'] . '_{finecms_block_id}_fields">' . str_replace($regex_array, $o_replace_array, $string) . '<div class="option"><a href="javascript:;" onClick="$(\'#li_' . $t['field'] . '_{finecms_block_id}_fields\').remove()">' . lang('a-mod-129') . '</a> <a href="javascript:;" style="cursor:move;" title="' . lang('a-mod-131') . '">' . lang('a-mod-130') . '</a></div></li>';
						$merge_string = str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $merge_string);
					}
					$data_fields .= str_replace($regex_array, $replace_array, $string);
					$data_fields .= '<div class="option"><a href="javascript:;" onClick="$(\'#li_' . $t['field'] . '_' . $i . '_fields\').remove()">' . lang('a-mod-129') . '</a> <a href="javascript:;" style="cursor:move;" title="' . lang('a-mod-131') . '">' . lang('a-mod-130') . '</a></div></li>';
				}
				$data_fields .= '</ul>
				<div class="bk10"></div>
				<div class="picBut cu"><a href="javascript:;" onClick="add_block_' . $t['field'] . '()">' . lang('a-add') . '</a></div> 
				<script type="text/javascript">
				function add_block_' . $t['field'] . '() {
				    var c  = \'' . addslashes($merge_string) . '\';
					var id = parseInt(Math.random()*1000);
					c = c.replace(/{finecms_block_id}/ig, id);
					$("#' . $t['field'] . '-sort-items").append(c);
				}
				$("#' . $t['field'] . '-sort-items").sortable();
				</script>
				</td>';
				$data_fields .= '</tr>';
			}
	    }
	    return $data_fields;
    }
	
	/**
     * 验证自定义字段
     */
	protected function checkFields($fields, $data, $msg=1) {
	    if (empty($fields)) return false;
		foreach ($fields['data'] as $t) {
		    if ($this->namespace != 'admin' && !$t['isshow']) continue;
			if ($t['formtype'] != 'merge' && isset($t['not_null']) && $t['not_null']) {
			    if (is_null($data[$t['field']]) || $data[$t['field']] == '') {
				    if ($msg == 1) {
					    $this->adminMsg(lang('com-0', array('1'=>$t['name'])));
					} elseif ($msg == 2) {
					    $this->memberMsg(lang('com-0', array('1'=>$t['name'])));
					} elseif ($msg == 3) {
					    $this->msg(lang('com-0', array('1'=>$t['name'])), null, 1);
					}
				}
				if (isset($t['pattern']) && $t['pattern']) {
				    if (!preg_match($t['pattern'], $data[$t['field']])) {
					    $showmsg = isset($t['errortips']) && $t['errortips'] ? $t['errortips'] : lang('com-1', array('1'=>$t['name']));
					    if ($msg == 1) {
					        $this->adminMsg($showmsg);
						} elseif ($msg == 2) {
							$this->memberMsg($showmsg);
						} elseif ($msg == 3) {
							$this->msg($showmsg, null, 1);
						}
					}
				} 
			}
	    }
	}
    
    /**
     * 生成水印图片
     */
    protected function watermark($file) {
        if (!$this->site['SITE_WATERMARK']) return false;
        $image = $this->instance('image_lib');
        if ($this->site['SITE_WATERMARK'] == 1) {
            $image->set_watermark_alpha($this->site['SITE_WATERMARK_ALPHA']);
            $image->make_image_watermark($file, $file);
        } else {
            $image->set_text_content($this->site['SITE_WATERMARK_TEXT']);
            $image->make_text_watermark($file, $file);
        }
    }
    
    /**
     * 生成网站地图
     */
    protected function sitemap() {
        sitemap_xml();
    }
	
	/**
     * 删除目录及文件
     */
    protected function delDir($filename) {
        if (empty($filename)) return false;
        if (is_file($filename) && file_exists($filename)) {
            unlink($filename);
        } else if ($filename != '.' && $filename!='..' && is_dir($filename)) {
            $dirs = scandir($filename);
            foreach ($dirs as $file) {
                if ($file != '.' && $file != '..') {
                    $this->delDir($filename . '/' . $file);
                }
            }
            rmdir($filename);
        }
    }
	
	/**
     * 用户是否能够查看未审核信息
     */
	protected function userShow($data) {
	    if ($data['status'] != 0) return true;
	    if ($this->session->is_set('user_id') && $this->session->get('user_id')) return true;
		if (cookie::is_set('member_id') && cookie::get('member_id') == $data['userid'] && $data['sysadd'] == 0) return true;
		return false;
	}
	
    /**
     * 验证验证码
     */
	protected function checkCode($value) {
	    $code  = $this->session->get('captcha');
		$value = strtolower($value);
		$this->session->delete('captcha');
		return $code == $value ? true : false;
	}
	
	/**
     * 模型栏目
     */
	protected function getModelCategory($modelid) {
	    $data = array();
		foreach ($this->cats as $cat) {
		    if ($modelid == $cat['modelid'] && $cat['typeid'] == 1 && $cat['child'] == 0) $data[$cat['catid']] = $cat;
		}
		return $data;
	}
	
	/**
     * 模型的关联表单
     */
	protected function getModelJoin($modelid) {
	    if (empty($modelid)) return null;
		$data   = $this->cache->get('formmodel');
		$return = null;
		if ($data) {
		    foreach ($data as $t) {
			    if ($t['joinid'] == $modelid) $return[] = $t;
			}
		}
		return $return;
	}
	
	/**
     * 可在会员中心显示的表单
     */
	protected function getFormMember() {
		$data   = $this->cache->get('formmodel');
		$join   = $this->cache->get('joinmodel');
		$return = null;
		if ($data) {
		    foreach ($data as $id=>$t) {
			    if (isset($t['setting']['form']['member']) && $t['setting']['form']['member']) {
				    $t['joinname'] = isset($join[$t['joinid']]['modelname']) && $join[$t['joinid']]['modelname'] ? $join[$t['joinid']]['modelname'] : '';
				    $return[$id]   = $t;
				}
			}
		}
		return $return;
	}
	
	/**
     * 格式化字段数据
     */
	protected function getFieldData($model, $data) {
	    if (!isset($model['fields']['data']) || empty($model['fields']['data']) || empty($data)) return $data;
	    foreach ($model['fields']['data'] as $t) {
			if (!isset($data[$t['field']])) continue;
			if ($t['formtype'] == 'editor') {
			    //把编辑器中的HTML实体转换为字符
				$data[$t['field']] = htmlspecialchars_decode($data[$t['field']]);
			} elseif (in_array($t['formtype'], array('checkbox', 'files', 'fields'))) {
				//转换数组格式
				$data[$t['field']] = string2array($data[$t['field']]);
			}
		}
		return $data;
	}
	
}