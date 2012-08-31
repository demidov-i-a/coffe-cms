<?php

class Coffe_BE
{

	/**
	 * Получение ссылки на упраление элементов LiveForm
	 *
	 * @static
	 * @param $table
	 * @param null $primary
	 * @param array $params
	 * @return string
	 */
	public static function urlLF($table, $primary = null, $params = array())
	{
		return Coffe_ModuleManager::getBackendModuleUrl('_liveform', array_merge($params, array('primary' => $primary, 'table' => $table)));
	}


	public static function renderEditTableButtons($table, $row, $back_url = null, $key = null, $rows = null, $buttons = array('add','edit','moveup','movedown','moveup'))
	{
		$GLOBALS['LANG']->loadFile('PATH_COFFE:admin/lang/interface.xml');
		$live_form = Coffe_ModuleManager::getLiveForm($table);
		$content = '';
		if (is_array($live_form) && count($live_form) && isset($live_form['primary'])){
			$content = '<div class="coffe-table-buttons">';
			$content .= '<ul>';
			if (in_array('add', $buttons)){
				$content .= '<li>';
				$content .= '<a href="'.self::urlLF($table, null, array('back_url' => $back_url)).'">';
				$content .= Coffe_Functions::getIcon('coffe/admin/icons/;common/addr');
				$content .= '</a>';
				$content .= '</li>';
			}
			if (in_array('edit', $buttons)){
				$content .= '<li>';
				$content .= '<a href="'.self::urlLF($table, $row[$live_form['primary']],array('back_url' => $back_url)).'">';
				$content .= Coffe_Functions::getIcon('coffe/admin/icons/;common/edit');
				$content .= '</a>';
				$content .= '</li>';
			}
			$content .= '</ul>';
			$content .= '</div>';
		}

		return $content;
	}


}














