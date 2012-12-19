<?php

class ContentModel extends Model {
    
    public function get_primary_key() {
        return $this->primary_key = 'id';
    }
    
    public function get_fields() {
        return $this->get_table_fields();
    }
    
    public function set($id, $tablename, $data) {
        if (!$this->is_table_exists($tablename)) return lang('m-con-37', array('1'=>$tablename));
        $table = Controller::model($tablename); //加载附表Model
        if (empty($data['catid'])) return lang('m-con-8');
        //数组转化为字符
		foreach ($data as $i=>$t) {
		    if (is_array($t)) $data[$i] = array2string($t);
		}
		//描述截取
	    if (empty($data['description']) && isset($data['content'])) {
		    $len = isset($data['fn_add_introduce']) && $data['fn_add_introduce'] && $data['fn_introcude_length'] ? $data['fn_introcude_length'] : 200;
		    $data['description'] = str_replace(array(' ', PHP_EOL, '　　'), array('', '', ''), strcut(clearhtml($data['content']), $len));
		}
		//提取缩略图
		if (empty($data['thumb']) && isset($data['content']) && isset($data['fn_auto_thumb']) && $data['fn_auto_thumb']) {
		    $content = htmlspecialchars_decode($data['content']);
		    if (preg_match('/<img(.+)>/Ui', $content, $img)) {
			    $img = str_replace(array('\\', '"'), array('', '\''), $img[1]);
			    if (preg_match('/src=\'(.+)\'/Ui', $img, $src)) {
				   $data['thumb'] = $src[1];
				}
			}
		}
		//关键字处理
		if ($data['keywords']) {
		    $data['keywords'] = str_replace('，', ',', $data['keywords']);
			$tags = @explode(',', $data['keywords']);
			if ($tags) {
			    foreach ($tags as $t) {
				    $name  = trim($t);
				    if ($name) {
						$d = $this->from('tag', 'id')->where('name=?', $name)->select(false);
						if (empty($d)) {
							$this->query('INSERT INTO `' . $this->prefix . 'tag` (`name`,`letter`) VALUES ("' . $name . '", "' . word2pinyin($name) . '")');
						}
					}
				}
			}
		}
        if ($id) {
			//修改
		    $_data = $this->find($id, '`status`, userid');
            $this->update($data,  'id=' . $id);
            $table->update($data, 'id=' . $id);
			$data['userid'] = $_data['userid'];
        } else {
			//添加
			$this->insert($data);
			$id = $this->get_insert_id();
			if (empty($id)) return lang('m-con-36');
			$data['id'] = $id;
			$table->insert($data);
			$status = $data['status'];
			//增加会员统计数量
			if (!$data['sysadd'] && $data['userid']) {
			    $this->query('UPDATE ' . $this->prefix . 'member_count SET post=post+1 WHERE id=' . $data['userid']);
			}
		}
		//积分处理
		if ( !$data['sysadd'] && ($data['status'] != $_data['status'] && $data['status'] == 1 ) || $status == 1) {
		    $this->credits($data['userid'], 1);
		}
        return $id;
    }
    
	/**
     * 删除
     */
    public function del($id, $catid) {
        $cache = new cache_file();
        $cat   = $cache->get('category');
        $table = $cat[$catid]['tablename'];
        if (empty($table)) return false;
		$_data = $this->find($id, 'userid,url,thumb,modelid');
        if (empty($_data)) return false;
        $this->delete('id=' . $id);
		if ($_data['thumb'] && file_exists($_data['thumb'])) @unlink($_data['thumb']);
        $this->query('delete from ' . $this->prefix . $table . ' where id=' . $id);
		if (!$data['sysadd']) $this->credits($_data['userid'], 0);
		$file  = substr($_data['url'], strlen(Controller::get_base_url())); //去掉主域名
		$file  = substr($file, 0, 9) == 'index.php' ? null : $file; //是否为动态链接
		//删除关联表单数据
		$mods  = $cache->get('model');
		$mod   = $mods[$_data['modelid']];
		if ($mod['joinid']) {
		    $form = $cache->get('formmodel');
			$join = $form[$mod['joinid']];
			if ($join) $this->query('delete from ' . $this->prefix . $join['tablename'] . ' where cid=' . $id);
		}
		if ($file && file_exists($file)) @unlink($file);
    }
    
	/**
     * 更新URL地址
     */
    public function url($id, $url) {
        $this->update(array('url'=>$url), 'id=' . $id);
    }
	
	/**
     * 审核文章
     */
    public function verify($id) {
	    if (empty($id)) return false;
		$data = $this->find($id, '`status`, userid');
		if ($data['status'] == 1) return false;
		$this->update(array('status'=>1), 'id=' . $id);
		//积分处理
		if (!$data['sysadd']) $this->credits($data['userid'], 1);
    }
    
    /**
     * 相关文章
     */
    public function relation($ids, $num) {
        return $this->where('id in (' . $ids . ')')->order('listorder desc, updatetime desc')->limit($num)->select();
    }
	
	/**
     * 积分处理
     */
	private function credits($userid, $action) {
	    if (empty($userid)) return false;
	    $member = $this->from('member')->where('id=' . $userid)->select(false);
		if (empty($member)) return false;
		$cache  = new cache_file();
		$config = $cache->get('member');
		if (isset($config['postcredits']) && $config['postcredits'] && $action == 1) {
		    //增加积分
			$credit = $member['credits'] + (int)$config['postcredits'];
		} elseif (isset($config['delcredits']) && $config['delcredits'] && $action == 0) {
		    //删除积分
			$credit = $member['credits'] - (int)$config['delcredits'];
		}
		if ($credit) $this->query('update ' . $this->prefix . 'member set credits=' . $credit . ' where id=' . $userid);
	}
    
}