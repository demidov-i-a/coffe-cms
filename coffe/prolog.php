<?php

/**
 * Точка входа
 *
 * @package coffe_cms
 */
require_once(dirname(__FILE__) . '/includes/init.inc.php');

$test = array(
	'test' => 'LANG:MODULE:main/lang.xml;menu_mod_manager'

);


try
{
	//Режим fronted
	define('COFFE_MODE','FE');
	Coffe::run();

}
//обработка исключений
catch(Coffe_DB_Exception $e)
{
	ob_end_clean();
	$error = 'Coffe DB: ' . $e->getMessage();
	require_once(PATH_ROOT . 'coffe/includes/exception.inc.php');
}
catch(Coffe_Exception $e)
{
	ob_end_clean();
	$error = 'Coffe: ' . $e->getMessage();
	require_once(PATH_ROOT . 'coffe/includes/exception.inc.php');
}
catch(Exception $e)
{
	ob_end_clean();
	$error = $e->getMessage();
	require_once(PATH_ROOT . 'coffe/includes/exception.inc.php');
}


