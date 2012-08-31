<?php
/**
 * ������� SelectDB ��� TableEditor
 *
 * @package coffe_cms
 */
class Coffe_TableEditor_Element_SelectDB extends Coffe_TableEditor_Element_Select
{

	/**
	 * ��� �������
	 *
	 * @var null|string
	 */
	protected $_table = null;

	/**
	 * primary-key �������
	 *
	 * @var null|string
	 */
	protected $_primary = null;

	/**
	 * �������������� ������� ��� ��������� �������
	 *
	 * @var string
	 */
	protected $_sql_where = '';

	/**
	 * ���������� �������
	 *
	 * @var string
	 */
	protected $_sql_sorting = '';


	/**
	 * ����� ���� �������� �� ����
	 *
	 * @var string
	 */
	protected $_fields = '';


	/**
	 * �������� ������� � ���������� �������
	 *
	 * @var null
	 */
	protected $_title = null;

	/**
	 * �������� ������ �� ���������
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
	 * ��������� ��������� ������
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
	 * ��������� ������ ����� ��� ������� �� ����
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
	 * ������ ��� �������
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
	 * �������� ��� �������
	 *
	 * @return null|string
	 */
	public function getTable()
	{
		return $this->_table;
	}

	/**
	 * ��������� primary-key �������
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
	 * ��������� primary-key �������
	 *
	 * @return null|string
	 */
	public function getPrimary()
	{
		return $this->_primary;
	}

	/**
	 * ������ ���������
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
	 * �������� ����������
	 *
	 * @return string
	 */
	public function getSqlSorting()
	{
		return $this->_sql_sorting;
	}

	/**
	 * ��������� �������
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
	 * ��������� �������
	 *
	 * @return string
	 */
	public function getSqlWhere()
	{
		return $this->_sql_where;
	}


	/**
	 * ��������� �����
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
	 * ��������� �����
	 *
	 * @return string
	 */
	public function getFields()
	{
		return $this->_fields;
	}


	/**
	 * ������ ���� ���������
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
	 * �������� ���� � ����������
	 *
	 * @return null|string
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * ��������� ��������� �� ���������
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
	 * ��������� ��������� �� ���������
	 *
	 * @return array
	 */
	public function getDefaultOptions()
	{
		return $this->_default_options;
	}

}