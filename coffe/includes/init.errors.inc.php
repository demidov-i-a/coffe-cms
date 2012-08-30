<?php

/**
 * Управление выводом ошибок
 */

ini_set('display_errors','On');

switch($GLOBALS['CFA']['display_errors'])
{
	//отладка, показываем все
	case -1: error_reporting(E_ALL | E_STRICT) ; break;

	//нормальный режим, не показываем ошибок
	case 0:  ini_set('display_errors','Off'); error_reporting(0); break;

	//показывать только фатальные ошибки
	case 1: error_reporting(E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_PARSE |E_STRICT) ; break;

	//показывать фатальные ошибки и предупреждения
	case 2: error_reporting(E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_PARSE | E_WARNING | E_CORE_WARNING | E_USER_WARNING | E_STRICT ) ; break;

}

