<?php

class Coffe_BE
{

	/**
	 * Получение ссылки на упраление элементов TableEditor
	 *
	 * @static
	 * @param $table
	 * @param null $primary
	 * @param array $params
	 * @return string
	 */
	public static function urlLF($table, $primary = null, $params = array())
	{
		return Coffe_ModuleManager::getBackendModuleUrl('_tableeditor', array_merge($params, array('primary' => $primary, 'table' => $table)));
	}


	/**
	 * Функция выводит иконки редактирования записей TableEditor
	 *
	 * @static
	 * @param $table
	 * @param $row
	 * @param null $back_url
	 * @param null $key
	 * @param null $rows
	 * @param array $buttons
	 * @return string
	 */
	public static function renderEditTableButtons($table, $row, $back_url = null, $key = null, $rows = null, $buttons = array('add','edit','moveup','movedown','moveup'))
	{

		$add_back_url = (is_array($back_url) && isset($back_url['add'])) ? $back_url['add'] : $back_url;
		$edit_back_url = (is_array($back_url) && isset($back_url['edit'])) ? $back_url['edit'] : $back_url;

		$GLOBALS['LANG']->loadFile('PATH_COFFE:admin/lang/interface.xml');
		$tableeditor = Coffe_ModuleManager::getTableEditor($table);
		$content = '';
		if (is_array($tableeditor) && count($tableeditor) && isset($tableeditor['primary'])){
			$content = '<div class="coffe-table-buttons">';
			$content .= '<ul>';
			if (in_array('add', $buttons)){
				$content .= '<li>';
				$content .= '<a href="'.self::urlLF($table, null, array('back_url' => $add_back_url)).'">';
				$content .= Coffe_Functions::getIcon('coffe/admin/icons/;common/addr');
				$content .= '</a>';
				$content .= '</li>';
			}
			if (in_array('edit', $buttons)){
				$content .= '<li>';
				$content .= '<a href="'.self::urlLF($table, $row[$tableeditor['primary']],array('back_url' => $edit_back_url)).'">';
				$content .= Coffe_Functions::getIcon('coffe/admin/icons/;common/edit');
				$content .= '</a>';
				$content .= '</li>';
			}

			if (in_array('remove', $buttons)){
				$content .= '<li>';
				$content .= '<a href="'.self::urlLF($table, $row[$tableeditor['primary']],array('operation' => 'remove','back_url' => $edit_back_url)).'">';
				$content .= Coffe_Functions::getIcon('coffe/admin/icons/;common/remove');
				$content .= '</a>';
				$content .= '</li>';
			}

			$content .= '</ul>';
			$content .= '</div>';
		}

		return $content;
	}


}














