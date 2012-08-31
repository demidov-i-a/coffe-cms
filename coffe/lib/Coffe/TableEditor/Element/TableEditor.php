<?php

/**
 * ������� TableEditor ��� TableEditor
 *
 * @package coffe_cms
 */
class Coffe_TableEditor_Element_TableEditor extends Coffe_TableEditor_Element_Abstract
{

	/**
	 * @var Coffe_TableEditor
	 */
	protected $_form = null;


	/**
	 * ���� � ����� � �������������
	 *
	 * @var null
	 */
	protected $_file_path = null;

	/**
	 * �����������
	 *
	 * ������������� ����� � ������������ ��������
	 *
	 * @param $name
	 * @param null $config
	 *
	 */
	public function __construct($name, $config = null)
	{
		parent::__construct($name, $config);
		$this->_form = new Coffe_TableEditor($name);
		if ($this->_file_path !== null){
			$file_path = Coffe_Func::makePath($this->_file_path);
			if (!file_exists($file_path)){
				throw new Coffe_Exception('The file not found: ' . htmlspecialchars($file_path));
			}
			$lv_config = require($file_path);
			if (!is_array($lv_config)){
				throw new Coffe_Exception('The file didn\'t return the array: ' . htmlspecialchars($file_path));
			}
			Coffe_Func::parseLangInArray($lv_config);
			$this->_form->build($lv_config);
		}
		elseif (isset($config['tableEditor'])){
			$this->_form->build($config['tableEditor']);
		}
	}

	/**
	 * ����� ��������
	 *
	 * @return string
	 */
	public function render()
	{
		return $this->_form->renderElements();
	}

	/**
	 * ��������� ��������
	 *
	 * @param $value
	 * @param null $data
	 * @return Coffe_TableEditor_Element_Abstract
	 */
	public function setValue($value, &$data = null)
	{
		$this->_value = $value;
		$this->_form->populate($this->_value);
		return $this;
	}

	/**
	 * ��������� �������� �� ����
	 *
	 * @param $value
	 * @param null $data
	 * @return Coffe_TableEditor_Element_Abstract
	 */
	public function setValueFromDB($value, &$data = null)
	{
		if (is_string($value) && trim($value)){
			$this->_value = @unserialize($value);
		}
		$this->_form->populate($this->_value, true);
		return $this;
	}

	/**
	 * ��������� ����� ����������� � ����
	 *
	 * @param null $value
	 * @param null $data
	 * @return mixed|null
	 */
	public function prepareForDB($value = null, &$data = null)
	{
		if ($value === null){
			$value = $this->getValue();
		}
		return serialize($value);
	}

	/**
	 * �������� ���������� ������
	 *
	 * @param $value
	 * @param null $data
	 * @return bool
	 */
	public function isValid($value, &$data = null)
	{
		return $this->_form->isValid($value);
	}

	/**
	 * ������ ��� ��������
	 *
	 * @param $name
	 */
	public function setName($name)
	{
		$this->_name = $name;
		$this->_form->setName($this->getFullName());
	}

	/**
	 * ��������� ��������
	 *
	 * @param $parent
	 * @return Coffe_TableEditor_Element_TableEditor
	 */
	public function setParent($parent)
	{
		$this->_parent = $parent;
		$this->_form->setName($this->getFullName());
		return $this;
	}


	/**
	 * ���������� ���� � �������������
	 *
	 * @param $path
	 * @return Coffe_TableEditor_Element_TableEditor
	 */
	public function setFilePath($path)
	{
		$this->_file_path = $path;
		return $this;

	}

	/**
	 * �������� ���� � �������������
	 *
	 * @return null
	 */
	public function getFilePath()
	{
		return $this->_file_path;
	}


}