<?php

/**
 * Элемент Checkbox для LiveForm
 *
 * @package coffe_cms
 */
class Coffe_LiveForm_Element_Checkbox extends Coffe_LiveForm_Element_Abstract
{

	/**
	 * Вывод элемента
	 *
	 * @return string
	 */
	public function render()
	{
		$attributes = array(
			'name' => $this->getFullName(),
			'id' => $this->getID(),
		);
		if (!empty($this->_value) && $this->_value == '1'){
			$attributes['checked'] = 'checked';
		}
		return '<input value="on" type="checkbox" ' . $this->getAttributes($attributes) . $this->getCloseTag();
	}

	/**
	 * Установка значения
	 *
	 * @param $value
	 * @return Coffe_LiveForm_Element_Checkbox
	 */
	public function setValue($value, &$data = null)
	{
		$this->_value = $value ? '1' : '0';
		return $this;
	}

	/**
	 * Установка значения из базы
	 *
	 * @param $value
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setValueFromDB($value, &$data = null)
	{
		$this->_value = intval($value) ? '1' : '0';
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
		return true;
	}

}

