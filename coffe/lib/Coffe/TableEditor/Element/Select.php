<?php
/**
 * Элемент Select для TableEditor
 *
 * @package coffe_cms
 */
class Coffe_TableEditor_Element_Select extends Coffe_TableEditor_Element_Abstract
{

	/**
	 * Массив элементов списка
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Атрибут size
	 *
	 * @var int
	 */
	protected $size = 1;

	/**
	 * Разрешить множественный выбор
	 *
	 * @var bool
	 */
	protected $multiple = false;

	/**
	 * Разделитель значений при сохранении в базу
	 *
	 * @var string
	 */
	protected $value_delimiter  = ',';


	/**
	 * Элементы readonly
	 *
	 * @var array
	 */
	protected $read_only_values = array();

	/**
	 * Установка элементов списка
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
	 * Установка значений только для чтения
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
	 * Получение значений только для чтения
	 *
	 * @return array
	 */
	public function getReadOnlyValues()
	{
		return $this->read_only_values;
	}

	/**
	 * Получение элементов списка
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
	 * Установка атрибута Size
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
	 * Получение атрибута Size
	 *
	 * @return int
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Установка атрибута multiple
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
	 * Получение атрибута multiple
	 *
	 * @return bool
	 */
	public function getMultiple()
	{
		return $this->multiple;
	}

	/**
	 * Установка разделителя значений
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
	 * Получение разделителя для значений
	 *
	 * @return string
	 */
	public function getDelimiter()
	{
		return $this->value_delimiter;
	}

	/**
	 * Получение полного имени элемента
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
	 * Вывод элемента
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
	 * Проверяет, что текущее значение элемента выбрано
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
	 * Подготовка данных дляя сохранения в базу
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
	 * Установка значения из базы
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
	 * Проверка валидности данных
	 *
	 * @param $value
	 * @param null $data
	 * @return bool
	 */
	public function isValid($value, &$data = null)
	{
		//значения как массив
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