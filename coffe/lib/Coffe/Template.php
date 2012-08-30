<?php

/**
 * ������ � ���������
 *
 * @package coffe_cms
 */

class Coffe_Template
{

	/**
	 * ��� �������� �������
	 *
	 * @var null
	 */
	protected $template = null;

	/**
	 * ���� � ����� � ��������� ������������ ROOT
	 *
	 * @var string
	 */
	protected static $dir = 'templates/';

	/**
	 * ���� �����
	 *
	 * @var string
	 */
	protected static $headFile = 'head.php';

	/**
	 * ���� ������������� �������
	 *
	 * @var string
	 */
	protected static $initFile = 'init.php';

	/**
	 * ���� �������� �������
	 *
	 * @var string
	 */
	protected static $descriptionFile = 'description.php';

	/**
	 * �������� ������� ������
	 *
	 * @return null|array
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * ������ ������� ������
	 *
	 * @param $template
	 */
	public function setTemplate($template)
	{
		$this->template = $template;
	}

	/**
	 * ��������� ���������� � ��������� ������������ ROOT
	 *
	 * @return string
	 */
	public static function getTemplatesDir()
	{
		return self::$dir;
	}

	/**
	 * ���������� ���� �������
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
	 * ��������� ������ ��������
	 *
	 * ������� ������ ������ ��������� �������� (���������� ����� � ��������� �� ����� description.php)
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
	 * ��������� ������� �������
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
	 * ��������� url �� ����� � ��������� ��������
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
	 * ��������� ���� �� ����� � ��������� ��������
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
	 * ��������� url �� ����� � ��������� (�������) ��������
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
	 * ��������� ���� �� ����� � ��������� (�������) ��������
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