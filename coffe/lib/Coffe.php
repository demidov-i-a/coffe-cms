<?php
/**
 * �������� ��������� �����
 *
 * @package coffe_cms
 */

class Coffe
{

	/**
	 * �������� ���������������� ����
	 *
	 * @var bool
	 */
	private static $showPanel = false;

	/**
	 * ������ Head
	 *
	 * @var Coffe_Head
	 */
	private static $headObj = null;


	/**
	 * ������ Template
	 *
	 * @var Coffe_Template
	 */
	private static $templateObj = null;


	/**
	 * ������� ��������
	 *
	 * @var null|array
	 */
	private static $page = null;


	/**
	 * ���������� ������
	 *
	 * @var bool
	 */
	private static $sessionStarted = false;


	/**
	 * ��������� �� ���������������
	 *
	 * @var bool
	 */
	private static $isDispatched = false;

	/**
	 * ���� �������
	 *
	 * @var string
	 */
	private static $content = '';

	/**
	 * ������ �� ���������� �����������
	 *
	 * @var array
	 */
	private static $component_buffer = array();


	/**
	 * @var string
	 */
	private static $cacheID = null;


	/**
	 * ������ ���������� ���� ��������
	 *
	 * @var null
	 */
	private static $page_cache = null;

	/**
	 * ����� �������� � ����
	 *
	 * @var bool
	 */
	private static $cacheHit = false;

	/**
	 * ���������� ���� ����
	 *
	 * @var bool
	 */
	private static $cacheOn = true;


	/**
	 * ����������������� �������� �������
	 *
	 * @var array
	 */
	private static $reserveMarks = array('PANEL', 'HEAD');


	/**
	 * ID �������� �������
	 *
	 * @var null
	 */
	private static $template_id = null;

	/**
	 * �������� ������ � ���������� ������
	 *
	 * @var bool
	 */
	private static $run = false;

	/**
	 * ��� �������� ������
	 *
	 * @var string
	 */
	private static $session_name = 'coffe_cms';

	/**
	 * ����������� �� ���� �������
	 *
	 * @var string
	 */
	private static $url_prefix = '';


	/**
	 * ������ ������� � ����������� ������
	 *
	 * @static
	 * @throws Coffe_Exception
	 */
	public static function run()
	{
		if (self::$run){
			throw new Coffe_Exception('It was already launched');
		}

		//������������� ������������
		self::initUser();

		//���������� ���� ����������
		self::includeInterface();

		//������ ������� ��� ������
		self::setUrlPrefix(self::getConfig('urlPrefix', ''));

		//����������� �������
		Coffe_ModuleManager::initModules();

		if (isset($_GET['cfAjax'])){
			self::executeAjaxRequest();
		}

		//�������� �� ���
		self::$cacheOn = !((bool)self::getConfig('cacheOff',false));

		//��������� ���, ���� �� ��������� � ������ ��������������
		if (self::isEditMode()){
			self::$cacheOn = false;
		}

		//������� ������� ��������
		$page = self::dispatch();

		if (!is_array($page)){
			throw new Coffe_Exception("The page isn't found");
		}
		$cacheHit = self::findPageInCache();

		//��� ������� ������
		if ($cacheHit && self::$cacheOn){
			self::cachedProcessing();
		}
		else{
			self::unCachedProcessing();
		}
	}

	/**
	 * ����������� ����� ����������
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
	 * ������ ������� ��� ���� url
	 *
	 * @static
	 * @param $prefix
	 */
	public static function setUrlPrefix($prefix)
	{
		self::$url_prefix = (string)$prefix;
	}

	/**
	 * �������� ������� ��� ���� url
	 *
	 * @static
	 * @return string
	 */
	public static function getUrlPrefix()
	{
		return self::$url_prefix;
	}

	/**
	 * ������������� ������������
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
	 * ���������������
	 *
	 * @static
	 * @return array|null
	 */
	public static function dispatch()
	{
		if (self::$isDispatched) {
			return self::$page;
		}
		//�������� uid ��������
		$uid = isset($_GET['uid']) ? $_GET['uid'] : self::getRootPageID();
		if (is_numeric($uid))
			self::pageDispatch($uid);
		Coffe_Event::call('afterDispatch', array(&self::$page));
		self::$isDispatched = true;
		return self::$page;
	}

	/**
	 * ��������� ID �������� ��� ����� �����
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
	 * ������� ������� ������� ��������, �� ������ ����������� uid
	 *
	 * @static
	 * @param $uid
	 * @param null $prev_uid
	 * @return array|null
	 */
	private static function pageDispatch($uid, $prev_uid = null)
	{
		//���� � ����
		if ($uid == $prev_uid){
			self::$page = null;
			return self::$page;
		}
		$CPage = Coffe_Page::getInstance();
		self::$page = $CPage->getById($uid, '');
		if (is_array(self::$page)){
			//���� �������� ������
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
			//���� �������� ������ � �� �� �����
			if (self::$page['hidden'] && !Coffe::isAdmin()){
				self::$page = null;
				//���� ���� ��������, ��������� ������ 404
				if ($page404 = self::getConfig('page404', null)){
					return self::pageDispatch(intval($page404), $uid);
				}
			}
		}
		return self::$page;
	}

	/**
	 * �������� �� ������
	 *
	 * @param $page
	 */
	private function gotoPageLink($page)
	{
		ob_clean();
		$link = trim($page['link']);

		//https ���� �����������
		if ((strpos($link, 'http') === 0)){
			header('Location: '. $link);
			exit();
		}
		//������������� ������
		if (substr($link, 0, 1) != '/'){
			header('Location: '. self::getUrlPrefix() . $link);
			exit();
		}
		header('Location: '. $link);
		exit();
	}

	/**
	 * ���� �������� � ����
	 *
	 * @static
	 * @return bool
	 */
	public static function findPageInCache()
	{
		//������� ������� ��������
		if (self::$cacheID !== null){
			return self::$cacheHit;
		}
		self::$cacheID = self::calculateCacheID();
		//���� ������� ���
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
	 * ��������� id ����
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
	 * ���������� ������� ������������ ��������
	 *
	 * @static
	 * @throws Coffe_Exception
	 */
	private static function cachedProcessing()
	{

		if (!is_array(self::$page_cache)){
			throw new Coffe_Exception("Data of a cache aren't found");
		}
		//��������������� �������
		self::$content = self::$page_cache['content'];

		//��������������� ������ ������������ �� ����
		$GLOBALS['CFA'] = unserialize(self::$page_cache['config']);

		//��������������� ������ Head
		Coffe_Head::createFormSerialize(self::$page_cache['head']);

		self::initHead();

		//��������� �� ���������� �����������
		self::parseUnCachedComponentsMarkers();

		//����� ������
		self::replacePanelMarker();

		//������ �����
		self::replaceHeadMarker();

		//����� ��������
		self::renderContent();
	}

	/**
	 * ���������� �������
	 *
	 * @static
	 *
	 */
	private static function unCachedProcessing()
	{
		//��������� ������ ������������
		Coffe_FE::loadPageConfig(self::getID(), $GLOBALS['CFA']);

		//������������� �����
		self::initHead();

		Coffe_Event::call('afterInitHead',array(self::getHead()));

		//�������������� ������ Head ���������� �����������
		if ($head_init = Coffe::getConfig('head', false)){
			self::$headObj->init($head_init);
		}

		Coffe_Event::call('afterInitHead',array(self::getHead()));

		//����������� �������
		self::includeTemplate();

		//������� �������
		self::parseContentMarkers();

		//������� ����� ��������
		self::parseGroupMarkers();

		//��������� ���������� �����������
		self::parseCachedComponentsMarkers();

		//���������� ������ � ���
		if (self::$cacheOn){
			Coffe_Page::getInstance()->savePageCache(self::getID(), self::$cacheID, self::$content);
		}

		//��������� �� ���������� �����������
		self::parseUnCachedComponentsMarkers();

		//����� ������
		self::replacePanelMarker();

		//������� �����
		self::replaceHeadMarker();

		//����� ��������
		self::renderContent();
	}


	/**
	 * ����������� �������
	 *
	 * @static
	 * @throws Coffe_Exception
	 */
	public static function includeTemplate()
	{
		//�������� ������� ������
		$id = trim(Coffe::getConfig('template.id', 'default'));

		self::$template_id = $id;

		if (empty($id) || !Coffe_Template::isExist($id)){
			throw new Coffe_Exception("The template isn't found");
		}
		$file = Coffe::getConfig('template.index', 'index.phtml');

		self::$templateObj = new Coffe_Template();
		self::$templateObj->setTemplate($id);

		self::$content = self::$templateObj->includeFile($file);

		//������ �������� �������
		self::parseTemplateMarks();

		//HOOK
		Coffe_Event::call('afterIncludeTemplateIndex',array(&self::$templateObj, &self::$content));
	}

	/**
	 * �������� ������
	 *
	 * @static
	 * @throws Coffe_Exception
	 */
	private static function parseTemplateMarks()
	{
		//�������� ������
		$counter = 0;
		$max_iteration = 100;
		while (preg_match('#\[\[FILE\:(.+?)\]\]#', self::$content, $out)){
			self::$content = preg_replace('#\[\[FILE\:' . preg_quote($out[1]) . '\]\]#',self::$templateObj->includeFile($out[1]),self::$content, 1);
			$counter++;
			if ($counter > $max_iteration){
				throw new Coffe_Exception('The number of iterations of a template is fixed as much as possible (' . $max_iteration . ')');
			}
		}
		//��������� ����������� ��������
		$marks = array('TEMPLATE_URL' => self::$templateObj->url());
		//��� ������� ������ ������������

		Coffe_Event::call('parseTemplateMarks',array(&$marks));
		$marks = @array_diff_key($marks, self::$reserveMarks);

		if (is_array($marks)){
			foreach ($marks as $mark => $value){
				self::$content = str_replace('[[' . $mark . ']]',$value, self::$content);
			}
		}
	}

	/**
	 * ������� � �������� ������� ��������
	 *
	 * @static
	 *
	 */
	private static function parseContentMarkers()
	{
		self::$content = preg_replace_callback('#\[\[content\:{0,1}([0-9]*?)\]\]#', array('Coffe','replaceContentMarkers'), self::$content);
	}

	/**
	 * ���� � �������� ������� ��������
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
		//��������� �������, �.�. � ���������������� ��������� ���� � ����
		$position--;
		$content = '';
		$components = Coffe_CpManager::getRowsByPid(self::getID(), false, $position);

		//���� �� �� � ������ ��������������, �� ������� ��� ������
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
	 * ������� � �������� ������� �����
	 *
	 * @static
	 *
	 */
	private static function parseGroupMarkers()
	{
		self::$content = preg_replace_callback('#\[\[group\:([^\]]+?)\]\]#', array('Coffe','replaceGroupMarkers'), self::$content);
	}

	/**
	 * ���� � �������� ������� ����� �����������
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

			//���� �� �� � ������ ��������������, �� ������� ��� ������
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
	 * ������� ������ ����������� ��� ���� � ��������� �������������� ���������� � �������
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
	 * ��������� ���� config ��� ����������
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
	 * ���������� ��������� ��� ��������� ��� � �����
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
		//������������ ���������
		else{
			$bufferId = uniqid();
			self::$component_buffer[$bufferId] = $component;
			$config['_bufferId_'] = $bufferId;
			return '{!component:' . $component['uid'] . '?' . http_build_query($config) .'!}';
		}
	}

	/**
	 * ��������� ����� � ������ ���������� �����������
	 *
	 * @static
	 *
	 */
	private static function parseCachedComponentsMarkers()
	{
		self::$content = preg_replace_callback('#\[\!component\:(.+?)\!\]#',array('Coffe','replaceCachedComponentsMarkers'), self::$content);
	}

	/**
	 * �������� ������� ���������� ����������� � �������
	 *
	 * @static
	 * @param $matches
	 * @return string
	 */
	private static function replaceCachedComponentsMarkers($matches)
	{
		$component_str = $matches[1];
		self::parseComponentStr($component_str, $id, $config);
		//����������� ������ ���������� �� ����
		if (is_numeric($id)){
			if ($component = Coffe_CpManager::getRowById($id)){
				return self::includeOrToBuffer($component, $config);
			}
			return self::getComponentNotFoundContent();
		}
		//������ ����������� ����������
		else{
			return self::includeComponent($id, isset($config['template']) ? $config['template'] : 'coffe:default', $config);
		}
	}

	/**
	 * ��������� ����� � ������ ������������ �����������
	 *
	 * @static
	 *
	 */
	private static function parseUnCachedComponentsMarkers()
	{
		self::$content = preg_replace_callback('#\{\!component\:(.+?)\!\}#',array('Coffe','replaceUnCachedComponentsMarkers'), self::$content);
	}

	/**
	 * �������� ������� ���������� ����������� � �������
	 *
	 * @static
	 * @param $matches
	 * @return string
	 */
	private static function replaceUnCachedComponentsMarkers($matches)
	{
		$component_str = $matches[1];
		self::parseComponentStr($component_str, $id, $config);
		//����������� ������ ���������� �� ����
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
		//������ ����������� ����������
		else{
			$template = isset($config['_template_']) ? $config['_template_'] : 'coffe:default';
			self::clearSystemComponentParams($config);
			return self::includeComponent($id, $template, $config);
		}
	}

	/**
	 * ��������� ������ ����������� ���������� �� �����
	 *
	 * @static
	 * @param $component_str
	 * @param $id
	 * @param $config
	 */
	private static function parseComponentStr($component_str, &$id, &$config)
	{
		$config = array();
		//���� ���������
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
	 * ������� �� ������� ��������� ���������
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
	 * ����������� ����������
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
	 * ����������� ���������� �� ������ ������ ������� �� ����
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
		//����� ����������
		return self::includeComponent($row['id'], $row['template'], $config, $row);
	}

	/**
	 * ���������� ������� ��� ����������, ������� �� ��� ������
	 *
	 * @static
	 * @return string
	 */
	private static function getComponentNotFoundContent()
	{
		return 'Component not found!';
	}

	/**
	 * ����� ������ �����
	 *
	 * @static
	 *
	 */
	private static function replaceHeadMarker()
	{
		self::$content = str_replace('[[HEAD]]', self::$headObj->renderHead(), self::$content);
	}

	/**
	 * ����� ������
	 *
	 * @static
	 *
	 */
	private static function replacePanelMarker()
	{
		self::$content = str_replace('[[PANEL]]',self::isAdmin() ? Coffe_ModuleManager::printPanel() : '', self::$content);
	}

	/**
	 * ����� ��������
	 *
	 * @static
	 *
	 */
	private static function  renderContent()
	{
		echo self::$content;
	}

	/**
	 * ��������� ������������ �� ������� CFA
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
	 * ���������� ������� ������� � ������� ������������
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
	 * ������ ���������������� ����
	 *
	 * @static
	 *
	 */
	public static function hidePanel()
	{
		self::$showPanel = false;
	}

	/**
	 * �������� ���������������� ����
	 *
	 * @static
	 *
	 */
	public static function showPanel()
	{
		self::$showPanel = true;
	}

	/**
	 * ������ �� ������ ����
	 *
	 * @static
	 * @return bool
	 */
	public static function isPanelVisible()
	{
		return self::$showPanel;
	}

	/**
	 * ���� �� �������� ������
	 *
	 * @static
	 * @return bool
	 */
	public static function isSessionStarted()
	{
		return self::$sessionStarted;
	}

	/**
	 * �������� ������
	 *
	 * @static
	 *
	 */
	public static function startSession()
	{
		if (!self::isSessionStarted()){
			//���������� ��� ������ ������
			$session_name = self::getConfig('coffe.session_name', self::$session_name);
			if (trim($session_name))
				session_name($session_name);
			session_start();
			self::$sessionStarted = true;
		}
	}

	/**
	 * ���������, ��� ������������ �������� �������
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
	 * ���������, ��������� �� �� � ���������������� ������
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
	 * ����������� �� ������������
	 *
	 * @static
	 * @return bool
	 */
	public static function isLogin()
	{
		return Coffe_User::getInstance()->isLogin();
	}

	/**
	 * ���������� ������� ��������
	 *
	 * @static
	 * @return array|null
	 */
	public static function getPage()
	{
		return self::$page;
	}

	/**
	 * ���������� id ������� ��������
	 *
	 * @static
	 * @return null|int
	 */
	public static function getID()
	{
		return is_array(self::$page) ? self::$page['uid'] : null;
	}

	/**
	 * ��������� ���������� �����������
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
			//��������� �������, �.�. � ���������������� ��������� ���� � ����
			$position = intval($position) - 1;
		}
		return Coffe_CpManager::getCountRows($page, (bool)$hidden, $position);
	}


	/**
	 * ������������� ������� Head
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
	 * ��������� ������� Coffe_Head
	 *
	 * @static
	 * @return Coffe_Head|null
	 */
	public static function getHead()
	{
		return self::initHead();
	}

	/**
	 * ��������� id �������� �������
	 *
	 * @static
	 * @return mixed
	 */
	public static function getTemplateID()
	{
		return self::$template_id;
	}

	/**
	 * ��������� ������ ��� ��������
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
	 * ������� ����
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
	 * ������ ��������� ��� ������� ��������
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