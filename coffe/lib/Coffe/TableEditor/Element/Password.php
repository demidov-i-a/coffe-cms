<?php
/**
 * Элемент Password для TableEditor
 *
 * @package coffe_cms
 */
class Coffe_TableEditor_Element_Password extends Coffe_TableEditor_Element_Abstract
{


	/**
	 * Показать пароль
	 *
	 * @var bool
	 */
	protected $_show_password = false;


	/**
	 * Установка флага отображения пароля
	 *
	 * @param $show_password
	 * @return Coffe_TableEditor_Element_Password
	 */
	public function setShowPassword($show_password)
	{
		$this->_show_password = (bool)$show_password;
		return $this;
	}

	/**
	 * Получение флага отображения пароля
	 *
	 * @return bool
	 */
	public function getShowPassword()
	{
		return $this->_show_password;
	}

	public function render()
	{
        $attributes = array(
            'name' => $this->getFullName(),
            'id' => $this->getID(),
        );

		if ($this->getShowPassword()){
			$attributes['value'] = $this->_htmlspecialchars ? htmlspecialchars($this->_value) : $this->_value;
		}

		return '<input type="password" ' . $this->getAttributes($attributes) . $this->getCloseTag();
	}

}