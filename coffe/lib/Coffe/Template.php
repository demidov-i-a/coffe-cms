<?php

/**
 * Работа с шаблонами
 *
 * @package coffe_cms
 */

class Coffe_Template
{

	/**
	 * Имя текущего шаблона
	 *
	 * @var null
	 */
	protected $template = null;

	/**
	 * Путь к папке с шаблонами относительно ROOT
	 *
	 * @var string
	 */
	protected static $dir = 'templates/';

	/**
	 * Файл шапка
	 *
	 * @var string
	 */
	protected static $headFile = 'head.php';

	/**
	 * Файл инициализации шаблона
	 *
	 * @var string
	 */
	protected static $initFile = 'init.php';

	/**
	 * Файл описания шаблона
	 *
	 * @var string
	 */
	protected static $descriptionFile = 'description.php';

	/**
	 * Получить текущий шаблон
	 *
	 * @return null|array
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Задает текущий шаблон
	 *
	 * @param $template
	 */
	public function setTemplate($template)
	{
		$this->template = $template;
	}

	/**
	 * Получение директории с шаблонами относительно ROOT
	 *
	 * @return string
	 */
	public static function getTemplatesDir()
	{
		return self::$dir;
	}

	/**
	 * Подключает файл шаблона
	 *
	 * @param $file
	 * @return string
	 * @throws Coffe_Exception
	 */
	public function includeFile($file)
	{
		$path = PATH_ROOT . Coffe_Template::$dir . $this->template . '/' . $file;
		if (!file_exists($path) || !is_readable($path)){
			throw new Coffe_Exception("The file " . htmlspecialchars($file) . " isn't found in template `{$this->template}`");
		}
		ob_start();
		require($path);
		return ob_get_clean();
	}

	/**
	 * Получение списка шаблонов
	 *
	 * Функция выдает список доступных шаблонов (физических папок с описанием из файла description.php)
	 *
	 * @return array
	 */
	public static function getSiteTemplates()
	{
		$templates = array();
		$path = PATH_ROOT . self::$dir;
		if(!is_dir($path)){
			throw new Coffe_Exception('The incorrect template directory');
		}
		$dh = opendir($path);
		while (false !== ($dir = readdir($dh))) {
			if (is_dir($path . $dir) && $dir !== '.' && $dir !== '..') {
				if (file_exists(PATH_ROOT . self::$dir . $dir . '/' . self::$descriptionFile)){
					$templates[$dir]['path'] = self::$dir . $dir;
					$templates[$dir]['name'] = $dir;
					$templates[$dir]['description'] = require_once(PATH_ROOT . self::$dir . $dir . '/' . self::$descriptionFile);
				}
			}
		}
		closedir($dh);
		return $templates;
	}

	/**
	 * Проверяет наличие шаблона
	 *
	 * @static
	 * @param $template
	 * @return bool
	 */
	public static function isExist($template)
	{
		return file_exists(PATH_ROOT . self::$dir . $template . '/' . self::$descriptionFile);
	}

	/**
	 * Получение url до папки с указанным шаблоном
	 *
	 * @static
	 * @param $template
	 * @return string
	 */
	public static function getUrl($template)
	{
		return Coffe::getUrlPrefix() . self::$dir . $template . '/';
	}

	/**
	 * Получение пути до папки с указанным шаблоном
	 *
	 * @static
	 * @param $template
	 * @return string
	 */
	public static function getPath($template)
	{
		return PATH_ROOT . self::$dir . $template . '/';
	}

	/**
	 * Получение url до папки с указанным (текущим) шаблоном
	 *
	 * @param null $template
	 * @return string
	 */
	public function url($template = null)
	{
		if (empty($template)) $template = $this->template;
		return Coffe::getUrlPrefix() . self::$dir . $template . '/';
	}

	/**
	 * Получение пути до папки с указанным (текущим) шаблоном
	 *
	 * @param null $template
	 * @return string
	 */
	public function path($template = null)
	{
		if (empty($template)) $template = $this->template;
		return PATH_ROOT . self::$dir . $template . '/';
	}

}