<?php
/**
 * Ёлемент Hidden дл€ LiveForm
 *
 * @package coffe_cms
 */
class Coffe_LiveForm_Element_Hidden extends Coffe_LiveForm_Element_Abstract
{

	public function render()
	{
        $attributes = array(
            'name' => $this->getFullName(),
            'id' => $this->getID(),
            'value' => $this->_htmlspecialchars ? htmlspecialchars($this->_value) : $this->_value,
        );
		return '<input type="hidden" ' . $this->getAttributes($attributes) . $this->getCloseTag();
	}

}