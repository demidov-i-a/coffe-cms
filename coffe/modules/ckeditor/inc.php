<?php

Coffe_Event::register('RTE.beforeContent', 'ckeditor_before_content');


/**
 * @param $element
 */
function ckeditor_before_content(Coffe_TableEditor_Element_RTE $element)
{
	$content = '';
	$head = Coffe::getHead();
	$head->addJsFile('ckeditor', Coffe::getUrlPrefix() . 'coffe/admin/ckeditor/ckeditor.js');
	$id = $element->getID();

	$head->addJsFile('ckeditor_init', Coffe::getUrlPrefix() . 'coffe/admin/ckeditor/init.js');

	$head->addData('ckeditor_run_' . $id,'
	<script type="text/javascript">
	   ckeditor_init("'.$id.'");
	</script>
	');


}