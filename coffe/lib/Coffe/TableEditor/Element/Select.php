<?php
/**
 * ������� Select ��� TableEditor
 *
 * @package coffe_cms
 */
class Coffe_TableEditor_Element_Select extends Coffe_TableEditor_Element_Abstract
{

	/**
	 * ������ ��������� ������
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * ������� size
	 *
	 * @var int
	 */
	protected $size = 1;

	/**
	 * ��������� ������������� �����
	 *
	 * @var bool
	 */
	protected $multiple = false;

	/**
	 * ����������� �������� ��� ���������� � ����
	 *
	 * @var string
	 */
	protected $value_delimiter  = ',';


	/**
	 * �������� readonly
	 *
	 * @var array
	 */
	protected $read_only_values = array();

	/**
	 * ��������� ��������� ������
	 *
	 * @param $options
	 * @return Coffe_TableEditor_Element_Select
	 */
	public function setOptions($options)
	{
		if (is_array($options)){
			$this->options = $options;
		}
		return $this;
	}

	/**
	 * ��������� �������� ������ ��� ������
	 *
	 * @param $values
	 * @return Coffe_TableEditor_Element_Select
	 */
	public function setReadOnlyValues($values)
	{
		if (is_array($values))
			$this->read_only_values = $values;
		return $this;
	}

	/**
	 * ��������� �������� ������ ��� ������
	 *
	 * @return array
	 */
	public function getReadOnlyValues()
	{
		return $this->read_only_values;
	}

	/**
	 * ��������� ��������� ������
	 *
	 * @return array
	 */
	public function getOptions()
	{
		if (isset($this->_config['callback'])){
			$result = Coffe_Func::execCallBack($this->_config['callback'], array(&$this->read_only_values, &$this));
			if (is_array($result)){
				$this->options = $result;
			}
		}
		return $this->options;
	}

	/**
	 * ��������� �������� Size
	 *
	 * @param $size
	 * @return Coffe_TableEditor_Element_Select
	 */
	public function setSize($size)
	{
		$this->size = (intval($size)) ? intval($size) : 1;
		return $this;
	}

	/**
	 * ��������� �������� Size
	 *
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * ��������� �������� multiple
	 *
	 * @param $multiple
	 * @return Coffe_TableEditor_Element_Select
	 */
	public function setMultiple($multiple)
	{
		$this->multiple = ($multiple) ? true : false;
		return $this;
	}

	/**
	 * ��������� �������� multiple
	 *
	 * @return bool
	 */
	public function getMultiple()
	{
		return $this->multiple;
	}

	/**
	 * ��������� ����������� ��������
	 *
	 * @param $delimiter
	 * @return Coffe_TableEditor_Element_Select
	 */
	public function setValueDelimiter($delimiter)
	{
		$this->value_delimiter = $delimiter;
		return $this;
	}

	/**
	 * ��������� ����������� ��� ��������
	 *
	 * @return string
	 */
	public function getDelimiter()
	{
		return $this->value_delimiter;
	}

	/**
	 * ��������� ������� ����� ��������
	 *
	 * @return string
	 */
	public function getFullName()
	{
		$name = (is_string($this->_parent)) ? ($this->_parent . '['.$this->_name . ']') : $this->_name;
		if ($this->multiple){
			$name .= '[]';
		}
		return htmlspecialchars($name);
	}

	/**
	 * ����� ��������
	 *
	 * @return string
	 */
	public function render()
	{
		$this->options = $this->getOptions();
		$attributes = array(
			'name' => $this->getFullName(),
			'size' => $this->size
		);
		if ($this->getMultiple()){
			$attributes['multiple'] = 'multiple';
		}
		$content =  '<select ' . $this->getAttributes($attributes) . '>';
		foreach ($this->options as $value => $name){
			$attributes = array('value' => ($this->_htmlspecialchars) ? htmlspecialchars($value) : $value);
			if ($this->isSelected($value)){
				$attributes['selected'] = 'selected';
			}

			if (in_array($value, $this->read_only_values)){
				$attributes['disabled'] = 'disabled';
			}
			$content .=  '<option ' . $this->getAttributes($attributes) . '>' . $name . '</option>';
		}
		$content .=  '</select>';
		return $content;
	}

	/**
	 * ���������, ��� ������� �������� �������� �������
	 *
	 * @param $value
	 * @return bool
	 */
	protected function isSelected($value)
	{
		if (is_array($this->_value) && in_array($value, $this->_value)){
			return true;
		}
		else{
			return ($this->_value == $value);
		}
	}

	/**
	 * ���������� ������ ���� ���������� � ����
	 *
	 * @param null $value
	 * @return null|string
	 */
	public function prepareForDB($value = null, &$data = null)
	{
		if ($value === null){
			$value = $this->_value;
		}
		if (is_array($value)){
			return implode($this->value_delimiter, $value);
		}
		return $value;
	}

	/**
	 * ��������� �������� �� ����
	 *
	 * @param $value
	 * @return Coffe_TableEditor_Element_Abstract
	 */
	public function setValueFromDB($value, &$data = null)
	{
		$arr = explode($this->value_delimiter, $value);
		if (count($arr)){
			$this->_value = $arr;
		}
		else{
			$this->_value = $value;
		}
		return $this;
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
		//�������� ��� ������
		if ($this->getRequire()){
			if ($this->getMultiple()){
				if (!is_array($value) || !count($value)){
					$this->_errors[] = self::IS_EMPTY;
					return false;
				}
			}
			else{
				if (!trim($value)){
					$this->_errors[] = self::IS_EMPTY;
					return false;
				}
			}
		}
		return true;
	}

}