<?php

/**
 * Базовый класс для компонента
 *
 * @package coffe_cms
 */
class Coffe_Component
{

	/**
	 * Будет установлен при вызове
	 *
	 * @var Coffe_View
	 */
	protected $view = null;

	/**
	 * Переводчик
	 *
	 * @var Coffe_Translate
	 */
	protected $lang = null;

	/**
	 * Адаптер базы данных
	 *
	 * @var Coffe_DB_Abstract
	 */
	protected $db = null;

	/**
	 * ID компонента, будет установлено при вызове
	 *
	 * @var null
	 */
	protected $component_id = null;

	/**
	 * Путь к шаблону относительно ROOT
	 *
	 * @var null
	 */
	protected $template_path = null;

	/**
	 * Данные при подключении компонента из базы
	 *
	 * @var null
	 */
	protected $data = null;

	/**
	 * Конфигурация компонента
	 *
	 * @var null
	 */
	protected $config = null;

	/**
	 * Конфигурация по умолчанию
	 *
	 * @var null
	 */
	public $default_config = null;

	/**
	 * Путь к пользовательскому шаблону относительно ROOT
	 *
	 * @var null
	 */
	protected $template_user_path = null;

	/**
	 * Подключать автоматически css -файлы
	 *
	 * @var bool
	 */
	protected $auto_include_css = true;


	/**
	 * Подключать автоматически js - файлы
	 *
	 * @var bool
	 */
	protected $auto_include_js = true;


	/**
	 * Вызывается после создания экземпляра компонента и инициализации основных параметров
	 */
	public function _init()
	{
		$this->_initDB();
		$this->_initLang();
		$this->_initView();
		$this->_initConfig();
		$this->includeTemplateFiles();
	}

	/**
	 *  Инициализация базы данных
	 */
	public function _initDB()
	{
		$this->db = $GLOBALS['COFFE_DB'];
	}

	public function _initView()
	{
		$this->view = new Coffe_View();
		$this->view->addDir(PATH_ROOT . $this->template_path)->addDir(PATH_ROOT . $this->template_user_path, 1);
		$this->initViewHelpers();
	}

	/**
	 * Инициализация помощников вида
	 */
	public function initViewHelpers()
	{
		$this->view->addHelper('url',array($this,'url'));
		$this->view->addHelper('lang',array($this,'lang'));
		$this->view->addHelper('_GET',array($this,'_GET'));
		$this->view->addHelper('_POST',array($this,'_POST'));
		$this->view->addHelper('_GP',array($this,'_GP'));
		$this->view->addHelper('conf',array($this,'conf'));
	}

	/**
	 * Инициализация языка
	 */
	public function _initLang()
	{
		$this->lang = new Coffe_Translate();
		$path_to_file = dirname(__FILE__) . '/lang.xml';
		if (is_file($path_to_file)){
			$this->lang->loadFile($path_to_file);
		}
		if (!empty($this->template_user_path)){
			$file = PATH_ROOT . $this->template_user_path . 'lang.xml';
			if (file_exists($file)){
				$this->lang->loadFile($file);
			}
		}
	}

	/**
	 * Инициализация конфигурации
	 */
	public function _initConfig()
	{
		if (is_array($this->config) && is_array($this->default_config)){
			$this->config = array_replace($this->default_config, $this->config);
		}
	}

	/**
	 * Подключает css и js щаблона
	 */
	public function includeTemplateFiles()
	{
		$template_path = (!empty($this->template_user_path)) ? $this->template_user_path : $this->template_path;
		if (!empty($template_path)){
			if ($this->auto_include_css || $this->auto_include_js){
				if (is_dir(PATH_ROOT . $template_path)){
					//подключаем css
					if ($this->auto_include_css){
						foreach (glob(PATH_ROOT . $template_path . "css/*.css") as $filename) {
							Coffe_Head::getInstance()->addCssFile(md5($filename), Coffe::getUrlPrefix() . $template_path . 'css/'. basename($filename));
						}
					}
					//подключаем js
					if ($this->auto_include_js){
						foreach (glob(PATH_ROOT . $template_path . "js/*.js") as $filename) {
							Coffe_Head::getInstance()->addJsFile(md5($filename), Coffe::getUrlPrefix() . $template_path . 'js/'. basename($filename));
						}
					}
				}
			}
		}
	}

	/**
	 * Получение ссылки для страницы
	 *
	 * @static
	 * @param null $pageId
	 * @param array $params
	 * @return string
	 */
	public static function url($pageId = null, $params = array())
	{
		return Coffe::getPageUrl($pageId, $params);
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
	 * Получение параметра
	 *
	 * @param $param
	 * @param null $default
	 * @return null
	 */
	public function conf($param, $default = null)
	{
		return isset($this->config[$param]) ? $this->config[$param] : $default;
	}

	/**
	 * Устанавливает путь к шаблону по умолчанию
	 *
	 * @param $path
	 */
	public function setTemplatePath($path)
	{
		$this->template_path = $path;
	}

	/**
	 * Устанавливает путь к шаблону пользователя
	 *
	 * @param $path
	 */
	public function setTemplateUserPath($path)
	{
		$this->template_user_path = $path;
	}

	/**
	 * Устанавливает ID компонента
	 *
	 * @param $id
	 */
	public function setComponentID($id)
	{
		$this->component_id = $id;
	}

	/**
	 * @param $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param $config
	 */
	public function setConfig($config)
	{
		$this->config = $config;
	}

	/**
	 * Это POST?
	 *
	 * @return bool
	 */
	public function isPost()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	 * Это GET?
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
	 * @return string
	 */
	public function main()
	{
		return 'This is content of component "'. $this->component_id .'"';

	}

	/**
	 * Вывод view
	 *
	 * @param $file
	 * @return string
	 */
	public function render($file)
	{
		return $this->view->render($file);
	}

}