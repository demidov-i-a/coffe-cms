<?php

/**
 * FE - функции
 *
 * @package coffe_cms
 */
class Coffe_FE
{

	/**
	 * Загрузка конфигурации для страницы в массив
	 *
	 * @static
	 * @param $uid
	 * @param $array
	 */
	public static function loadPageConfig($uid, &$array)
	{
		$CPage = Coffe_Page::getInstance();
		$branch = $CPage->getBranchUp($uid, '');
		$branch = array_reverse($branch);
		self::mergePageConfig($branch, $array);
	}


	/**
	 * Формирует массив конфигурации на основе ветки страниц
	 *
	 * @static
	 * @param $branch
	 * @param $total
	 * @return array
	 */
	public static function mergePageConfig($branch, &$total)
	{
		$config = self::getBranchConfigArray($branch);
		$max_level = count($config);
		foreach ($config as $level => $params){
			$last = (count($config) == $level + 1);
			foreach ($params as $name => $value){
				$param_level = self::getParamLevel($name);
				if ((($param_level !== 0) && ($param_level <= $max_level)) ||
					(($param_level === 0) && $last)){
					self::addValueToArray($total, ltrim($name, '@'), $value);
				}
			}
		}

		return  $total;
	}

	/**
	 * Отдает массивы конфигурации на основе ветки страниц
	 *
	 * @static
	 * @param $branch
	 * @return array
	 */
	private static function getBranchConfigArray($branch)
	{
		$CIni = new Coffe_INI();
		$config = array();
		foreach ($branch as $page){
			if (trim($page['config'])){
				$config[] = $CIni->readString($page['config'])->getIni();
				$CIni->clearIni();
			}
			else{
				$config[] = array();
			}
		}
		return $config;
	}

	/**
	 * добавляет значение к массиву
	 *
	 * @static
	 * @param $total
	 * @param $key
	 * @param $value
	 */
	public static function addValueToArray(&$total, $key, $value)
	{
		if (isset($total[$key])){
			$total = array_replace_recursive($total, array($key => $value));
		}
		else{
			$total[$key] = $value;
		}
	}

	/**
	 * Возвращает уровень параметра
	 *
	 * @static
	 * @param $name
	 * @return int
	 */
	private static function getParamLevel($name)
	{
		if (preg_match('#^(@+)#', $name, $out)){
			return strlen($out[1]);
		}
		return 0;
	}



}