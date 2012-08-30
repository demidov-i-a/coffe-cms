<?php

/**
 * Ёлемент RTE дл€ LiveForm
 *
 * @package coffe_cms
 */
class Coffe_LiveForm_Element_RTE extends Coffe_LiveForm_Element_Textarea
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