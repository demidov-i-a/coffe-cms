<?php

/**
 * ����������� ������� ��� LiveForm
 *
 * @package coffe_cms
 *
 */
abstract class Coffe_LiveForm_Element_Abstract
{

	/**
	 * ��� ��������
	 *
	 * @var null
	 */
	protected $_name = null;

	/**
	 * ��� ������������� ��������
	 *
	 * @var null
	 */
	protected $_parent = null;

	/**
	 * ��������� ��������
	 *
	 * @var null
	 */
	protected $_label = null;

	/**
	 * �������� ��������
	 *
	 * @var null
	 */
	protected $_value = null;

	/**
	 * ����� ��������
	 *
	 * @var string
	 */
	protected $_class = '';

	/**
	 * ������������
	 *
	 * @var null
	 */
	protected $_config = null;

	/**
	 * ������
	 *
	 * @var array
	 */
	protected $_errors = array();


	/**
	 * ������������ xml �������� � html ����
	 *
	 * @var bool
	 */
	protected static $_xml_style = true;


	/**
	 * ID ��������
	 *
	 * @var null
	 */
	protected $_id = null;

	/**
	 * ������������ �� ��������
	 *
	 * @var bool
	 */
	protected $_htmlspecialchars = true;


	/**
	 * ����������� ��� ����������
	 *
	 * @var bool
	 */
	protected $_require = false;


	const IS_EMPTY = 'is_empty';

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
		$this->_name = $name;
		if (is_array($config)){
			$this->_config = $config;
			$this->initParams();
		}
	}


	/**
	 * ���������� �������������� ����������
	 *
	 * @param $require
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setRequire($require)
	{
		$this->_require = (bool)$require;
		return $this;
	}

	/**
	 * �������� ���� ������������� ����������
	 *
	 * @return bool
	 */
	public function getRequire()
	{
		return $this->_require;
	}

	/**
	 * ������������� ����������
	 */
	protected  function initParams()
	{
		if (is_array($this->_config)){
			foreach ($this->_config as $name => $value){
				$function = 'set' . str_replace('_', '', $name);
				if (method_exists($this,$function)){
					call_user_func(array($this, $function), $value);
				}
			}
		}
	}

	/**
	 * @param $value
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setHtmlspecialchars($value)
	{
		$this->_htmlspecialchars = (bool)$value;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getHtmlspecialchars()
	{
		return $this->_htmlspecialchars;
	}

	/**
	 * ��������� �����
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * ��������� �����
	 *
	 * @param $name
	 */
	public function setName($name)
	{
		$this->_name = $name;
	}

	/**
	 * ��������� ������� ����� ��������
	 *
	 * @return string
	 */
	public function getFullName()
	{
		return (is_string($this->_parent)) ? ($this->_parent . '['.$this->_name . ']') : $this->_name;
	}

	/**
	 * ��������� ��������
	 *
	 * @return null
	 */
	public function getParent()
	{
		return $this->_parent;
	}

	/**
	 * ��������� ��������
	 *
	 * @param $parent
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setParent($parent)
	{
		$this->_parent = $parent;
		return $this;
	}

	/**
	 * ���������� ���������
	 *
	 * @param $label
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setLabel($label)
	{
		$this->_label = $label;
		return $this;
	}

	/**
	 * �������� ��������
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return htmlspecialchars($this->_label);
	}

	/**
	 * ���������� �����
	 *
	 * @param $class
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setClass($class)
	{
		$this->_class = $class;
		return $this;
	}

	/**
	 * �������� �����
	 *
	 * @return string
	 */
	public function getClass()
	{
		return $this->_class;
	}

	/**
	 * ��������� ��������
	 *
	 * @param $value
	 * @param null $data
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setValue($value, &$data = null)
	{
		$this->_value = $value;
		return $this;
	}

	/**
	 * ��������� �������� �� ����
	 *
	 * @param $value
	 * @param null $data
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setValueFromDB($value, &$data = null)
	{
		$this->_value = $value;
		return $this;
	}

	/**
	 * ��������� ��������
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->_value;
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
		return $value;
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
		if ($this->getRequire()){
			if (!trim($value)){
				$this->_errors[] = self::IS_EMPTY;
				return false;
			}
		}
		return true;
	}

	/**
	 * ��������� ������� ������
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * ��������� ������� ������
	 *
	 * @param $errors
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setErrors($errors)
	{
		if (is_array($errors)){
			$this->_errors = $errors;
		}
		return $this;
	}

	/**
	 * ������� ������ ������
	 *
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function clearErrors()
	{
		$this->_errors = array();
		return $this;
	}

	/**
	 * ��������� ������ ��������� �� ������ �������
	 *
	 * @param array $attributes
	 * @return string
	 */
	public function getAttributes(array $attributes)
	{
		$result = '';
		foreach ($attributes as $name => $value){
			$result .= $name . ' = "' . $value . '" ';
		}
		return $result;
	}

	/**
	 * ��������� ������������ ���� � ����������� �� �����
	 *
	 * @return string
	 */
	public function getCloseTag()
	{
		return self::$_xml_style ? '/>' : '>';
	}


	/**
	 * ��������� ID ��������
	 *
	 * @return mixed
	 */
	public function getID()
	{
		return is_string($this->_id) ? $this->_id : trim(str_replace(array('[',']'),'-', str_replace('][', '-', $this->getFullName())),'-');
	}

	/**
	 * ��������� ID ��������
	 *
	 * @param $id
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setID($id)
	{
		$this->_id = $id;
		return $this;
	}


	/**
	 * ��������� ��������
	 *
	 * @abstract
	 * @return mixed
	 */
	abstract public function render();

}
