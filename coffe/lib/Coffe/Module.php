<?php

/**
 * Базовый класс для backend - модуля
 *
 * @package coffe_cms
 */
class Coffe_Module
{

	/**
	 * @var Coffe_View
	 */
	protected $view = null;

	/**
	 * @var Coffe_View
	 */
	protected $layout = null;

	/**
	 * @var Coffe_Translate
	 */
	protected $lang = null;

	/**
	 * @var Coffe_Flash
	 */
	protected $flash = null;

	/**
	 * ID backend-модуля
	 *
	 * @var null
	 */
	protected $module_id = null;

	/**
	 * Необходима аторизация
	 *
	 * @var bool
	 */
	protected $need_auth = true;

	/**
	 * Работа с иконками
	 *
	 * @var Coffe_Icon
	 */
	protected $icon = null;

	/**
	 * Адаптер базы данных
	 *
	 * @var Coffe_DB_Abstract
	 */
	protected $db = null;

	/**
	 * Меню модуля
	 *
	 * @var array
	 */
	public $module_menu = array();

	/**
	 * Заголовок шапки модуля
	 *
	 * @var string
	 */
	public $module_title = '';


	/**
	 * ID шаблона
	 *
	 * @var null
	 */
	protected $template_id = null;

	public function __construct()
	{
		if (!trim($this->module_id)){
			throw new Coffe_Exception('The module id isn\'t set');
		}
		if ($this->need_auth && (!Coffe_User::getInstance()->isLogin() || !Coffe::isAdmin())){
			if ($this->isAjaxRequest())
				die('Access denied');
			else
				$this->redirectToModule('_login',array('redirect_url' => $_SERVER['REQUEST_URI'])); exit();
		}
		$this->template_id = Coffe::getConfig('adminTemplate','default');
		$this->initDB();
		$this->initLayout();
		$this->initLang();
		$this->initFlash();
		$this->initIcon();
		$this->initView();
		$this->includeJsFiles();
		$this->includeCssFiles();
	}

	/**
	 * Точка входа
	 */
	public function run()
	{
		$this->dispatch();
	}

	/**
	 * Диспетчеризация
	 */
	public function dispatch()
	{
		$action = $this->_GET('action','index');
		if (method_exists($this, $action . 'Action')){
			call_user_func(array($this, $action . 'Action'));
		}
	}


	/**
	 *  Инициализация базы данных
	 */
	public function initDB()
	{
		$this->db = $GLOBALS['COFFE_DB'];
	}

	/**
	 * Инициализация объекта View
	 *
	 * @return Coffe_View
	 */
	public function initView()
	{
		if ($this->view === null){
			$this->view = new Coffe_View();
			$backend_module = Coffe_ModuleManager::getBackendModule($this->module_id);
			$this->view->setDir(PATH_ROOT . dirname($backend_module['path']) . '/views/');
			$this->initViewHelpers();
			$this->view->module_id = $this->module_id;
		}
		return $this->view;
	}

	/**
	 * Инициализация каркаса
	 *
	 * @return Coffe_View|null
	 */
	public function initLayout()
	{
		if ($this->layout === null){
			$this->layout = new Coffe_View();
			$template_dir = PATH_ROOT . 'coffe/admin/templates/' . $this->template_id . '/';
			if (!is_dir($template_dir)){
				throw new Coffe_Exception("Not possibly about to initialize the module: the directory " . htmlspecialchars($template_dir) . " doesn't exist");
			}
			$this->layout->setDir($template_dir);
		}
		//кодировка
		Coffe::initHead()->addData('charset', '<meta http-equiv = "text/html" charset = "' . Coffe::getConfig('charset', 'utf-8') . '" />');
		return $this->layout;
	}

	/**
	 * Подключение js - файлов
	 */
	public function includeJsFiles()
	{
		Coffe::initHead()->addJsFile('jquery', Coffe::getUrlPrefix() . 'coffe/admin/js/jquery.js')
			->addJsFile('twitter_bootstrap_js', Coffe::getUrlPrefix() . 'coffe/admin/js/bootstrap.min.js')
			->addJsFile('admin_js', Coffe::getUrlPrefix() . 'coffe/admin/templates/'. $this->template_id . '/js/admin.js');
	}


	/**
	 * Подключение css файла
	 */
	public function includeCssFiles()
	{

		Coffe::initHead()->addCssFile('bootstrap.min.css', Coffe::getUrlPrefix() . 'coffe/admin/templates/'. $this->template_id . '/css/bootstrap.min.css')
			->addCssFile('admin_css', Coffe::getUrlPrefix() . 'coffe/admin/templates/'. $this->template_id . '/css/admin.css');

	}

	/**
	 * Инициализация помощников вида
	 */
	public function initViewHelpers()
	{
		$this->view->addHelper('url',array($this,'url'));
		$this->view->addHelper('urlLf',array($this,'urlLf'));
		$this->view->addHelper('lang',array($this,'lang'));
		$this->view->addHelper('_GET',array($this,'_GET'));
		$this->view->addHelper('_POST',array($this,'_POST'));
		$this->view->addHelper('_GP',array($this,'_GP'));
		if (is_object($this->icon)){
			$this->view->addHelper('icon',array($this->icon,'getIcon'));
			$this->view->addHelper('iconSrc',array($this->icon,'getIconSrc'));
		}
	}

	/**
	 *  Инициализация объекта Flash для работы с собщениями
	 */
	public function initFlash()
	{
		$this->flash = new Coffe_Flash();
		$this->flash->setSessionKey('__coffe_module');
	}

	/**
	 *  Инициализация объекта для работы с иконками
	 */
	public function initIcon()
	{
		$this->icon = new Coffe_Icon();
		$backend_module = Coffe_ModuleManager::getBackendModule($this->module_id);
		//приоритет модуля (можно переодпределить стандартные иконки)
		$this->icon->addDir(dirname($backend_module['path']) . '/icons/')
			->addDir('coffe/admin/icons/');
	}

	/**
	 * Установка шаблона
	 *
	 * @param $view
	 */
	public function setView($view)
	{
		$this->view = $view;
	}

	/**
	 * Получение шаблонизатора
	 *
	 * @return Coffe_View|null
	 */
	public function getView()
	{
		return $this->view;
	}

	/**
	 * Инициализация объекта Translate
	 */
	public function initLang()
	{
		$backend_module = Coffe_ModuleManager::getBackendModule($this->module_id);
		$this->lang = new Coffe_Translate();
		$this->lang->loadFile(PATH_ROOT . dirname($backend_module['path']) . '/lang.xml');
	}

	/**
	 * Получение значения языковой переменной
	 *
	 * @param $name
	 * @param array $marker_array
	 * @param null $default
	 * @return bool|mixed|null
	 */
	public function lang($name, $marker_array = array(), $default = null)
	{
		return $this->lang->get($name, $marker_array, $default);
	}

	/**
	 * Это POST
	 *
	 * @return bool
	 */
	public function isPost()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	 * Это GET
	 *
	 * @return bool
	 */
	public function isGet()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'GET');
	}

	/**
	 * Получение значения из POST массива
	 *
	 * @param null $param
	 * @param null $default
	 * @return array|null
	 */
	public function _POST($param = null, $default = null)
	{
		return is_string($param) ? (isset($_POST[$param]) ? $_POST[$param] : $default) : $_POST;
	}

	/**
	 * Получение GET или POST параметра
	 *
	 * @param null $param
	 * @param null $default
	 * @return null
	 */
	public function _GP($param = null, $default = null)
	{
		if(empty($param)) return $default;
		return isset($_POST[$param]) ? $_POST[$param] : (isset($_GET[$param]) ? $_GET[$param] : $default);
	}

	/**
	 * Получение значения из GET массива
	 *
	 * @param null $param
	 * @param null $default
	 * @return array|null
	 */
	public function _GET($param = null, $default = null)
	{
		return is_string($param) ? (isset($_GET[$param]) ? $_GET[$param] : $default) : $_GET;
	}

	/**
	 * Получение url к backend-модулю
	 *
	 * @param null $module
	 * @param null $params
	 * @return mixed
	 */
	public function url($module = null, $params = null)
	{
		if (!$module) $module = $this->module_id;
		return Coffe_ModuleManager::getBackendModuleUrl($module, $params);
	}


	/**
	 * Получение ссылки на редактирование элемента TableEditor
	 *
	 * @param $table
	 * @param null $primary
	 * @param array $params
	 * @return mixed
	 */
	public function urlLf($table, $primary = null, $params = array())
	{
		return $this->url('_tableeditor', array_merge($params, array('primary' => $primary, 'table' => $table)));
	}

	/**
	 * Редирект к модулю
	 *
	 * @param null $module
	 * @param array $params
	 */
	public function redirectToModule($module = null, $params = array())
	{
		return $this->redirect($this->url($module,$params));
	}

	/**
	 * Редирект на указанный адрес
	 *
	 * @param $location
	 * @return bool
	 */
	public function redirect($location)
	{
		header('Location: ' . $location);
		exit();
	}

	/**
	 * Рендер шаблона
	 *
	 * @param $file
	 * @param bool $layout
	 * @param bool $navigation
	 * @return bool
	 */
	public function render($file, $layout = true, $navigation = true)
	{
		return $this->renderContent($this->view->render($file),$layout, $navigation);
	}

	/**
	 * Вывод контента модуля
	 *
	 * @param $content
	 * @param bool $layout
	 * @param bool $navigation
	 * @return bool
	 */
	public function renderContent($content = '', $layout = true, $navigation = true)
	{
		if ($layout){
			$this->layout->flashObj = $this->flash;
			$this->layout->module_title = $this->module_title;
			$this->layout->module_menu = $this->module_menu;
			$this->layout->navigation = $navigation;
			$this->layout->content = $content;
			$this->layout->setHelpers($this->view->getHelpers());
			echo $this->layout->render('template.php');
		}
		else{
			echo $content;
		}
		return true;
	}

	/**
	 * Проверка, что был ajax запрос
	 *
	 * @return bool
	 */
	public function isAjaxRequest()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

}