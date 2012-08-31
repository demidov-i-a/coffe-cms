<?php
/**
 * Элемент SelectDB для TableEditor
 *
 * @package coffe_cms
 */
class Coffe_TableEditor_Element_SelectDB extends Coffe_TableEditor_Element_Select
{

	/**
	 * Имя таблицы
	 *
	 * @var null|string
	 */
	protected $_table = null;

	/**
	 * primary-key таблицы
	 *
	 * @var null|string
	 */
	protected $_primary = null;

	/**
	 * Дополнительное условие при получении записей
	 *
	 * @var string
	 */
	protected $_sql_where = '';

	/**
	 * Сортировка записей
	 *
	 * @var string
	 */
	protected $_sql_sorting = '';


	/**
	 * Какие поля получать из базы
	 *
	 * @var string
	 */
	protected $_fields = '';


	/**
	 * Название колонки с заголовком таблицы
	 *
	 * @var null
	 */
	protected $_title = null;

	/**
	 * Элементы списка по умолчанию
	 *
	 * @var array
	 */
	protected $_default_options = array();



	/**
	 * @var Coffe_DB_Abstract
	 */
	protected $db = null;

	public function __construct($name, $config = null)
	{
		parent::__construct($name, $config);
		if (empty($this->_table)){
			throw new Coffe_Exception('The table name is not set');
		}
		if (empty($this->_primary)){
			throw new Coffe_Exception('The primary column is not set');
		}
		if (empty($this->_title)){
			throw new Coffe_Exception('The title column is not set');
		}
		$this->db = $GLOBALS['COFFE_DB'];
	}


	/**
	 * Получение элементов списка
	 *
	 * @return array
	 */
	public function getOptions()
	{
		$options = $this->getDefaultOptions();
		$res = $this->db->select($this->getTotalFields(), $this->getTable(), $this->getSqlWhere(), '', $this->getSqlSorting());
		if ($rows = $this->db->fetchAll($res)){
			foreach ($rows as $row){
				$options[$row[$this->getPrimary()]] = $row[$this->getTitle()];
			}
		}
		$this->options = $options;
		return $this->options;
	}

	/**
	 * Получение списка полей для выборки из базы
	 *
	 * @return string
	 */
	protected function getTotalFields()
	{
		$fields = $this->getFields();
		if (trim($fields)) return $fields;
		$columns = array();
		$columns[] = $this->getPrimary();
		$columns[] = $this->getTitle();
		return implode(',', $columns);
	}

	/**
	 * Задать имя таблицы
	 *
	 * @param $table
	 * @return Coffe_TableEditor_Element_SelectDB
	 */
	public function setTable($table)
	{
		$this->_table = (string)$table;
		return $this;
	}

	/**
	 * Получить имя таблицы
	 *
	 * @return null|string
	 */
	public function getTable()
	{
		return $this->_table;
	}

	/**
	 * Установка primary-key таблицы
	 *
	 * @param $primary
	 * @return Coffe_TableEditor_Element_SelectDB
	 */
	public function setPrimary($primary)
	{
		$this->_primary = (string)$primary;
		return $this;
	}


	/**
	 * Получение primary-key таблицы
	 *
	 * @return null|string
	 */
	public function getPrimary()
	{
		return $this->_primary;
	}

	/**
	 * Задать сортироку
	 *
	 * @param $sql
	 * @return Coffe_TableEditor_Element_SelectDB
	 */
	public function setSqlSorting($sql)
	{
		$this->_sql_sorting = (string)$sql;
		return $this;
	}

	/**
	 * Получить сортировку
	 *
	 * @return string
	 */
	public function getSqlSorting()
	{
		return $this->_sql_sorting;
	}

	/**
	 * Установка условия
	 *
	 * @param $sql
	 * @return Coffe_TableEditor_Element_SelectDB
	 */
	public function setSqlWhere($sql)
	{
		$this->_sql_where= (string)$sql;
		return $this;
	}

	/**
	 * Получение условия
	 *
	 * @return string
	 */
	public function getSqlWhere()
	{
		return $this->_sql_where;
	}


	/**
	 * Установка полей
	 *
	 * @param $fields
	 * @return Coffe_TableEditor_Element_SelectDB
	 */
	public function setFields($fields)
	{
		$this->_fields = (string)$fields;
		return $this;
	}

	/**
	 * Получение полей
	 *
	 * @return string
	 */
	public function getFields()
	{
		return $this->_fields;
	}


	/**
	 * Задать поле заголовка
	 *
	 * @param $title
	 * @return Coffe_TableEditor_Element_SelectDB
	 */
	public function setTitle($title)
	{
		$this->_title = (string)$title;
		return $this;
	}

	/**
	 * Получить поле с заголовком
	 *
	 * @return null|string
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * Установка элементов по умолчанию
	 *
	 * @param $options
	 * @return Coffe_TableEditor_Element_SelectDB
	 */
	public function setDefaultOptions($options)
	{
		$this->_default_options = (array)$options;
		return $this;
	}

	/**
	 * Получение элементов по умолчанию
	 *
	 * @return array
	 */
	public function getDefaultOptions()
	{
		return $this->_default_options;
	}

}