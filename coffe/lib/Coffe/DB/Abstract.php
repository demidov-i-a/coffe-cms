<?php

/**
 * ����������� ������� ���� ������
 *
 * @package coffe_cms
 */

abstract class Coffe_DB_Abstract
{

	/**
	 * ���������� � ����� ������
	 *
	 * @var object|resource|null
	 */
	protected $_connection = null;

	/**
	 * ������������
	 *
	 * @var array
	 */
	protected $_config = array();


	/**
	 * �������� �������
	 *
	 * @var bool
	 */
	protected $_debug_on = false;

	/**
	 * ������ ��� ����� �������� ��� debug_on = true
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
	 * ��������� ������������
	 *
	 * @return array
	 */
	public function getConfig()
	{
		return $this->_config;
	}

	/** ��������� ����������
	 *
	 * @return null|object|resource
	 */
	public function getConnection()
	{
		$this->_connect();
		return $this->_connection;
	}


	/**
	 * ���������� � ����� ������
	 *
	 * @return bool
	 */
	abstract protected function _connect();

	/**
	 * ��������� ���������� ����������
	 *
	 * @return boolean
	 */
	abstract public function isConnected();


	/**
	 * ��������� ����������
	 *
	 * @return bool
	 */
	abstract public function closeConnection();

	/**
	 * ����� ���� ������
	 *
	 * @abstract
	 * @param $db_name
	 *
	 * @return bool
	 */
	abstract public function selectDB($db_name);

	/**
	 * ������������� �������� ����� �������� � ���� ������
	 *
	 * @abstract
	 * @param $value
	 * @param null $type
	 *
	 * @return string
	 */
	abstract public function escapeString($value, $type = null);


	/**
	 * ������ ������������� ������
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
	 * ���������/���������� ������ �������
	 */
	public function debug($on)
	{
		$this->_debug_on = (bool)$on;
	}

	/**
	 * ���������� ���� �������
	 *
	 * @return bool
	 */
	public function isDebug()
	{
		return $this->_debug_on;
	}

	/**
	 * ��� ������� ���������� ������ ��������
	 *
	 * @return array
	 */
	public function getQueries()
	{
		return $this->_queries;
	}

	/**
	 * ���������� ������� �� ������� ������
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
	 * ������� ������
	 *
	 * @abstract
	 * @param $table
	 * @param array $bind
	 *
	 * @return bool|int
	 */
	abstract public function insert($table, array $bind);

	/**
	 * ���������� ������
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
	 * �������� ������
	 *
	 * @abstract
	 * @param $table
	 * @param string $where
	 *
	 * @return bool
	 */
	abstract public function delete($table, $where = '');

	/**
	 * ��������� ������������ ������
	 *
	 * @abstract
	 * @param $sql
	 *
	 * @return resource|bool
	 */
	abstract public function query($sql);

	/**
	 * ��������� ����� ������ ������
	 *
	 * @abstract
	 * @param $res
	 * @param bool $assoc
	 *
	 * @return bool|array
	 */
	abstract function fetch($res, $assoc = true);

	/**
	 * ��������� ���� ����� ������
	 *
	 * @abstract
	 * @param $res
	 * @param bool $assoc
	 *
	 * @return bool|array
	 */
	abstract function fetchAll($res, $assoc = true);


	/**
	 * ��������� id ��������� ����������� ������
	 *
	 * @param string $tableName   OPTIONAL Name of table.
	 * @param string $primaryKey  OPTIONAL Name of primary key column.
	 * @return string
	 */
	abstract public function lastInsertId($tableName = null, $primaryKey = null);

	/**
	 * ��������� ��������� ������
	 *
	 * @abstract
	 * @return string|bool
	 */
	abstract public function lastError();


	/**
	 * ��������� ����� (�������) ���� ������
	 *
	 * @abstract
	 * @param $scheme
	 * @param $errors
	 * @param $warnings
	 * @return mixed
	 */
	abstract public function installScheme($scheme, &$errors, &$warnings);


	/**
	 * �������� ����� (�������) ���� ������
	 *
	 * @abstract
	 * @param $scheme
	 * @param $errors
	 * @param $warnings
	 * @return mixed
	 */
	abstract public function uninstallScheme($scheme, &$errors, &$warnings);



	/**
	 * ��������� �������� ������� � ������� cms
	 *
	 * @abstract
	 * @param $scheme
	 * @return mixed
	 */
	abstract public function getScheme($scheme);




}