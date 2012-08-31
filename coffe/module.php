<?php

/**
 * Запуск backend - модуля
 *
 * @package coffe_cms
 */
require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes/init.inc.php');

define('COFFE_MODE','BE');

try{
	//инициализация пользователя
	Coffe::initUser();

	Coffe::setUrlPrefix(Coffe_Func::getAbsPrefixUrl());

	//подключаем модули
	Coffe_ModuleManager::initModules();

	$components = Coffe_CpManager::getAllComponents();

	$module = isset($_GET['_module_']) ? trim($_GET['_module_']) : null;

	if (empty($module) || !Coffe_ModuleManager::isBackendModuleRegistered($module)){
		throw new Coffe_Exception('The module ' . htmlspecialchars($module) . ' isn\'t found');
	}

	$module = Coffe_ModuleManager::getBackendModule($module);

	if (!is_readable(PATH_ROOT . $module['path'])){
		throw new Coffe_Exception('The file ' . htmlspecialchars($module['path']) . ' isn\'t found');
	}

	require_once(PATH_ROOT . $module['path']);

	//модуль - это класс
	if (isset($module['class'])){
		$CModule = new $module['class']();
		//функция в классе
		if (isset($module['function'])){
			if (method_exists($CModule , $module['function'])){
				call_user_func(array($CModule , $module['function']));
			}
			else{
				throw new Coffe_Exception('The function ' . $module['function'] . ' in class ' . $module['class']. ' isn\'t found');
			}
		}
	}
	else{
		//функция в файле
		if (isset($module['function'])){
			if (function_exists($module['function'])){
				call_user_func($module['function']);
			}
			else{
				throw new Coffe_Exception('The function ' . $module['function'] . ' isn\'t found');
			}
		}
	}
}
catch(Coffe_Exception $e){
	die('Coffe: '. $e->getMessage());
}