<?php

/**
 * Вход в систему
 *
 * @package coffe_cms
 */
class Login_Module extends Coffe_Module
{

	protected $module_id = '_login';

	/**
	 * Разрешаем доступ не авторизованным
	 *
	 * @var bool
	 */
	protected $need_auth = false;

	/**
	 * @var Coffe_User
	 */
	protected $auth = null;


	public function run()
	{
		Coffe::getHead()->addJsFile('_main_login_js', Coffe_ModuleManager::getModuleRelPath('main') . 'login/js/login.js');
		Coffe::getHead()->addCssFile('_main_login_css', Coffe_ModuleManager::getModuleRelPath('main') . 'login/css/login.css');
		$this->auth = Coffe_User::getInstance();
		if ($this->auth->isLogin()){
			$this->showUser();
		}
		else{
			$this->loginUser();
		}
	}

	public function loginUser()
	{
		$this->view->login = '';
		if ($this->isPost()){
			if ($this->auth->authorize($this->_POST('login'),$this->_POST('password'))){
				$this->redirect($this->_GET('redirect_url',Coffe_Func::getAbsPrefixUrl()));
			}
			$this->view->login = $this->_POST('login');
		}
		$this->view->redirect_url = $this->_GP('redirect_url', Coffe_Func::getAbsPrefixUrl());
		$this->render('login.phtml', true, false);
	}

	public function showUser()
	{
		if ($this->_GET('logout')){
			$this->auth->clear();
			$this->redirect($this->_GET('redirect_url',$this->url($this->module_id)));
		}
		$this->view->user = $this->auth->get();
		$this->view->main_page_link = Coffe_Func::getAbsPrefixUrl();
		$this->render('user.phtml',true,false);

	}


}