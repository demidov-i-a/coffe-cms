<?php

/**
 * Абстрактный адаптер базы данных
 *
 * @package coffe_cms
 */

abstract class Coffe_DB_Abstract
{

	/**
	 * Соединение с базой данных
	 *
	 * @var object|resource|null
	 */
	protected $_connection = null;

	/**
	 * Конфигурация
	 *
	 * @var array
	 */
	protected $_config = array();


	/**
	 * Включена отладка
	 *
	 * @var bool
	 */
	protected $_debug_on = false;

	/**
	 * Массив для сбора запросов при debug_on = true
	 *
	 * @var array
	 */
	protected $_queries = array();

	public function __construct($config)
	{
		if (is_array($config)) {
			$this->_config = $config;
		}
	}

	/**
	 * Получение конфигурации
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return $this->_config;
	}

	/** Получение соединения
	 *
	 * @return null|object|resource
	 */
	public function getConnection()
	{
		$this->_connect();
		return $this->_connection;
	}


	/**
	 * Соединение с базой данных
	 *
	 * @return bool
	 */
	abstract protected function _connect();

	/**
	 * Проверяет активность соединения
	 *
	 * @return boolean
	 */
	abstract public function isConnected();


	/**
	 * Закрывает соединение
	 *
	 * @return bool
	 */
	abstract public function closeConnection();

	/**
	 * Выбор базы данных
	 *
	 * @abstract
	 * @param $db_name
	 *
	 * @return bool
	 */
	abstract public function selectDB($db_name);

	/**
	 * Экранирование значения перед вставкой в базу данных
	 *
	 * @abstract
	 * @param $value
	 * @param null $type
	 *
	 * @return string
	 */
	abstract public function escapeString($value, $type = null);


	/**
	 * Полное экранирование строки
	 *
	 * @param $value
	 * @param null $type
	 * @return string
	 */
	public function fullEscapeString($value, $type = null)
	{
		return '\'' .$this->escapeString($value, $type)  . '\'';
	}

	/**
	 * Включение/отключение режима отладки
	 */
	public function debug($on)
	{
		$this->_debug_on = (bool)$on;
	}

	/**
	 * Возвращает флаг отладки
	 *
	 * @return bool
	 */
	public function isDebug()
	{
		return $this->_debug_on;
	}

	/**
	 * При отладке возвращает массив запросов
	 *
	 * @return array
	 */
	public function getQueries()
	{
		return $this->_queries;
	}

	/**
	 * Выполнение запроса на выборку данных
	 *
	 * @abstract
	 * @param $fields
	 * @param $table
	 * @param $where
	 * @param string $groupBy
	 * @param string $orderBy
	 * @param string $limit
	 *
	 * @return resource|bool
	 */
	abstract public function select($fields, $table, $where = '', $groupBy = '', $orderBy = '', $limit = '');

	/**
	 * Вставка данных
	 *
	 * @abstract
	 * @param $table
	 * @param array $bind
	 *
	 * @return bool|int
	 */
	abstract public function insert($table, array $bind);

	/**
	 * Обновление данных
	 *
	 * @abstract
	 * @param $table
	 * @param array $bind
	 * @param string $where
	 *
	 * @return bool
	 */
	abstract public function update($table, array $bind, $where = '');

	/**
	 * Удаление данных
	 *
	 * @abstract
	 * @param $table
	 * @param string $where
	 *
	 * @return bool
	 */
	abstract public function delete($table, $where = '');

	/**
	 * Выполняет произвольный запрос
	 *
	 * @abstract
	 * @param $sql
	 *
	 * @return resource|bool
	 */
	abstract public function query($sql);

	/**
	 * Получение одной строки данных
	 *
	 * @abstract
	 * @param $res
	 * @param bool $assoc
	 *
	 * @return bool|array
	 */
	abstract function fetch($res, $assoc = true);

	/**
	 * Получение всех строк данных
	 *
	 * @abstract
	 * @param $res
	 * @param bool $assoc
	 *
	 * @return bool|array
	 */
	abstract function fetchAll($res, $assoc = true);


	/**
	 * Получение id последней вставленной записи
	 *
	 * @param string $tableName   OPTIONAL Name of table.
	 * @param string $primaryKey  OPTIONAL Name of primary key column.
	 * @return string
	 */
	abstract public function lastInsertId($tableName = null, $primaryKey = null);

	/**
	 * Получение последней ошибки
	 *
	 * @abstract
	 * @return string|bool
	 */
	abstract public function lastError();


	/**
	 * Установка схемы (таблицы) базы данных
	 *
	 * @abstract
	 * @param $scheme
	 * @param $errors
	 * @param $warnings
	 * @return mixed
	 */
	abstract public function installScheme($scheme, &$errors, &$warnings);


	/**
	 * Удаление схемы (таблицы) базы данных
	 *
	 * @abstract
	 * @param $scheme
	 * @param $errors
	 * @param $warnings
	 * @return mixed
	 */
	abstract public function uninstallScheme($scheme, &$errors, &$warnings);



	/**
	 * Получение описания таблицы в формате cms
	 *
	 * @abstract
	 * @param $scheme
	 * @return mixed
	 */
	abstract public function getScheme($scheme);




}