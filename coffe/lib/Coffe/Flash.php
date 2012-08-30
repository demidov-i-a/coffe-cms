<?php

/**
 * Работа с сообщениями
 *
 * @package coffe_cms
 */
class Coffe_Flash
{

	/**
	 * Ключ в массиве $_SESSION для хранения сообщений
	 *
	 * @var string
	 */
	protected $session_key = 'coffe_flash';


	public function __construct()
	{
	
		Coffe::startSession();
		
		if (!isset($_SESSION[$this->session_key])){
			$_SESSION[$this->session_key] = array();
		}
	}

	/**
	 * Установка ключа в массиве $_SESSION
	 *
	 * @param $key
	 * @return Coffe_Flash
	 */
	public function setSessionKey($key)
	{
		$this->session_key = (string)$key;
		return $this;
	}

	/**
	 * Получение ключа в массиве $_SESSION
	 *
	 * @return string
	 */
	public function getSessionKey()
	{
		return $this->session_key;
	}

	/**
	 * Добавление сообщения
	 *
	 * @param $type
	 * @param $message
	 * @return Coffe_Flash
	 */
	public function push($type, $message)
	{
		if (is_array($message)){
			foreach ($message as $mes){
				$_SESSION[$this->session_key][$type][] = $mes;
			}
		}
		elseif(is_string($message)){
			$_SESSION[$this->session_key][$type][] = $message;
		}
		return $this;
	}

	/**
	 * Добавление ошибки
	 *
	 * @param $message
	 * @return Coffe_Flash
	 */
	public function pushError($message)
	{
		$this->push('error',$message);
		return $this;
	}

	/**
	 * Добавление информации
	 *
	 * @param $message
	 * @return Coffe_Flash
	 */
	public function pushInfo($message)
	{
		$this->push('info',$message);
		return $this;
	}

	/**
	 * Добавление успешной информации
	 *
	 * @param $message
	 * @return Coffe_Flash
	 */
	public function pushSuccess($message)
	{
		$this->push('success',$message);
		return $this;
	}

	/**
	 * Получить все сообщения
	 *
	 * @return null|array
	 */
	public function getAll()
	{
		return (isset($_SESSION[$this->session_key])) ? $_SESSION[$this->session_key] : null;
	}

	/**
	 * Получить сообщения по типу
	 *
	 * @param $type
	 * @return null|array
	 */
	public function getByType($type)
	{
		return (isset($_SESSION[$this->session_key][$type])) ? $_SESSION[$this->session_key][$type] : null;
	}

	/**
	 * Очистка всех сообщений
	 *
	 * @return Coffe_Flash
	 */
	public function clearAll()
	{
		$_SESSION[$this->session_key] = array();
		return $this;
	}

	/**
	 * Очистка по типу
	 *
	 * @param $type
	 * @return Coffe_Flash
	 */
	public function clearByType($type)
	{
		$_SESSION[$this->session_key][$type] = array();
		return $this;
	}

	/**
	 * Получение количества сообщений
	 *
	 * @param null $type
	 * @return int
	 */
	public function count($type = null)
	{
		return ($type !== null)
			? ((isset($_SESSION[$this->session_key][$type])) ? count($_SESSION[$this->session_key][$type]) : 0)
			: ((isset($_SESSION[$this->session_key])) ? count($_SESSION[$this->session_key]) : 0);
	}


}