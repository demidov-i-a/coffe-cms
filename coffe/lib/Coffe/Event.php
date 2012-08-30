<?php

/**
 * Диспетчер событий
 *
 * @package coffe_cms
 */
class Coffe_Event
{

	/**
	 * Массив событий
	 *
	 * @var array
	 */
	protected static $events = array();

	/**
	 * Добавление обработчика события
	 *
	 * @static
	 * @param $event название события
	 * @param $func обработчик (валидный callback)
	 * @return bool
	 */
	public static function register($event, $func)
	{
		if (is_array($event)){
			foreach ($event as $e){
				self::$events[trim($e)][] = $func;
			}
		}
		elseif (is_string($event)){
			self::$events[trim($event)][] = $func;
		}
	}

	/**
	 * Получение событий
	 *
	 * @static
	 * @param null $event
	 * @return array|null
	 */
	public static function get($event = null)
	{
		return ($event === null) ? (self::$events) : (self::isRegister($event) ? self::$events[$event] : null);
	}


	/**
	 * Вызывает событие полученное ранее функцией get
	 *
	 * @static
	 * @param $func
	 * @param array $args
	 * @return mixed
	 */
	public static function callEventDirect($func, $args = array())
	{
		return call_user_func_array($func, $args);
	}

	/**
	 * Выполенение события
	 *
	 * @static
	 * @param $event название события
	 * @param array $args
	 * @return array
	 */
	public static function call($event, $args = array())
	{
		$event = trim($event);
		$result = array();
		if(isset(self::$events[$event])){
			foreach(self::$events[$event] as $func){
				$result[] = call_user_func_array($func, $args);
			}
		}
		return $result;
	}

	/**
	 * Выполняет последнее событие из списка событий
	 *
	 * @static
	 * @param $event
	 * @param array $args
	 * @return null
	 */
	public static function callLast($event, $args = array())
	{
		$event = trim($event);
		if(isset(self::$events[$event])){
			$func = end(self::$events[$event]);
			return call_user_func_array($func, $args);
		}
		return null;
	}

	/**
	 * Выполняет последнее событие из списка событий
	 *
	 * @static
	 * @param $event
	 * @param array $args
	 * @return null
	 */
	public static function callFirst($event, $args = array())
	{
		$event = trim($event);
		if(isset(self::$events[$event])){
			$func = reset(self::$events[$event]);
			return call_user_func_array($func, $args);
		}
		return null;
	}

	/**
	 * Выполняет проверку, существует ли обработчик данного события
	 *
	 * @static
	 * @param $event название события
	 * @return bool
	 */
	public static function isRegister($event)
	{
		return (isset(self::$events[$event]) && count(self::$events[$event]) > 0);
	}

	/**
	 * Очистка обработчиков события
	 *
	 * @static
	 * @param $event название события
	 */
	public static function clear($event)
	{
		if (isset(self::$events[$event])) unset(self::$events[$event]);
	}

}