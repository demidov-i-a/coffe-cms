<?php
/**
 * ������� Password ��� TableEditor
 *
 * @package coffe_cms
 */
class Coffe_TableEditor_Element_Password extends Coffe_TableEditor_Element_Abstract
{


	/**
	 * �������� ������
	 *
	 * @var bool
	 */
	protected $_show_password = false;


	/**
	 * ��������� ����� ����������� ������
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
	 * ��������� ����� ����������� ������
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