<?php

class SearchModel extends Model {

    public function get_primary_key() {
        return $this->primary_key = 'id';
    }
	
    public function get_fields() {
        return $this->get_table_fields();
    }
	
	/*
	 * 搜索结果
	 */
	public function getData($id, $cache, $param, $start, $limit, $kw_fields, $kw_or) {
	    $kw    = urldecode($param['kw']);
	    unset($param['page'], $param['id'], $param['kw']);
	    list($params, $from) = $this->getSQL($param, $kw, $kw_fields, $kw_or);
		$sql   = 'SELECT * FROM ' . $from;
		$this->set_table_name('search');
		$this->get_table_fields(true);
		$data  = $id ? $this->find($id) : $this->getOne('params=?', md5($params));
		$data  = empty($data) ? $this->addData($params, $kw, $sql) : $data;
		$data  = time() - $data['addtime'] > $cache * 3600 ? $this->updateData($data, $params, $sql) : $data;
		if (empty($data)) return array('total'=> 0, 'keywords'=> $kw);
		$order = isset($param['order']) && $param['order'] ? str_replace('_', ' ', $param['order']) : '`' . $this->prefix . 'content`.`updatetime` DESC';
		return array(
		    'total'    => $data['total'],
			'keywords' => $data['keywords'],
			'sql'      => $data['sql'] . ' ORDER BY ' . $order . ' LIMIT ' . $start . ',' . $limit,
			'id'       => $data['id'],
		);
	}
	
	/*
	 * 组建sql
	 */
	private function getSQL($param, $kw, $kw_fields, $kw_or) {
		$this->set_table_name('content');
		$_fields = $this->get_table_fields();
		if (is_array($param) && $param) {
		    $where_or   = $param_fields = $_data_fields = $table_fields = $data_fields = array();
			$cache_file = new cache_file();
			$category   = $cache_file->get('category');
			foreach($param as $key=>$val) {
				//参数归类
				if(substr($key, 0, 2) == 'OR') {
				    unset($param[$key]);
					$key = substr($key, 2);
					$where_or[] = $key;
					$param[$key]= $val;
				}
			}
			if (isset($param['modelid']) && $param['modelid']) {
				$cache_data = $cache_file->get('model');
				$table      = $cache_data[$param['modelid']]['tablename'];
				if ($table) {
				    $this->set_table_name($table);
		            $_data_fields = $this->get_table_fields(true);
				}
			} elseif (isset($param['catid']) && $param['catid']) {
				$table      = $category[$param['catid']]['tablename'];
				if ($table) {
				    $this->set_table_name($table);
		            $_data_fields = $this->get_table_fields(true);
				}
			}
			$more = isset($param['more']) && $param['more'] && $table && $_data_fields ? true : false;
			foreach ($param as $k=>$v) {
			    if (in_array($k, $_fields)) {
				    $table_fields[] = $k;
				} elseif (isset($_data_fields) && in_array($k, $_data_fields)) {
				    $more = true;
				    $data_fields[] = $k;
				}
			}
		} else {
		    if (empty($kw)) return false;
		}
		$where  = '`' . $this->prefix . 'content`.`status`=1';
		if ($kw) {
		    if ($kw_fields) {
			    $kw_fields = explode(',', $kw_fields);
				$kw_where  = '';
				$kw_count  = 0;
				foreach ($kw_fields as $f) {
				    $andor = empty($kw_count) ? '' : ($kw_or ? ' OR' : ' AND');
					if (in_array($f, $_fields)) {
					    //主表
						$kw_where .= $andor . ' `' . $this->prefix . 'content`.`' . $f . '` LIKE \'%' . $kw . '%\'';
					} elseif (isset($table) && isset($_data_fields) && in_array($f, $_data_fields)) {
					    //附表
					    $kw_where .= $andor . ' `' . $this->prefix . $table . '`.`' . $f . '` LIKE \'%' . $kw . '%\'';
						$more      = true;
					}
					$kw_count      = 1;
				}
				$where .= ' AND (' . $kw_where . ')';
			} else {
		        $where .= ' AND `' . $this->prefix . 'content`.`title` LIKE \'%' . $kw . '%\'';
			}
		}
		if (isset($param['modelid']) && $param['modelid'])  $where .= ' AND `' . $this->prefix . 'content`.`modelid`=' . (int)$param['modelid'];
		if (isset($param['catid']) && $param['catid']) {
		    $cat    = $category[$param['catid']];
			$where .= $cat['child'] ? ' AND `' . $this->prefix . 'content`.`catid` IN (' . $cat['arrchilds'] . ')' : ' AND `' . $this->prefix . 'content`.`catid`=' . (int)$param['catid'];
		}
		unset($param['catid'], $param['modelid']);
		if ($table_fields) {
		    foreach ($table_fields as $field) {
			    if (isset($param[$field]) && $param[$field]) {
				    $value = $param[$field];
					$andor = is_array($where_or) && in_array($field, $where_or) ? 'OR' : 'AND';
				    if (is_numeric($value)) {
					    $where .= ' ' . $andor . ' `' . $this->prefix . 'content`.`' . $field . '`=' . $value;
					} elseif (substr($value, 0, 1) == '%' && substr($value, -1, 1) == '%') {
					    $where .= ' ' . $andor . ' `' . $this->prefix . 'content`.`' . $field . '` LIKE \'' . $value . '\'';
					} else {
					    $where .= ' ' . $andor . ' `' . $this->prefix . 'content`.`' . $field . '`=\'' . $param[$field] . '\'';
					}
				}
			}
		}
		if ($data_fields && $table && $more) {
		    foreach ($data_fields as $field) {
			    if (isset($param[$field]) && $param[$field]) {
					$value = $param[$field];
					$andor = is_array($where_or) && in_array($field, $where_or) ? 'OR' : 'AND';
				    if (is_numeric($value)) {
					    $where .= ' ' . $andor . ' `' . $this->prefix . $table . '`.`' . $field . '`=' . $value;
					} elseif (substr($value, 0, 1) == '%' && substr($value, -1, 1) == '%') {
					    $where .= ' ' . $andor . ' `' . $this->prefix . $table . '`.`' . $field . '` LIKE \'' . $value . '\'';
					} else {
					    $where .= ' ' . $andor . ' `' . $this->prefix . $table . '`.`' . $field . '`=\'' . $param[$field] . '\'';
					}
				}
			}
		}
		$from = $more && $table ? $this->prefix . 'content LEFT JOIN ' . $this->prefix . $table . ' ON `' . $this->prefix . 'content`.`id`=`' . $this->prefix . $table . '`.`id`' : $this->prefix . 'content';
		$sql  = 'SELECT `' . $this->prefix . 'content`.`id`,`' . $this->prefix . 'content`.`title`,`' . $this->prefix . 'content`.`url`' . ' FROM ' . $from . ' WHERE ' . $where . ' ORDER BY `' . $this->prefix . 'content`.`updatetime` DESC LIMIT 500';
		return array($sql, $from);
	}
	
	/*
	 * 搜索数据保存
	 */
	private function addData($params, $kw, $sql) {
	    if (empty($sql) || empty($params)) return false;
	    $data = $this->execute($params, true);
		if (empty($data)) return false;
		$total= count($data);
		if ($total == 1 && $data && strcasecmp($data[0]['title'], $kw) == 0) Controller::redirect(SITE_URL . $data[0]['url']);
		$ids  = '';
		foreach ($data as $t) { $ids .= $t['id'] . ','; }
		$ids  = substr($ids, -1) == ',' ? substr($ids, 0, -1) : $ids;
		$data = array(
		    'params'   => md5($params),
			'addtime'  => time(),
			'total'    => $total,
			'keywords' => $kw,
			'sql'      => $sql . ' WHERE `' . $this->prefix . 'content`.`id` IN (' . $ids . ')',
		);
		$data['id'] = strlen($kw) > 2 ? $this->insert($data) : true;
		return $data['id'] ? $data : false;
	}
	
	/*
	 * 搜索数据更新
	 */
	private function updateData($data, $params, $sql) {
	    if (empty($data))   return false;
		if (empty($params)) return $data;
	    $cdata  = $this->execute($data['sql'], true);
		if (empty($cdata)) {
		    $this->delete('id=' . $data['id']);
		    return false;
		}
		$kw    = $data['keywords'];
		$total = count($cdata);
		if ($total == 1 && $cdata && strcasecmp($cdata[0]['title'], $kw) == 0) Controller::redirect(SITE_URL . $cdata[0]['url']);
		$ids   = '';
		foreach ($cdata as $t) {  $ids .= $t['id'] . ','; }
		$ids   = substr($ids, -1) == ',' ? substr($ids, 0, -1) : $ids;
		$upda  = array(
		    'params'   => md5($params),
			'addtime'  => time(),
			'total'    => $total,
			'keywords' => $kw,
			'sql'      => $sql . ' WHERE `' . $this->prefix . 'content`.`id` IN (' . $ids . ')',
		);
		$this->update($upda, 'id=' . $data['id']);
		$upda['id']    = $data['id'];
		return $upda;
	}
	
}