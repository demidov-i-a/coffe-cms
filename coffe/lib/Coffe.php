<?php
/**
 * Основной системный класс
 *
 * @package coffe_cms
 */

class Coffe
{

	/**
	 * Выводить административное меню
	 *
	 * @var bool
	 */
	private static $showPanel = false;

	/**
	 * Объект Head
	 *
	 * @var Coffe_Head
	 */
	private static $headObj = null;


	/**
	 * Объект Template
	 *
	 * @var Coffe_Template
	 */
	private static $templateObj = null;


	/**
	 * Текущая страница
	 *
	 * @var null|array
	 */
	private static $page = null;


	/**
	 * Стартовали сессию
	 *
	 * @var bool
	 */
	private static $sessionStarted = false;


	/**
	 * Выполнена ли диспетчеризация
	 *
	 * @var bool
	 */
	private static $isDispatched = false;

	/**
	 * Весь контент
	 *
	 * @var string
	 */
	private static $content = '';

	/**
	 * Массив не кешируемых компонентов
	 *
	 * @var array
	 */
	private static $component_buffer = array();


	/**
	 * @var string
	 */
	private static $cacheID = null;


	/**
	 * Строка найденного кеша страницы
	 *
	 * @var null
	 */
	private static $page_cache = null;

	/**
	 * Нашли страницу в кеше
	 *
	 * @var bool
	 */
	private static $cacheHit = false;

	/**
	 * Глобальный флаг кеша
	 *
	 * @var bool
	 */
	private static $cacheOn = true;


	/**
	 * Зарезервированные системой маркеры
	 *
	 * @var array
	 */
	private static $reserveMarks = array('PANEL', 'HEAD');


	/**
	 * ID текущего шаблона
	 *
	 * @var null
	 */
	private static $template_id = null;

	/**
	 * Выполнен запуск в нормальном режиме
	 *
	 * @var bool
	 */
	private static $run = false;

	/**
	 * Имя основной сессии
	 *
	 * @var string
	 */
	private static $session_name = 'coffe_cms';

	/**
	 * Добавляется ко всем ссылкам
	 *
	 * @var string
	 */
	private static $url_prefix = '';


	/**
	 * Запуск системы в стандартном режиме
	 *
	 * @static
	 * @throws Coffe_Exception
	 */
	public static function run()
	{
		if (self::$run){
			throw new Coffe_Exception('It was already launched');
		}

		//инициализация пользователя
		self::initUser();

		//подключаем файл интерфейса
		self::includeInterface();

		//задает префикс для ссылок
		self::setUrlPrefix(self::getConfig('urlPrefix', ''));

		//подключение модулей
		Coffe_ModuleManager::initModules();

		if (isset($_GET['cfAjax'])){
			self::executeAjaxRequest();
		}

		//разрешен ли кеш
		self::$cacheOn = !((bool)self::getConfig('cacheOff',false));

		//отключаем кеш, если мы находимся в режиме редактирования
		if (self::isEditMode()){
			self::$cacheOn = false;
		}

		//находим искомую страницу
		$page = self::dispatch();

		if (!is_array($page)){
			throw new Coffe_Exception("The page isn't found");
		}
		$cacheHit = self::findPageInCache();

		//кеш второго уровня
		if ($cacheHit && self::$cacheOn){
			self::cachedProcessing();
		}
		else{
			self::unCachedProcessing();
		}
	}

	/**
	 * Подключение файла интерфейса
	 *
	 * @static
	 *
	 */
	private static function includeInterface()
	{
		$path = PATH_COFFE . 'interface.php';
		if (file_exists($path)){
			require($path);
		}
	}

	/**
	 * Задает префикс для всех url
	 *
	 * @static
	 * @param $prefix
	 */
	public static function setUrlPrefix($prefix)
	{
		self::$url_prefix = (string)$prefix;
	}

	/**
	 * Получить префикс для всех url
	 *
	 * @static
	 * @return string
	 */
	public static function getUrlPrefix()
	{
		return self::$url_prefix;
	}

	/**
	 * Инициализация пользователя
	 *
	 * @static
	 *
	 */
	public static function initUser()
	{
		$CUser = Coffe_User::getInstance();
		$CUser->start();
		if ($CUser->isLogin()){
			$user = $CUser->getUser();
			if (isset($user['admin']) && $user['admin']){
				self::showPanel();
			}
		}
	}

	/**
	 * Диспетчеризация
	 *
	 * @static
	 * @return array|null
	 */
	public static function dispatch()
	{
		if (self::$isDispatched) {
			return self::$page;
		}
		//Получаем uid страницы
		$uid = isset($_GET['uid']) ? $_GET['uid'] : self::getRootPageID();
		if (is_numeric($uid))
			self::pageDispatch($uid);
		Coffe_Event::call('afterDispatch', array(&self::$page));
		self::$isDispatched = true;
		return self::$page;
	}

	/**
	 * Получение ID страницы для корня сайта
	 *
	 * @static
	 * @return bool|mixed
	 */
	private static function getRootPageID()
	{
		$uid = false;
		if ($_SERVER['REQUEST_URI'] == '/' || $_SERVER['REQUEST_URI'] == '' || $_SERVER['SCRIPT_NAME'] == '/index.php'){
			$uid = self::getConfig('rootPage', false);
		}
		if (!is_numeric($uid)){
			$uid = self::getConfig('page404', false);
		}
		return $uid;
	}

	/**
	 * Функция находит текущую старницу, на основе переданного uid
	 *
	 * @static
	 * @param $uid
	 * @param null $prev_uid
	 * @return array|null
	 */
	private static function pageDispatch($uid, $prev_uid = null)
	{
		//ушли в цикл
		if ($uid == $prev_uid){
			self::$page = null;
			return self::$page;
		}
		$CPage = Coffe_Page::getInstance();
		self::$page = $CPage->getById($uid, '');
		if (is_array(self::$page)){
			//если страница ссылка
			if (self::$page['type'] == '20'){
				$link = self::$page['link'];
				if (is_numeric($link)){
					self::$page = null;
					return self::pageDispatch($link, $uid);
				}
				else{
					self::gotoPageLink(self::$page);
				}
			}
			//если страница скрыта и мы не админ
			if (self::$page['hidden'] && !Coffe::isAdmin()){
				self::$page = null;
				//если есть страница, обработки ошибки 404
				if ($page404 = self::getConfig('page404', null)){
					return self::pageDispatch(intval($page404), $uid);
				}
			}
		}
		return self::$page;
	}

	/**
	 * Редирект на ссылку
	 *
	 * @param $page
	 */
	private function gotoPageLink($page)
	{
		ob_clean();
		$link = trim($page['link']);

		//https тоже учитывается
		if ((strpos($link, 'http') === 0)){
			header('Location: '. $link);
			exit();
		}
		//относительная ссылка
		if (substr($link, 0, 1) != '/'){
			header('Location: '. self::getUrlPrefix() . $link);
			exit();
		}
		header('Location: '. $link);
		exit();
	}

	/**
	 * Ищет страницу в кеше
	 *
	 * @static
	 * @return bool
	 */
	public static function findPageInCache()
	{
		//функция вызвана повторно
		if (self::$cacheID !== null){
			return self::$cacheHit;
		}
		self::$cacheID = self::calculateCacheID();
		//если включен кеш
		if (self::$cacheOn){
			$CPage = Coffe_Page::getInstance();
			if (self::$page_cache = $CPage->findInCache(self::$cacheID, self::$page['uid'])){
				self::$cacheHit = true;
				return true;
			}
		}
		return false;
	}

	/**
	 * Вычисляет id кеша
	 *
	 * @static
	 * @return string
	 */
	private static function calculateCacheID()
	{
		$values = array('uid' => self::getID(),'auth' => self::isLogin());
		if (isset($_GET['cacheID']) && trim($_GET['cacheID'])){
			$values['cacheID'] = trim($_GET['cacheID']);
		}
		Coffe_Event::call('calculateCacheID', array(&$values));
		return md5(serialize($values));
	}

	/**
	 * Кешируемый процесс формирования контента
	 *
	 * @static
	 * @throws Coffe_Exception
	 */
	private static function cachedProcessing()
	{

		if (!is_array(self::$page_cache)){
			throw new Coffe_Exception("Data of a cache aren't found");
		}
		//восстанавливаем контент
		self::$content = self::$page_cache['content'];

		//восстанавливаем массив конфигурации из кеша
		$GLOBALS['CFA'] = unserialize(self::$page_cache['config']);

		//восстанавливаем объект Head
		Coffe_Head::createFormSerialize(self::$page_cache['head']);

		self::initHead();

		//обработка не кешируемых компонентов
		self::parseUnCachedComponentsMarkers();

		//вывод панели
		self::replacePanelMarker();

		//замена шапки
		self::replaceHeadMarker();

		//вывод контента
		self::renderContent();
	}

	/**
	 * Некешируем процесс
	 *
	 * @static
	 *
	 */
	private static function unCachedProcessing()
	{
		//формируем массив конфигурации
		Coffe_FE::loadPageConfig(self::getID(), $GLOBALS['CFA']);

		//инициализация шапки
		self::initHead();

		Coffe_Event::call('afterInitHead',array(self::getHead()));

		//инициализируем объект Head начальными параметрами
		if ($head_init = Coffe::getConfig('head', false)){
			self::$headObj->init($head_init);
		}

		Coffe_Event::call('afterInitHead',array(self::getHead()));

		//подключение шаблона
		self::includeTemplate();

		//контент страниц
		self::parseContentMarkers();

		//парсинг групп маркеров
		self::parseGroupMarkers();

		//обработка кешируемых компонентов
		self::parseCachedComponentsMarkers();

		//сохранение данных в кеш
		if (self::$cacheOn){
			Coffe_Page::getInstance()->savePageCache(self::getID(), self::$cacheID, self::$content);
		}

		//обработка не кешируемых компонентов
		self::parseUnCachedComponentsMarkers();

		//вывод панели
		self::replacePanelMarker();

		//выводим шапку
		self::replaceHeadMarker();

		//вывод контента
		self::renderContent();
	}


	/**
	 * Подключение шаблона
	 *
	 * @static
	 * @throws Coffe_Exception
	 */
	public static function includeTemplate()
	{
		//получаем текущий шаблон
		$id = trim(Coffe::getConfig('template.id', 'default'));

		self::$template_id = $id;

		if (empty($id) || !Coffe_Template::isExist($id)){
			throw new Coffe_Exception("The template isn't found");
		}
		$file = Coffe::getConfig('template.index', 'index.phtml');

		self::$templateObj = new Coffe_Template();
		self::$templateObj->setTemplate($id);

		self::$content = self::$templateObj->includeFile($file);

		//замена маркеров шаблона
		self::parseTemplateMarks();

		//HOOK
		Coffe_Event::call('afterIncludeTemplateIndex',array(&self::$templateObj, &self::$content));
	}

	/**
	 * Собирает шаблон
	 *
	 * @static
	 * @throws Coffe_Exception
	 */
	private static function parseTemplateMarks()
	{
		//собираем шаблон
		$counter = 0;
		$max_iteration = 100;
		while (preg_match('#\[\[FILE\:(.+?)\]\]#', self::$content, $out)){
			self::$content = preg_replace('#\[\[FILE\:' . preg_quote($out[1]) . '\]\]#',self::$templateObj->includeFile($out[1]),self::$content, 1);
			$counter++;
			if ($counter > $max_iteration){
				throw new Coffe_Exception('The number of iterations of a template is fixed as much as possible (' . $max_iteration . ')');
			}
		}
		//выполняем подстановку маркеров
		$marks = array('TEMPLATE_URL' => self::$templateObj->url());
		//эти маркеры нельзя использовать

		Coffe_Event::call('parseTemplateMarks',array(&$marks));
		$marks = @array_diff_key($marks, self::$reserveMarks);

		if (is_array($marks)){
			foreach ($marks as $mark => $value){
				self::$content = str_replace('[[' . $mark . ']]',$value, self::$content);
			}
		}
	}

	/**
	 * Находит и заменяет маркеры контента
	 *
	 * @static
	 *
	 */
	private static function parseContentMarkers()
	{
		self::$content = preg_replace_callback('#\[\[content\:{0,1}([0-9]*?)\]\]#', array('Coffe','replaceContentMarkers'), self::$content);
	}

	/**
	 * Ищем и заменяет маркеры контента
	 *
	 * @static
	 * @param $matches
	 * @return string
	 */
	private static function replaceContentMarkers($matches)
	{
		$position = 1;
		if (isset($matches[1]) && is_numeric($matches[1])){
			$position = intval($matches[1]);
		}
		//уменьшаем позицию, т.к. в действительности нумерация идет с нуля
		$position--;
		$content = '';
		$components = Coffe_CpManager::getRowsByPid(self::getID(), false, $position);

		//если мы не в режиме редактирования, то выводим как обычно
		if (!self::isEditMode()){
			foreach ($components as $component){
				$content .= self::includeOrToBuffer($component) . PHP_EOL;
			}
		}
		else{
			return self::renderEditedComponentList($components, self::getID());
		}
		return $content;
	}


	/**
	 * Находит и заменяет маркеры групп
	 *
	 * @static
	 *
	 */
	private static function parseGroupMarkers()
	{
		self::$content = preg_replace_callback('#\[\[group\:([^\]]+?)\]\]#', array('Coffe','replaceGroupMarkers'), self::$content);
	}

	/**
	 * Ищем и заменяет маркеры групп компонентов
	 *
	 * @static
	 * @param $matches
	 * @return string
	 */
	private static function replaceGroupMarkers($matches)
	{
		$content = '';
		if (isset($matches[1])){
			$group = trim($matches[1]);
			$content = '';
			$components = Coffe_CpManager::getRowsByGroup($group,false);

			//если мы не в режиме редактирования, то выводим как обычно
			if (!self::isEditMode()){
				foreach ($components as $component){
					$content .= self::includeOrToBuffer($component) . PHP_EOL;
				}
			}
			else{
				return self::renderEditedComponentList($components, null, $group);
			}
		}
		return $content;
	}


	/**
	 * Выводит список комопнентов без кеша с передачей дополнительных параметров в события
	 *
	 * @static
	 * @param $components
	 * @param null $pid
	 * @param null $cp_group
	 * @return string
	 */
	private static function renderEditedComponentList($components, $pid = null, $cp_group = null)
	{
		$total_content = '';
		if (is_array($components)){
			foreach($components as $key => $component){
				$config = null;
				if (isset($component['config'])){
					$config = self::getComponentConfig($component['config']);
				}
				Coffe_Event::call('beforeComponentInclude', array(&$component['id'], &$component['template'], &$config, &$component, $key, $components));
				$content = Coffe_CpManager::includeComponent($component['id'], $component['template'], $config, $component);
				Coffe_Event::call('afterComponentInclude',array(&$content, $component['id'], $component['template'], $config, $component, $key, $components));
				$total_content .= $content;
			}
		}
		return $total_content;
	}

	/**
	 * Обработка поля config для компонента
	 *
	 * @static
	 * @param $field
	 * @return array|mixed|null
	 */
	private static function getComponentConfig($field)
	{
		if (Coffe_Event::isRegister('getComponentConfig')){
			return Coffe_Event::callLast('getComponentConfig', array($field));
		}
		$config = null;
		if (trim($field)){
			$config = unserialize($field);
		}
		return $config;
	}

	/**
	 * Подключает компонент или добавляет его в буфер
	 *
	 * @static
	 * @param $component
	 * @param array $config
	 * @return string
	 */
	private static function includeOrToBuffer($component, $config  = array())
	{
		if ($component['cache'] > 0){
			return self::includeComponentRow($component);
		}
		//некешируемый компонент
		else{
			$bufferId = uniqid();
			self::$component_buffer[$bufferId] = $component;
			$config['_bufferId_'] = $bufferId;
			return '{!component:' . $component['uid'] . '?' . http_build_query($config) .'!}';
		}
	}

	/**
	 * Выполняет поиск и замену кешируемых компонентов
	 *
	 * @static
	 *
	 */
	private static function parseCachedComponentsMarkers()
	{
		self::$content = preg_replace_callback('#\[\!component\:(.+?)\!\]#',array('Coffe','replaceCachedComponentsMarkers'), self::$content);
	}

	/**
	 * Заменяет маркеры кешируемых компонентов в шаблоне
	 *
	 * @static
	 * @param $matches
	 * @return string
	 */
	private static function replaceCachedComponentsMarkers($matches)
	{
		$component_str = $matches[1];
		self::parseComponentStr($component_str, $id, $config);
		//подключение строки компонента из базы
		if (is_numeric($id)){
			if ($component = Coffe_CpManager::getRowById($id)){
				return self::includeOrToBuffer($component, $config);
			}
			return self::getComponentNotFoundContent();
		}
		//прямое подключение компонента
		else{
			return self::includeComponent($id, isset($config['template']) ? $config['template'] : 'coffe:default', $config);
		}
	}

	/**
	 * Выполняет поиск и замену некешируемых компонентов
	 *
	 * @static
	 *
	 */
	private static function parseUnCachedComponentsMarkers()
	{
		self::$content = preg_replace_callback('#\{\!component\:(.+?)\!\}#',array('Coffe','replaceUnCachedComponentsMarkers'), self::$content);
	}

	/**
	 * Заменяет маркеры кешируемых компонентов в шаблоне
	 *
	 * @static
	 * @param $matches
	 * @return string
	 */
	private static function replaceUnCachedComponentsMarkers($matches)
	{
		$component_str = $matches[1];
		self::parseComponentStr($component_str, $id, $config);
		//подключение строки компонента из базы
		if (is_numeric($id)){
			$id = intval($id);
			if (isset($config['_bufferId_']) && isset(self::$component_buffer[$config['_bufferId_']])){
				$component = self::$component_buffer[$config['_bufferId_']];
			}
			else{
				$component = Coffe_CpManager::getRowById($id);
			}
			if ($component){
				return self::includeComponentRow($component);
			}
			return self::getComponentNotFoundContent();
		}
		//прямое подключение компонента
		else{
			$template = isset($config['_template_']) ? $config['_template_'] : 'coffe:default';
			self::clearSystemComponentParams($config);
			return self::includeComponent($id, $template, $config);
		}
	}

	/**
	 * Разбивает строку подключения компонента на части
	 *
	 * @static
	 * @param $component_str
	 * @param $id
	 * @param $config
	 */
	private static function parseComponentStr($component_str, &$id, &$config)
	{
		$config = array();
		//ищем параметры
		$pos = strpos($component_str, '?');
		if ($pos !== false){
			$id = trim(substr($component_str, 0, $pos));
			$query = substr($component_str, $pos + 1);
			parse_str($query, $config);
		}
		else{
			$id = $component_str;
		}
	}

	/**
	 * Удаляет из массива системные параметры
	 *
	 * @static
	 * @param $params
	 */
	private static function clearSystemComponentParams(&$params)
	{
		if (is_array($params)){
			foreach($params as $key => $value){
				if ($key[0] == '_' && $key[strlen($key) - 1] == '_'){
					unset($params[$key]);
				}
			}
		}
	}

	/**
	 * Подключение компонента
	 *
	 * @static
	 * @param $id
	 * @param string $template
	 * @param null $config
	 * @param null $data
	 * @return string
	 */
	public static function includeComponent($id, $template = 'coffe:default', $config = null, $data = null)
	{
		//hook
		Coffe_Event::call('beforeComponentInclude',array(&$id, &$template, &$config, &$data, null, null));
		$content = Coffe_CpManager::includeComponent($id, $template, $config, $data);
		//hook
		Coffe_Event::call('afterComponentInclude',array(&$content, $id, $template, $config, $data, null, null));
		return $content;
	}

	/**
	 * Подключение компонента на основе строки таблицы из базы
	 *
	 * @static
	 * @param $row
	 * @return string
	 */
	private static function includeComponentRow($row)
	{
		$config = null;
		if (isset($row['config'])){
			$config = self::getComponentConfig($row['config']);
		}
		//вызов компонента
		return self::includeComponent($row['id'], $row['template'], $config, $row);
	}

	/**
	 * Возвращает контент для комопнента, который не был найден
	 *
	 * @static
	 * @return string
	 */
	private static function getComponentNotFoundContent()
	{
		return 'Component not found!';
	}

	/**
	 * Вывод данных шапки
	 *
	 * @static
	 *
	 */
	private static function replaceHeadMarker()
	{
		self::$content = str_replace('[[HEAD]]', self::$headObj->renderHead(), self::$content);
	}

	/**
	 * Вывод панели
	 *
	 * @static
	 *
	 */
	private static function replacePanelMarker()
	{
		self::$content = str_replace('[[PANEL]]',self::isAdmin() ? Coffe_ModuleManager::printPanel() : '', self::$content);
	}

	/**
	 * Вывод контента
	 *
	 * @static
	 *
	 */
	private static function  renderContent()
	{
		echo self::$content;
	}

	/**
	 * Получение конфигурации из массива CFA
	 *
	 * @static
	 * @param null $key
	 * @param null $default
	 * @return mixed
	 */
	public static function getConfig($key = null, $default = null)
	{
		if ($key !== null){
			$keys = explode('.', $key);
			$value = self::recursiveFindElement($GLOBALS['CFA'],$keys);
			return ($value === null) ? $default : $value;
		}
		return $GLOBALS['CFA'];
	}

	/**
	 * Рекурсивно находит элемент в массиве конфигурации
	 *
	 * @static
	 * @param $array
	 * @param $keys
	 * @return mixed
	 */
	private static function recursiveFindElement(&$array, &$keys)
	{
		$key = array_shift($keys);
		return (isset($array[$key])) ? (count($keys) ? self::recursiveFindElement($array[$key], $keys) : $array[$key]) : null;
	}

	/**
	 * Скрыть административное меню
	 *
	 * @static
	 *
	 */
	public static function hidePanel()
	{
		self::$showPanel = false;
	}

	/**
	 * Показать административное меню
	 *
	 * @static
	 *
	 */
	public static function showPanel()
	{
		self::$showPanel = true;
	}

	/**
	 * Видима ли панель меню
	 *
	 * @static
	 * @return bool
	 */
	public static function isPanelVisible()
	{
		return self::$showPanel;
	}

	/**
	 * Была ли запущена сессия
	 *
	 * @static
	 * @return bool
	 */
	public static function isSessionStarted()
	{
		return self::$sessionStarted;
	}

	/**
	 * Стартует сессию
	 *
	 * @static
	 *
	 */
	public static function startSession()
	{
		if (!self::isSessionStarted()){
			//определяем имя группы сессии
			$session_name = self::getConfig('coffe.session_name', self::$session_name);
			if (trim($session_name))
				session_name($session_name);
			session_start();
			self::$sessionStarted = true;
		}
	}

	/**
	 * Проверяет, что пользователь является админом
	 *
	 * @static
	 * @return bool
	 */
	public static function isAdmin()
	{
		$CUser = Coffe_User::getInstance();
		if ($CUser->isLogin()){
			$user = $CUser->getUser();
			return (isset($user['admin']) && ($user['admin'] > 0));
		}
		return false;
	}

	/**
	 * Проверяет, находимся ли мы в административном режиме
	 *
	 * @static
	 * @return bool
	 */
	public static function isEditMode()
	{
		if (self::isAdmin()){
			$edit = intval(Coffe_User::getInstance()->getPermanentData('admin_edit_mode'));
			return ($edit > 0);
		}
		return false;
	}

	/**
	 * Авторизован ли пользователь
	 *
	 * @static
	 * @return bool
	 */
	public static function isLogin()
	{
		return Coffe_User::getInstance()->isLogin();
	}

	/**
	 * Возврашает текущую страницу
	 *
	 * @static
	 * @return array|null
	 */
	public static function getPage()
	{
		return self::$page;
	}

	/**
	 * Возвращает id текущей страницы
	 *
	 * @static
	 * @return null|int
	 */
	public static function getID()
	{
		return is_array(self::$page) ? self::$page['uid'] : null;
	}

	/**
	 * Получение количества компонентов
	 *
	 * @static
	 * @param int $position
	 * @param null $page
	 * @param bool $hidden
	 * @return mixed
	 */
	public static function getComponentPositionCount($position = null, $page = null, $hidden = false)
	{
		if ($page === null){
			$page = intval(self::getID());
		}
		if ($position !== null){
			//уменьшаем позицию, т.к. в действительности нумерация идет с нуля
			$position = intval($position) - 1;
		}
		return Coffe_CpManager::getCountRows($page, (bool)$hidden, $position);
	}


	/**
	 * Инициализация объекта Head
	 *
	 * @static
	 * @return Coffe_Head|null
	 */
	public static function initHead()
	{
		if (self::$headObj !== null){
			return self::$headObj;
		}
		self::$headObj = Coffe_Head::getInstance();
		return self::$headObj;
	}

	/**
	 * Получение объекта Coffe_Head
	 *
	 * @static
	 * @return Coffe_Head|null
	 */
	public static function getHead()
	{
		return self::initHead();
	}

	/**
	 * Получение id текущего шаблона
	 *
	 * @static
	 * @return mixed
	 */
	public static function getTemplateID()
	{
		return self::$template_id;
	}

	/**
	 * Получение ссылки для страницы
	 *
	 * @static
	 * @param null $pageId
	 * @param array $params
	 * @return string
	 */
	public static function getPageUrl($pageId = null, $params = array())
	{
		$params = (array)$params;
		$params['uid'] = ($pageId !== null) ? intval($pageId) : self::getID();
		$url = self::getUrlPrefix() . 'index.php';
		if (count($params)){
			$url .= '?' . http_build_query($params);
		}
		return $url;
	}

	/**
	 * Очистка кеша
	 *
	 * @static
	 * @param null $uid
	 */
	public static function clearPageCache($uid = null)
	{
		$CPage = Coffe_Page::getInstance();
		$CPage->clearCache($uid);
	}

	/**
	 * Задает заголовок для текущей страницы
	 *
	 * @param $title
	 */
	public static function setTitle($title)
	{
		self::getHead()->setTitle($title);
	}


	public static function executeAjaxRequest()
	{
		$id = (string)$_GET['cfAjax'];
		if (Coffe_Event::isRegister('cfAjax.' . $id)){
			Coffe_Event::callLast('cfAjax.' . $id);
		}
		exit();
	}

}