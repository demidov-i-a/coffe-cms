<?php

/**
 * �������������
 *
 * @package coffe_cms
 *
 */

class Coffe_AutoLoader
{

	/**
	 * ���� �� ������� ����� �������� �����
	 *
	 * @var array
	 */
	protected static $paths = array();

	/**
	 * ���������� ������ ���� � ������
	 *
	 * @static
	 * @param $path
	 */
	public static function addPath($path)
	{
		if (is_dir($path)){
			self::$paths[] = $path;
		}
	}

	/**
	 * �������� ������
	 *
	 * @static
	 * @param $class
	 * @return bool
	 */
	public static function load($class)
	{
		$classPath = str_replace('_', DIRECTORY_SEPARATOR, $class);
		$classPath .= '.php';
		foreach (self::$paths as $path) {
			if (is_file($path . $classPath)) {
				require_once $path . $classPath;
				return true;
			}
		}
	}
}

spl_autoload_register(array('Coffe_AutoLoader', 'load'));