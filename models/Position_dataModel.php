<?php

class Position_dataModel extends Model {
	
	public function get_primary_key() {
		return $this->primary_key = 'id';
	}
	
	public function set($id, $data) {
	    if ($id) {
		    $check = $this->where('id<>' . $id)->where('catid=' . (int)$data['catid'])->where('posid=' . $data['posid'])->where('contentid=' . (int)$data['contentid'])->select(false);
		    if ($check) return false;
	        $this->update($data, 'id=' . $id);
			$this->updateContent($data);
	        return true;
	    }
		if ($data['contentid'] && $data['catid']) {
			$check = $this->where('catid=' . (int)$data['catid'])->where('posid=' . $data['posid'])->where('contentid=' . (int)$data['contentid'])->select(false);
			if ($check) return false;
		}
	    $this->insert($data);
	    if (!$this->get_insert_id()) return false;
		$this->updateContent($data);
	    return true;
	}
	
	private function updateContent($data) {
	    if (empty($data['contentid'])) return false;
		$content  = $this->from('content', 'position')->where('id=' . $data['contentid'])->select(false);
		if (empty($content)) return false;
		$position = @explode(',', $content['position']);
		$update   = array();
		if (empty($position)) {
			$update = array($data['posid']);
		} else {
			foreach ($position as $p) {
				if ($p) $update[] = $p;
			}
			$update[] = $data['posid'];
			$update   = array_unique($update);
		}
		$update = @implode(',', $update);
		$this->query('update ' . $this->prefix . 'content set position="' . $update . '" where id=' . $data['contentid']);
	}
	
	public function del($posid) {
	    $this->delete('posid=' . $posid);
	    $table = $this->prefix . 'position_data';
	    $this->query('delete from ' . $table . ' where posid=' . $posid);
	}
	
	
}