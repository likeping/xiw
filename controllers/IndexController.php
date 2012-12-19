<?php

class IndexController extends Common {

	public function __construct() {
        parent::__construct();
	}
	
	public function indexAction() {
		if (file_exists(APP_ROOT . 'index.html')) $this->redirect(SITE_PATH . 'index.html');
	    $this->view->assign(array(
	        'indexc'           => 1, //首页标识符
	        'meta_title'       => $this->site['SITE_TITLE'],
	        'meta_keywords'    => $this->site['SITE_KEYWORDS'], 
	        'meta_description' => $this->site['SITE_DESCRIPTION'],
	    ));
		$this->view->display('index');
	}
	
}