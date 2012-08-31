<?php

/**
 * Ёлемент RTE дл€ TableEditor
 *
 * @package coffe_cms
 */
class Coffe_TableEditor_Element_RTE extends Coffe_TableEditor_Element_Textarea
{

	public function render()
	{
		$content = '';
		if (Coffe_Event::isRegister('RTE.beforeContent')){
			$content .= Coffe_Event::callLast('RTE.beforeContent', array(&$this));
		}
		$content .= parent::render();
		if (Coffe_Event::isRegister('RTE.afterContent')){
			$content .= Coffe_Event::callLast('RTE.afterContent', array(&$this));
		}
		return $content;
	}

}