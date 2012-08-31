<?php

/**
 * Класс для управления модулями
 *
 * @package coffe_cms
 */
class Coffe_ModuleManager
{

	/**
	 * Массив загруженных модулей
	 *
	 * @var array
	 */
	protected static $_modules = array();

	/**
	 * Массив подключенных модулей
	 *
	 * @var array
	 */
	protected static $_included_modules = array();

	/**
	 * Директория с модулями
	 *
	 * @var string
	 */
	protected static $_modules_dir = 'coffe/modules/';

	/**
	 * Файл с описанием модуля
	 *
	 * @var string
	 */
	protected static $_module_desc_file = 'description.php';

	/**
	 * Файл инициализации модуля
	 *
	 * @var string
	 */
	protected static $_module_init_file = 'init.php';

	/**
	 * Файл подключения модуля
	 *
	 * @var string
	 */
	protected static $_module_include_file = 'init.php';


	/**
	 * Массив загруженных backend-модулей
	 *
	 * @var array
	 */
	protected static $_backend_modules = array();

	/**
	 * Путь к файлу для запуска backend-модулей
	 *
	 * @var string
	 */
	protected static $_backend_module_execute = 'coffe/module.php';

	/**
	 * административное меню
	 *
	 * @var array
	 */
	protected static $_menu = array();

	/**
	 * Возможные атрибуты колонки в схеме
	 *
	 * @var array
	 */
	protected static $scheme_column_attributes = array(
		'type','length','autoincrement' , 'unsigned', 'null' , 'default' ,'binary'
	);

	/**
	 * Разрешенные типы столбцов в схемах
	 *
	 * @var array
	 */
	protected static $allowed_scheme_types = array(
		'int', 'tinyint', 'bigint' , 'float', 'double' , 'date', 'datetime', 'timestamp', 'tinytext',
		'tinyblob', 'text', 'blob'
	);


	/**
	 * Описание модулей
	 *
	 * @var array
	 */
	protected static $_description = null;

	/**
	 * Подключение модулей
	 *
	 * @static
	 *
	 */
	public static function initModules()
	{
		$modules = self::getInstalledModules();
		foreach ($modules as $module){
			self::initModule($module);
		}
	}

	/**
	 * Отдает массив установленных модулей
	 *
	 * @static
	 * @return array
	 */
	private static function getInstalledModules()
	{
		$modules = Coffe::getConfig('modules', '');
		$modules = Coffe_Functions::trimExplode(',', $modules, true);
		//основной модуль
		if (!in_array('main', $modules)){
			array_push($modules, 'main');
		}

		return $modules;
	}



	/**
	 * @static
	 * @param $module
	 * @return bool
	 * @throws Coffe_Exception
	 */
	public static function initModule($module)
	{
		$module_key = trim(strtolower($module));
		//модуль уже загружен
		if (self::isModuleInit($module_key)){
			return true;
		}

		$directory = self::$_modules_dir;
		$module_dir = PATH_ROOT . $directory . $module . '/';
		if (!is_dir($module_dir) || !is_readable($module_dir)){
			throw new Coffe_Exception('Incorrect directory of the module: "' . htmlspecialchars($module_dir) . '". Please check a key "modules" in the file config.ini');
		}
		self::$_modules[$module_key]['path'] = $directory . $module . '/';
		self::$_modules[$module_key]['name'] = $module_key;

		if (file_exists($module_dir . self::$_module_init_file)){
			//не require_once, т.к. выше всё равно происходит проверка на повторное включение
			require($module_dir . self::$_module_init_file);
		}

		return true;
	}

	/**
	 * Подключение модуля
	 *
	 * @static
	 * @param $module
	 * @return bool
	 */
	public static function includeModule($module)
	{
		if (self::isModuleIncluded($module))
			return true;
		if ($module = self::getModule($module)){
			$obj = self::getModuleObject($module['name']);
			if (is_object($obj) && method_exists($obj, 'includeModule')){
				call_user_func(array($obj, 'includeModule'));
				self::$_included_modules[$module['name']] = true;
				return true;
			}
		}
		return false;
	}


	/**
	 * Получение экземпляра основного объекта модуля
	 *
	 * @static
	 * @param $module
	 * @return null|object
	 */
	public static function getModuleObject($module)
	{
		$module = self::getModule($module);
		if ($module){
			if (isset($module['object'])){
				return  $module['object'];
			}
			$module_file = PATH_ROOT . $module['path'] . 'module.php';
			if (file_exists($module_file)){
				require($module_file);
				$module_class = $module['name'] . '_Module';
				if (class_exists($module_class)){
					$obj = new $module_class;
					self::$_modules[$module['name']]['object'] = $obj;
					return $obj;
				}
			}
		}
		return null;
	}

	/**
	 * Загружает языковой файл модуля в глобальный объект
	 *
	 * @static
	 * @param $module
	 * @return bool
	 */
	public static function loadModuleLang($module)
	{
		$module = self::getModule($module);
		if ($module){
			$GLOBALS['LANG']->loadFile(Coffe_ModuleManager::getModuleAbsPath($module['name']) . 'lang.xml');
			return true;
		}
		return false;
	}

	/**
	 * Проверяет, загружен ли модуль
	 *
	 * @static
	 * @param string $module
	 * @return bool
	 */
	public static function isModuleInit($module)
	{
		$module = trim(strtolower($module));
		return (isset(self::$_modules[$module])) ? true: false;
	}

	/**
	 * Проверяет, подключен ли модуль
	 *
	 * @static
	 * @param string $module
	 * @return bool
	 */
	public static function isModuleIncluded($module)
	{
		$module = trim(strtolower($module));
		return (isset(self::$_included_modules[$module])) ? true: false;
	}

	/**
	 * Получение модуля
	 *
	 * @static
	 * @param $module
	 * @return null|array
	 */
	public static function getModule($module)
	{
		$module = trim(strtolower($module));
		return (isset(self::$_modules[$module])) ? self::$_modules[$module]: null;
	}

	/**
	 * Получение всех загруженных модулей
	 *
	 * @static
	 * @return array
	 */
	public static function getModules()
	{
		return self::$_modules;
	}

	/**
	 * Получение пути к папке с загруженным модулем
	 *
	 * @static
	 * @param $module
	 * @return null|string
	 */
	public static function getModulePath($module)
	{
		$module = trim(strtolower($module));
		return (isset(self::$_modules[$module])) ? self::$_modules[$module]['path']: null;
	}

	/**
	 * Получение url к папке с модулем
	 *
	 * @static
	 * @param $module
	 * @return string|bool
	 */
	public static function getModuleRelPath($module)
	{
		$module = strtolower($module);
		if (self::isModuleInit($module)){
			return Coffe::getUrlPrefix() . self::$_modules[$module]['path'];
		}
		return false;
	}

	/**
	 * Получение абсолютного пути к папке с модулем
	 *
	 * @static
	 * @param $module
	 * @return string|bool
	 */
	public static function getModuleAbsPath($module)
	{
		$module = trim(strtolower($module));
		if (self::isModuleInit($module)){
			return PATH_ROOT . self::$_modules[$module]['path'];
		}
		return false;
	}


	/**
	 * TODO: получать только модули, у которых есть файл описания
	 *
	 * Получение описания всех модулей (в том числе не установленных)
	 *
	 * @static
	 * @return array
	 * @throws Coffe_Exception
	 */
	public static function getModulesDescription()
	{
		if (self::$_description !== null){
			return self::$_description;
		}
		$modules = array();
		if(!is_readable(PATH_ROOT . self::$_modules_dir)){
			throw new Coffe_Exception('Not incorrect directory of the module: ' . self::$_modules_dir);
		}
		$dh = opendir(PATH_ROOT . self::$_modules_dir);
		$LANG =  new Coffe_Translate();
		while (false !== ($sub_dir = readdir($dh))) {
			if (is_dir(PATH_ROOT . self::$_modules_dir . $sub_dir) && $sub_dir !== '.' && $sub_dir !== '..') {
				$module_key = trim(strtolower($sub_dir));
				$modules[$module_key]['path'] = self::$_modules_dir . $sub_dir . '/';
				$modules[$module_key]['name'] = $module_key;
				$modules[$module_key]['user'] = true;
				if (file_exists(PATH_ROOT . $modules[$module_key]['path'] . self::$_module_desc_file)){
					$modules[$module_key]['description'] = require(PATH_ROOT . $modules[$module_key]['path'] . self::$_module_desc_file);
					if (is_array($modules[$module_key]['description'])){
						Coffe_Functions::parseLangInArray($modules[$module_key]['description']);
					}
				}
			}
			$LANG->clearAll();
			$LANG->clearLoadedFiles();
		}
		self::$_description = $modules;
		return self::$_description;
	}


	/**
	 * Зарегестрировать backend - модуль
	 *
	 * @static
	 * @param $parent имя родительского модуля
	 * @param $name название модуля
	 * @param $callback
	 * @throws Coffe_Exception
	 */
	public static function registerBM($parent, $name, $callback)
	{
		$name = trim($name); $callback = trim($callback); $parent = trim($parent);
		$name = strtolower(strtolower($name));
		//если по ошибке был передан абсолютный путь, пробуем преобразовать в относительный
		if (is_string($callback) && strpos($callback, PATH_ROOT) === 0){
			$callback = preg_replace('#' . preg_quote(PATH_ROOT) . '#', '', $callback, 1);
		}
		if (!preg_match('#^[a-z_-]+[0-9_-]*#si', $name)){
			throw new Coffe_Exception('Not incorrect name of the module');
		}
		if (self::isBackendModuleRegistered($name)){
			throw new Coffe_Exception('The backend module ' . htmlspecialchars($name) . ' already registered');
		}

		$function = Coffe_Functions::explodeFunctionPath($callback);

		if (!isset($function['file']) || !is_file(PATH_ROOT . $function['file'])){
			throw new Coffe_Exception('The file of the module isn\'t found: ' . htmlspecialchars($function['file']));
		}
		self::$_backend_modules[$name]['path'] = $function['file'];
		self::$_backend_modules[$name]['parent'] = $parent;
		self::$_backend_modules[$name]['name'] = $name;

		if (isset($function['class'])) self::$_backend_modules[$name]['class'] = $function['class'];
		if (isset($function['function'])) self::$_backend_modules[$name]['function'] = $function['function'];
	}

	/**
	 * Получение всех backend-модулей
	 *
	 * @static
	 * @return array
	 */
	public static function getBackendModules()
	{
		return self::$_backend_modules;
	}

	/**
	 * Проверяет загружен ли backend - модуль
	 *
	 * @static
	 * @param $module
	 * @return bool
	 */
	public static function isBackendModuleRegistered($module)
	{
		$module = trim(strtolower($module));
		return (isset(self::$_backend_modules[$module])) ? true : false;
	}

	/**
	 * Получение backend - модуля
	 *
	 * @static
	 * @param $module
	 * @return null|array
	 */
	public static function getBackendModule($module)
	{
		$module = trim(strtolower($module));
		return (isset(self::$_backend_modules[$module])) ? self::$_backend_modules[$module]: null;
	}

	/**
	 * Получение ссылки на backend - модуль
	 *
	 * @static
	 * @param $module
	 * @param null $params
	 * @return string
	 */
	public static function getBackendModuleUrl($module, $params = null)
	{
		$module = trim(strtolower($module));
		$params['_module_'] = $module;
		return Coffe::getUrlPrefix(). self::$_backend_module_execute . (is_array($params) ? ('?' . http_build_query($params)) : '');
	}

	/**
	 * Добавление элемена административного меню
	 *
	 * @static
	 * @param $id
	 * @param $label
	 * @param $params
	 */
	public static function addMenu($id, $label, $params = array())
	{
		self::$_menu[$id] = array(
			'position' => isset($params['position']) ? $params['position'] : '_top',
			'id' => $id ,
			'label' => $label,
			'sorting' => isset($params['sorting']) ? $params['sorting'] : 0,
		);

		if (isset($params['be_module'])){
			self::$_menu[$id]['href'] = 'javascript:void(0)';
			self::$_menu[$id]['onclick'] = "COFFE_PANEL.goto('" . self::getBackendModuleUrl($params['be_module'], isset($params['be_params']) ? $params['be_params'] : array()) . "')";
		}
		self::$_menu[$id] = array_merge($params, self::$_menu[$id]);
	}

	/**
	 * Получение административного меню
	 *
	 * @static
	 * @return array
	 */
	public static function getMenu()
	{
		return self::$_menu;
	}

	/**
	 * Вывод панели администрирования
	 *
	 * @static
	 * @return string
	 */
	public static function printPanel()
	{
		Coffe_Event::call('beforePanelRender', array(&self::$_menu));
		$template = Coffe::getConfig('adminTemplate','default');
		$file_path = PATH_ROOT . 'coffe/admin/templates/' . $template . '/panel.php';
		if (file_exists($file_path)){
			ob_start();
			require($file_path);
			return ob_get_clean();
		}
		return "The file isn't found : " . htmlspecialchars($file_path);
	}

	/**
	 * Получить расширение таблицы
	 *
	 * @static
	 * @param $table
	 * @return array
	 */
	public static function getTableEditor($table)
	{
		$modules = self::getModulesDescription();
		$table_ext = array();
		foreach ($modules as $module){
			if (isset($module['description']['tableEditor'][$table]) && is_array($module['description']['tableEditor'][$table])){
				$table_ext = self::mergeExtTables($table_ext, $module['description']['tableEditor'][$table]);
			}
		}
		Coffe_Event::call('afterBuildExtTable',array($table, &$table_ext));
		return $table_ext;
	}

	/**
	 * Объединение расширений таблиц
	 *
	 * @static
	 * @param array $table1
	 * @param array $table2
	 * @return array
	 */
	public static function mergeExtTables(array $table1, array $table2)
	{
		//нельзя удалить секцию с описанием readonly - полей
		if (isset($table2['readonly']) && !is_array($table2['readonly'])){
			unset($table2['readonly']);
		}
		if (isset($table2['elements']) && !is_array($table2['elements'])){
			unset($table2['elements']);
		}
		if (isset($table1['readonly']) && is_array($table1['readonly'])){
			foreach ($table1['readonly'] as $element){
				if (isset($table2['elements'][$element])){
					unset($table2['elements'][$element]);
				}
			}
		}
		return array_replace_recursive($table1, $table2);
	}

	/**
	 * TODO: вынести эти схемы в отдельный класс
	 *
	 * Функция сверяет оригинальную схему с текущей и выдает массив колонок, которые
	 * требуют обновления (имееют отличия)
	 *
	 *
	 * @static
	 * @param $scheme
	 * @param $current
	 * @param bool $other требуются прочие обновления базы данных
	 * @return array
	 */
	public static function getSchemeDifference($scheme, $current, &$other = false)
	{
		$columns = array();

		if (is_array($scheme) && is_array($current)){

			if (isset($scheme['columns']) && is_array($scheme['columns'])){

				//нужно обновить все колонки
				if (!isset($current['columns']) || !is_array($current['columns'])){
					return $scheme['columns'];
				}

				foreach($scheme['columns'] as $name => $params){
					if (!isset($current['columns'][$name]) || !is_array($current['columns'][$name])){
						$columns[$name] = $params;
						continue;
					}

					foreach(self::$scheme_column_attributes as $attribute){
						if (isset($params[$attribute])
							&& (!isset($current['columns'][$name][$attribute])
								|| ($current['columns'][$name][$attribute] != $params[$attribute]))
						){
							$columns[$name] = $params;
							continue;
						}
					}
				}
			}
			//прочие различия, не касающиеся колонок
			if (isset($scheme['primary']) && ($scheme['primary'] !== false) && !isset($current['primary'])
				||
				(isset($current['primary']) && isset($scheme['primary']) &&  $scheme['primary'] != $current['primary'])){
				$other = true;
			}
		}
		return $columns;
	}

	/**
	 * Проверка схемы при передаче в адаптер базы данных
	 *
	 * Схема должна быть всегда обработана этой функцией, прежде чем передаваться дальше
	 *
	 * @static
	 * @param $scheme
	 * @param $errors
	 * @param $warnings
	 * @return bool
	 */
	public static function validateScheme(&$scheme, &$errors, &$warnings)
	{
		//работаем только в нижнем регистре
		$scheme = array_change_key_case($scheme, CASE_LOWER);

		if (!isset($scheme['name']) || !is_string($scheme['name'])  || count($scheme['columns']) == 0){
			$errors[] = "The scheme name isn't set";
			return false;
		}

		if (!isset($scheme['columns']) || !is_array($scheme['columns'])){
			$errors[] = "The diagram '" . htmlspecialchars($scheme['name']) . "' doesn't contain columns";
			return false;
		}

		$scheme['columns'] = array_change_key_case($scheme['columns'], CASE_LOWER);

		if (isset($scheme['primary']) && (!is_string($scheme['primary']) || !isset($scheme['columns'][$scheme['primary']])) && !(is_bool($scheme['primary']) && $scheme['primary'] == false)){
			$errors[] = "The primary key not found";
			return false;
		}

		$autoincrement = false;

		foreach ($scheme['columns'] as $name => $params){

			if (!preg_match('#^[a-z][a-z0-9_]+$#',$name)){
				$warnings[] = "The incorrect name of a column `{$name}`, it was deleted";
				unset($scheme['columns'][$name]);
			}

			if (!is_array($params) || !isset($params['type']) || !in_array($params['type'], self::$allowed_scheme_types)){
				$warnings[] = "For the column `{$name}` the type isn't set and it was deleted from the scheme";
				unset($scheme['columns'][$name]);
			}

			if (isset($params['autoincrement']) && $params['autoincrement']){
				if ($autoincrement){
					$warnings[] = "There can be only one auto column and it must be defined as a key.";
					unset($scheme['columns'][$name]['autoincrement']);
				}
				else{
					//задан primary, но этот столбец не autoincrement
					if (isset($scheme['primary']) && ($scheme['primary'] != $name)){
						$errors[] = 'There can be only one auto column and it must be defined as a key';
						break;
					}
					$autoincrement = true;
					$scheme['primary'] = $name;
				}
			}
		}

		if (!isset($scheme['columns']) || !is_array($scheme['columns']) || count($scheme['columns']) == 0){
			$errors[] = "The diagram '" . htmlspecialchars($scheme['name']) . "' doesn't contain columns";
			return false;
		}

		return true;
	}

	/**
	 * Обновляет список модулей в файле конфигурации
	 *
	 * @static
	 * @param $modules
	 * @return bool
	 */
	public static function updateModulesInConfigFile($modules)
	{
		if (!is_file(PATH_CONFIG_FILE) || !is_writable(PATH_CONFIG_FILE)){
			return false;
		}
		$array = @file(PATH_CONFIG_FILE);
		if (!is_array($array)){
			return false;
		}
		$array = array_reverse($array);
		$find = false;
		foreach ($array as $key => $line){
			$line = trim($line);
			$pattern = '/^modules\s*=\s*(.*)$/i';
			if (preg_match($pattern, $line)){
				$array[$key] = preg_replace($pattern, 'modules = ' . implode(', ',$modules), $line) . PHP_EOL;
				$find = true;
				break;
			}
		}
		$array = array_reverse($array);
		if (!$find){
			$array[] = PHP_EOL . ';Updated by Coffe CMS ' . date('d.m.Y H:i:s',time()) . PHP_EOL;
			$array[] = 'modules = ' . implode(', ',$modules) . PHP_EOL;
		}
		return (bool)@file_put_contents(PATH_CONFIG_FILE, $array);
	}

}