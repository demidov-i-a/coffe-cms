<?php
if (defined('COOFE_INIT_INCLUDED')) return;

//определяем реальную корневую директорию
$root_path = realpath(dirname(__FILE__).'/../../');

//проверям наличие слеша на конце, если нет - добавляем
$root_path .= (strrpos($root_path, DIRECTORY_SEPARATOR) != strlen($root_path) - 1) ? DIRECTORY_SEPARATOR : '';

//корневая директория
define('PATH_ROOT', $root_path);

//корневая директория папки cms
define('PATH_COFFE', PATH_ROOT . 'coffe/');

//путь к файлу конфигурации
define('PATH_CONFIG_FILE', PATH_COFFE . 'settings/config.ini');

//путь к библиотекам
define('PATH_LIB', PATH_ROOT . 'coffe/lib/');

//путь к компонентам
define('PATH_CP', PATH_ROOT . 'coffe/components/');

set_include_path(PATH_LIB);

//подключаем автозагрузчик
require 'Coffe/AutoLoader.php';

Coffe_AutoLoader::addPath(PATH_LIB);


require_once(PATH_LIB . 'Coffe/INI.php');

$CINI = new Coffe_INI();
$GLOBALS['CFA'] = $CINI->readFile(PATH_CONFIG_FILE)->getIni('default');

if (!is_array($GLOBALS['CFA'])){
	die('Error of parsing of the configuration file');
}

//включая сообщения об ошибках
require_once(PATH_ROOT . 'coffe/includes/init.errors.inc.php');

//избавляемся от магических кавычек
require_once(PATH_ROOT . 'coffe/includes/strip_quotes.inc.php');

if (!is_array($GLOBALS['CFA']['db'])){
	die('The database configuration isn\'t found');
}

if (!isset($GLOBALS['CFA']['db']['adapter'])) {
	die('The adapter of a database isn\'t set');
}
if (!class_exists($GLOBALS['CFA']['db']['adapter'])){
	die('The database adapter ' . $GLOBALS['CFA']['db']['adapter'] . ' isn\'t found');
}

$GLOBALS['COFFE_DB'] = new $GLOBALS['CFA']['db']['adapter']($GLOBALS['CFA']['db']);

$GLOBALS['LANG'] = new Coffe_Translate();

define("COOFE_INIT_INCLUDED", true);