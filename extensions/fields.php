<?php
if (!defined('IN_FINECMS')) exit();

/**
 * 字段操作函数库
 */

function formtype() {
    return array(
        'input'    => lang('a-mod-80'),//'单行文本',
        'textarea' => lang('a-mod-81'),//'多行文本',
		'password' => lang('a-mod-82'),//'密码文本',
        'editor'   => lang('a-mod-83'),//'编辑器',
        'select'   => lang('a-mod-84'),//'下拉选择框',
        'radio'    => lang('a-mod-85'),//'单选按钮',
        'checkbox' => lang('a-mod-86'),//'复选框',
        'image'    => lang('a-mod-87'),//'单图上传',
        'file'     => lang('a-mod-88'),//'文件上传',
        'files'    => lang('a-mod-89'),//'多文件上传',
        'date'     => lang('a-mod-90'),//'日期时间',
		'linkage'  => lang('a-mod-91'),//'联动菜单',
		'merge'    => lang('a-mod-92'),//'组合字段',
		'map'      => lang('a-mod-93'),//'地图字段',
		'fields'   => lang('a-mod-94'),//'多字段组合',
    );
}

$editor_file = $date_file = $upload_file = 0;

/**
 * 以下函数作用于字段添加/修改部分
 */

function form_input($setting='') {
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
	<tbody>
	<tr> 
      <td width="100">' . lang('a-mod-96') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' . (isset($setting['size']) ? $setting['size'] : '150') . '" name="setting[size]"><font color="gray">px</font></td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-95') . ' ：</td>
      <td><input type="text" class="input-text" size="30" value="' . (isset($setting['default']) ? $setting['default'] : '') . '" name="setting[default]"></td>
    </tr>
    </tbody>
	</table>';
}

function form_password($setting='') {
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
	<tbody>
	<tr> 
      <td width="100">' . lang('a-mod-96') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' .(isset($setting['size']) ? $setting['size'] : '150') . '" name="setting[size]"><font color="gray">px</font></td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-95') . ' ：</td>
      <td><input type="text" class="input-text" size="30" value="' .(isset($setting['default']) ? $setting['default'] : '') . '" name="setting[default]"></td>
    </tr>
    </tbody>
	</table>';
}

function form_textarea($setting='') {
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
	<tbody>
	<tr> 
      <td width="100">' . lang('a-mod-97') . ' ：</td>
      <td><input type="text" class="input-text" size="20" value="' .(isset($setting['width']) ? $setting['width'] : '400') . '" name="setting[width]">
      <font color="gray">px</font>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-98') . ' ：</td>
      <td><input type="text" class="input-text" size="20" value="' .(isset($setting['height']) ? $setting['height'] : '90') . '" name="setting[height]">
      <font color="gray">px</font>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-95') . ' ：</td>
      <td><textarea name="setting[default]" rows="2" cols="30" class="text">' . (isset($setting['default']) ? $setting['default'] : '') . '</textarea></td>
    </tr>
    </tbody>
	</table>';
}

function form_editor($setting='') {
	$t = isset($setting['type']) && $setting['type'] ?  1 : (!isset($setting['type']) ? 1 : 0);
	$w = isset($setting['width'])  ? $setting['width']  : '100';
	$h = isset($setting['height']) ? $setting['height'] : '300';
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
    <tbody>
	<tr> 
      <td width="100">' . lang('a-mod-97') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' . $w. '" name="setting[width]">
      <font color="gray">%</font>
      </td>
    </tr>
    <tr> 
      <td>' . lang('a-mod-98') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' . $h . '" name="setting[height]">
      <font color="gray">px</font>
      </td>
    </tr>
    <tr> 
      <td>' . lang('a-mod-99') . ' ：</td>
      <td><input type="radio" value=1 name="setting[type]" ' . ($t == 1 ? 'checked' : '') . '> ' . lang('a-mod-100') . '&nbsp;&nbsp;&nbsp;&nbsp;
	  <input type="radio" value=0 name="setting[type]"' . ($t == 0 ? 'checked' : '') . '> ' . lang('a-mod-101') . '
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-95') . ' ：</td>
      <td><textarea name="setting[default]" rows="2" cols="30" class="text">' . (isset($setting['default']) ? $setting['default'] : '') . '</textarea></td>
    </tr>
    </tbody>
	</table>';
}

function form_select($setting='') {
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
	<tbody>
	<tr> 
      <td width="170">' . lang('a-mod-102') . ' ：</td>
      <td><textarea name="setting[content]" style="width:195px;height:100px;" class="text">' . (isset($setting['content']) ? $setting['content'] : '') . '</textarea>
      <font color="gray">' . lang('a-mod-103') . '</font>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-104') . ' ：</td>
      <td><input type="text" class="input-text" style="width:200px;" value="' . (isset($setting['default']) ? $setting['default'] : '') . '" name="setting[default]"></td>
    </tr>
    </tbody>
	</table>';
}

function form_radio($setting='') {
    return form_select($setting);
}

function form_checkbox($setting='') {
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
	<tbody>
	<tr> 
      <td width="170">' . lang('a-mod-102') . ' ：</td>
      <td><textarea name="setting[content]" style="width:195px;height:100px;" class="text">' . (isset($setting['content']) ? $setting['content'] : '') . '</textarea>
      <font color="gray">' . lang('a-mod-103') . '</font>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-104') . ' ：</td>
      <td><input type="text" class="input-text" style="width:200px;" value="' . (isset($setting['default']) ? $setting['default'] : '') . '" name="setting[default]">
	  <br><font color="gray">' . lang('a-mod-105') . '</font>
	  </td>
    </tr>
    </tbody>
	</table>';
}

function form_image($setting='') {
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
    <tbody>
	<tr> 
      <td width="100">' . lang('a-mod-97') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' . (isset($setting['width']) ? $setting['width'] : '200') . '" name="setting[width]">
      <font color="gray">px</font>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-98') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' . (isset($setting['height']) ? $setting['height'] : '160') . '" name="setting[height]">
      <font color="gray">px</font>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-106') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' . (isset($setting['size']) ? $setting['size'] : '2') . '" name="setting[size]">
      <font color="gray">MB</font>
      </td>
    </tr>
    </tbody>
	</table>';
}

function form_file($setting='') {
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
    <tbody>
	<tr> 
      <td width="100">' . lang('a-mod-107') . ' ：</td>
      <td><input type="text" class="input-text" size="50" value="' . (isset($setting['type']) ? $setting['type'] : '') . '" name="setting[type]">
      <font color="gray">' . lang('a-mod-108') . '</font>
      </td>
    </tr>
    <tr> 
      <td>' . lang('a-mod-106') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' . (isset($setting['size']) ? $setting['size'] : '2') . '" name="setting[size]">
      <font color="gray">MB</font>
      </td>
    </tr>
    </tbody>
	</table>';
}

function form_files($setting='') {
    return form_file($setting);
}

function form_date($setting='') {
    $type  = isset($setting['type']) && $setting['type']   ? $setting['type']  : '%Y-%m-%d %H:%M:%S';
    $width = isset($setting['width']) && $setting['width'] ? $setting['width'] : 150;
    return '
    <table width="98%" cellspacing="1" cellpadding="2">
    <tbody>
	<tr> 
      <td width="100">' . lang('a-mod-97') . ' ：</td>
      <td><input type="text" class="input-text" size="7" value="' . $width . '" name="setting[width]">
      <font color="gray">px</font>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-107') . ' ：</td>
      <td><input type="text" class="input-text" size="25" value="' . $type . '" name="setting[type]">
      <font color="gray">' . lang('a-mod-109') . '</font>
      </td>
    </tr>
    </tbody>
	</table>';
}

function form_linkage($setting='') {
    $link = Controller::model('linkage');
	$data = $link->where('keyid=0')->select();
	$str  = '
    <table width="98%" cellspacing="1" cellpadding="2">
    <tbody>
	<tr> 
    <td width="200">' . lang('a-mod-110') . ' ：</td><td><select name="setting[id]"><option value=0> &nbsp;---&nbsp; </option>';
	if ($data) {
	    foreach($data as $c) {
		    $selected = isset($setting['id']) && $c['id'] == $setting['id'] ? ' selected' : '';
		    $str     .= '<option value=' . $c['id'] . ' ' . $selected . '>' . $c['name'] . '</option>';
		}
	}
	$str .= '</select></td></tr>
	<td>' . lang('a-mod-111') . ' ：</td>
      <td><input type="text" class="input-text" size="10" value="' . (isset($setting['level']) ? $setting['level'] : '') . '" name="setting[level]">
      <font color="gray">' . lang('a-mod-112') . '</font>
      </td>
	</tr>
	<tr> 
      <td>' . lang('a-mod-104') . ' ：</td>
      <td><input type="text" class="input-text" size="20" value="' . (isset($setting['default']) ? $setting['default'] : '') . '" name="setting[default]"></td>
    </tr>
    </tbody>
	</table>';
	return $str;
}

function form_merge($setting='') {
    return '
	<table width="98%" cellspacing="1" cellpadding="2">
    <tbody>
	<tr> 
      <td width="150">' . lang('a-mod-92') . ' ：</td>
      <td><input type="text" name="setting[content]" class="input-text" value="' . (isset($setting['content']) ? $setting['content'] : '') . '" size=50>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-107') . ' ：</td>
      <td><font color="gray">' . lang('a-mod-113') . '</font></td>
    </tr>
    </tbody>
	</table>';
}

function form_map($setting='') {
    return '
	<table width="98%" cellspacing="1" cellpadding="2">
    <tbody>
	<tr> 
      <td width="100">' . lang('a-mod-114') . '</td>
      <td><input type="text" name="setting[city]" class="input-text" value="' . (isset($setting['city']) ? $setting['city'] : '') . '" size=20></td>
    </tr>
    </tbody>
	</table>';
}

function form_fields($setting='') {
    return '
	<table width="98%" cellspacing="1" cellpadding="2">
    <tbody>
	<tr> 
      <td width="100">' . lang('a-mod-94') . ' ：</td>
      <td><textarea name="setting[content]" style="width:444px;height:200px;" class="text">' . (isset($setting['content']) ? $setting['content'] : '') . '</textarea>
      </td>
    </tr>
	<tr> 
      <td>' . lang('a-mod-107') . ' ：</td>
      <td><font color="gray">' . lang('a-mod-113') . '</font></td>
    </tr>
    </tbody>
	</table>';
}

/////////////////////////////////////////////////////////

function get_content_value($content) {
    if ($content != '' && preg_match('/^\{M:(.+)\}$/U', $content, $field)) {
	    if (App::get_namespace_id() == 'admin') return null;
		if (!cookie::is_set('member_id'))       return null;
		if (!cookie::get('member_id'))          return null; 
	    $name     = trim($field[1]);
	    $member   = Controller::model('member');
	    $data     = $member->find(cookie::get('member_id'));
		if (isset($data[$name])) return $data[$name];
		$cache    = new cache_file();
		$model    = $cache->get('membermodel');
		$_member  = Controller::model($model[$data['modelid']]['tablename']);
	    $_data    = $_member->find(cookie::get('member_id'));
		if (isset($_data[$name])) return $_data[$name];
	} else {
	    return $content;
	}
}

/**
 * 以下函数作用于发布内容部分
 */

function content_input($name, $content='', $setting='') {
    $content = is_null($content[0])    ? get_content_value($setting['default']) : $content[0];
    $style   = isset($setting['size']) ? " style='width:" . ($setting['size'] ? $setting['size'] : 150) . "px;'": ''; 
    return '<input type="text" value="' . $content . '" class="input-text" name="data[' . $name . ']" ' . $style . '>';
}

function content_password($name, $content='', $setting='') {
    $content = is_null($content[0])    ? get_content_value($setting['default']) : $content[0];
    $style   = isset($setting['size']) ? " style='width:" . ($setting['size'] ? $setting['size'] : 150) . "px;'": ''; 
    return '<input type="password" value="' . $content . '" class="input-text" name="data[' . $name . ']" ' . $style . '>';
}

function content_textarea($name, $content='', $setting='') {
    $content = is_null($content[0]) ? get_content_value($setting['default']) : $content[0];
    $style   = isset($setting['width']) && $setting['width']   ? 'width:' . $setting['width'] . 'px;' : '';
    $style  .= isset($setting['height']) && $setting['height'] ? 'height:' . $setting['height'] . 'px;' : '';
    return '<textarea style="' . $style . '" name="data[' . $name . ']">' . $content . '</textarea>';
}

function content_editor($name, $content='', $setting='') {
    global $editor_file;
	$content = is_null($content[0]) ? get_content_value($setting['default']) : $content[0];
    $w       = isset($setting['width']) && $setting['width']   ? $setting['width']  : '98';
    $h       = isset($setting['height']) && $setting['height'] ? $setting['height'] : '400';
    $id      = $name;
	$type    = isset($setting['type']) && $setting['type'] ?  1 : (!isset($setting['type']) ? 1 : 0);
	$str     = '';
	$page    = $name == 'content' ? ", '|', 'pagebreak'" : '';
	if ($editor_file == 0) {
	    $str.= '
		<script type="text/javascript" src="' . EXT_PATH . 'kindeditor/kindeditor.js"></script>
        <script type="text/javascript" src="' . LANG_PATH . 'kindeditor.js"></script>
		';
	}
	if ($type) {
        $str.= "
		<script type=\"text/javascript\">KindEditor.ready(function(K) { 
		    K.create('#" . $id . "', { 
			    allowFileManager : true,
				resizeType : 0,
				langType : '" . SYS_LANGUAGE . "',
				items : [
					'source', '|', 'undo', 'redo', '|', 'preview', 'print', 'template', 'code', 'cut', 'copy', 'paste',
					'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
					'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
					'superscript', 'clearhtml', 'quickformat',  '|', 'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
					'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image',
					'flash', 'media', 'table', 'hr', 'emoticons', 'baidumap', 'anchor', 'link', 'unlink', '|', 'pagebreak'
				]
			});
		});
		</script>";
	} else {
	    $str.= "
		<script type=\"text/javascript\">KindEditor.ready(function(K) { 
			K.create('#" . $id . "', { 
				allowFileManager : true,
				allowImageUpload : false,
				resizeType : 0 ,
				langType : '" . SYS_LANGUAGE . "',
				items : [
				    'source', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
				    'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', '|', 'emoticons', 'image', 'link'" . $page . "
				]
			});
		});
		</script>";
	}
    $str .= '<textarea id="' . $id . '" name="data[' . $name . ']" style="width:' . $w . '%;height:' . $h . 'px;visibility:hidden;">' . $content . '</textarea>';
	if ($name == 'content') {
	    $str .= '<div style="padding-top:8px;"><label><input type="checkbox" checked="" value="1" name="data[fn_add_introduce]"> ' . lang('a-mod-115') . '</label><input type="text" size="3" value="200" name="data[fn_introcude_length]" class="input-text">' . lang('a-mod-116') . '<label><input type="checkbox" checked="" value="1" name="data[fn_auto_thumb]"> ' . lang('a-mod-117') . '</div>';
	}
	$editor_file++;
    return $str;
}

function content_select($name, $content='', $setting='') {
    $content = is_null($content[0]) ? get_content_value($setting['default']) : $content[0];
    $select  = explode(chr(13), $setting['content']);
    $str     = "<select id='" . $name . "' name='data[" . $name . "]'>";
    foreach ($select as $t) {
        $n   = $v = $selected = '';
        list($n, $v) = explode('|', $t);
        $v   = is_null($v) ? trim($n) : trim($v);
        $selected = $v == $content ? ' selected' : '';
        $str.= "<option value='" . $v . "'" . $selected . ">" . $n . "</option>";
    }
    return $str . '</select>';
}

function content_radio($name, $content='', $setting='') {
    $content = is_null($content[0]) ? get_content_value($setting['default']) : $content[0];
    $select  = explode(chr(13), $setting['content']);
    $str     = '';
    foreach ($select as $t) {
        $n   = $v = $selected = '';
        list($n, $v) = explode('|', $t);
        $v   = is_null($v) ? trim($n) : trim($v);
        $selected = $v==$content ? ' checked' : '';
        $str.= $n . '&nbsp;<input type="radio" name="data[' . $name . ']" value="' . $v . '" ' . $selected . '/>&nbsp;&nbsp;';
    }
    return $str;
}

function content_checkbox($name, $content='', $setting='') {
    $default = get_content_value($setting['default']);
    $content = is_null($content[0]) ? ($default ? @explode(',', $default) : '') : string2array($content[0]);
    $select  = explode(chr(13), $setting['content']);
    $str     = '';
    foreach ($select as $t) {
        $n   = $v = $selected = '';
        list($n, $v) = explode('|', $t);
        $v   = is_null($v) ? trim($n) : trim($v);
        $selected = is_array($content) && in_array($v, $content) ? ' checked' : '';
        $str.= $n . '&nbsp;<input type="checkbox" name="data[' . $name . '][]" value="' . $v . '" ' . $selected . ' />&nbsp;&nbsp;';
    }
    return $str;
}

function content_image($name, $content='', $setting='') {
    $content = $content[0];
    $size    = (int)$setting['size'];
	$height  = isset($setting['height']) ? $setting['height'] : '';
	$width   = isset($setting['width'])  ? $setting['width']  : '';
    $str     = '<input type="text" class="input-text" size="50" value="' . $content . '" name="data[' . $name . ']" id="' . $name . '">
    <input type="button" style="width:66px;cursor:pointer;" class="button" onClick="preview(\'' . $name . '\')" value="' . lang('a-mod-118') . '">
    <input type="button" style="width:66px;cursor:pointer;" class="button" onClick="uploadImage(\'' . $name . '\',\'' . $width . '\',\'' . $height . '\',\'' . $size . '\')" value="' . lang('a-mod-119') . '">';
    return $str;
}

function content_file($name, $content='', $setting='') {
    $content = $content[0];
    $type    = base64_encode($setting['type']);
    $size    = (int)$setting['size'];
    return '<input type="text" class="input-text" size="50" value="' . $content . '" name="data[' . $name . ']" id="' . $name . '">
    <input type="button" style="width:66px;cursor:pointer;" class="button" onClick="uploadFile(\'' . $name . '\',\'' . $type . '\',\'' . $size . '\')" value="' . lang('a-mod-120') . '">';
}

function content_files($name, $content='', $setting='') {
    global $upload_file;
    $content = $content[0];
    $set     = base64_encode($setting['type']) . '|' . (int)$setting['size'];
	$str     = '';
	if ($date_file == 0) $str.= '<script type="text/javascript" src="' . ADMIN_THEME . 'js/jquery-ui.min.js"></script>';
	$date_file++;
    $str    .= '<input type="hidden" value="' . $name . '" name="listfiles[]">
		<fieldset class="blue pad-10">
        <legend>' . lang('a-mod-121') . '</legend>
        <div class="picList" id="list_' . $name . '_files"><ul id="' . $name . '-sort-items">';
    if ($content) {
        $content  = string2array($content);
        $filepath = $content['file'];
        $filename = $content['alt'];
		if (is_array($filepath) && !empty($filepath)) {
			foreach ($filepath as $id=>$path){
				$alt  = isset($filename[$id]) ? $filename[$id] : '';
				$str .= '<li id="files_999' . $id . '">';
				$str .= '<input type="text" class="input-text" style="width:310px;" value="' . $path . '" name="data[' . $name . '][file][]">';
				$str .= '<input type="text" class="input-text" style="width:160px;" value="' . $alt . '" name="data[' . $name . '][alt][]">';
				$str .= '<a href="javascript:removediv(\'999' . $id . '\');">' . lang('a-mod-129') . '</a> <a href="javascript:;" style="cursor:move;" title="' . lang('a-mod-131') . '">' . lang('a-mod-130') . '</a></li>';
			}
		}
    }
    $str .= '</ul></fieldset>
		<div class="bk10"></div>
        <div class="picBut cu"><a href="javascript:;" onClick="add_null_file(\'' . $name . '\')">' . lang('a-mod-127') . '</a></div> 		
		<div class="picBut cu"><a href="javascript:;" onClick="uploadFiles(\'' . $name . '\',\'' . $set . '\')">' . lang('a-mod-128') . '</a></div>
		<div class="onShow">' . lang('a-mod-122') . '</div><script>$("#' . $name . '-sort-items").sortable();</script>';
    return $str;
}

function content_date($name, $content='', $setting='') {
    global $date_file;
    $c     = $content[0];
	$type  = isset($setting['type'])  ? $setting['type']  : '%Y-%m-%d %H:%M:%S';
	$width = isset($setting['width']) ? $setting['width'] : 150;
	$str   = '';
	if ($date_file == 0) {
	    $str .= '
		<link href="' . EXT_PATH . 'calendar/jscal2.css" type="text/css" rel="stylesheet">
		<link href="' . EXT_PATH . 'calendar/border-radius.css" type="text/css" rel="stylesheet">
		<link href="' . EXT_PATH . 'calendar/win2k.css" type="text/css" rel="stylesheet">
		<script type="text/javascript" src="' . EXT_PATH . 'calendar/calendar.js"></script>
		<script type="text/javascript" src="' . LANG_PATH . 'calendar.js"></script>
		<script type="text/javascript" src="' . ADMIN_THEME . 'js/jquery-ui.min.js"></script>';
	}
	$date_file++;
	return $str . '
	<input type="hidden" value="' . $c . '" name="data[' . $name . ']" id="date_' . $name . '">
	<input type="text" readonly="" class="date input-text" style="width:' . $width . 'px;" value="' . ($c ? date(str_replace(array('%','M','S'), array('','i','s'), $type), $c) : '') . '" id="' . $name . '" >
	<script type="text/javascript">
		Calendar.setup({
		weekNumbers : true,
		inputField  : "' . $name . '",
		trigger     : "' . $name . '",
		dateFormat  : "' . $type . '",
		showTime    : true,
		minuteStep  : 1,
		onSelect    : function() {
			this.hide();
			var time = $("#' . $name . '").val();
			var date = (new Date(Date.parse(time.replace(/-/g,"/")))).getTime() / 1000;
			$("#date_' . $name . '").val(date);
		}
		});
    </script>';
}

function content_linkage($name, $content='', $setting='') {
    $content = is_null($content[0]) ? get_content_value($setting['default']) : $content[0];
    return linkageform($setting['id'], $content, $name, $setting['level']);
}

function content_merge($name, $content='', $setting='') {
    
}

function content_map($name, $content='', $setting='') {
    $w   = isset($setting['width'])  ? $setting['width']  : 700;
    $h   = isset($setting['height']) ? $setting['height'] : 430;
	$url = url('api/baidumap', array('name'=>$name, 'value'=>$content[0], 'apikey'=>$setting['apikey'], 'city'=>$setting['city']), 1);
    $str = "
	<script type='text/javascript'>
	function openMap_" . $name . "() {
	    var id = 'map_" . $name . "';
		window.top.art.dialog({id:id,iframe:'" . $url . "', title:'" . lang('a-mod-124') . "', width:" . $w . ", height:" . $h . ", lock:true},
		function(){
			var d = window.top.art.dialog({id:id}).data.iframe;
			var mapvalue  = d.document.getElementById('" . $name . "').value;
			var _mapvalue = $('#" . $name . "').val();
			if (_mapvalue == '' && mapvalue) {
				$('#" . $name . "').val(mapvalue);
				$('#result_" . $name . "').html('" . lang('a-mod-125') . "');
			} else if (_mapvalue != '' && _mapvalue != mapvalue) {
			    $('#" . $name . "').val(mapvalue);
				$('#result_" . $name . "').html('" . lang('a-mod-126') . "');
			} else {
				$('#result_" . $name . "').html('');
			}
		},
		function(){
			window.top.art.dialog({id:id}).close()
		});void(0);
	}
	</script>";
	return $str . '<input name="' . $name . '_mark" id="' . $name . '_mark" value="' . lang('a-mod-123') . '" class="button" onclick="openMap_' . $name . '()" type="button">
	<input name="data[' . $name . ']" value="' . $content[0] . '" id="' . $name . '" type="hidden"> <span id="result_' . $name . '"></span>';
}