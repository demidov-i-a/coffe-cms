<?php

function __create_menu_main_module__()
{
	$page = Coffe::getPage();
	Coffe_ModuleManager::loadModuleLang('main');
	$Lang = $GLOBALS['LANG'];

	Coffe_ModuleManager::addMenu('_pages', $Lang->get('menu_pages'), array(
		'sorting' => -999,
		'be_module' => '_pages',
	));

	if ($page)
	{
		//добаление страницы
		Coffe_ModuleManager::addMenu('_pages_add', $Lang->get('menu_pages_add'), array(
			'position' => '_pages',
			'be_module' => '_pages',
			'be_params' => array(
				'action' => 'add',
				'uid' => $page['pid']
			)
		));

		//добаление подстраницы
		Coffe_ModuleManager::addMenu('_pages_add_sub', $Lang->get('menu_pages_add_sub'), array(
			'position' => '_pages',
			'be_module' => '_pages',
			'be_params' => array(
				'action' => 'add',
				'uid' => $page['uid']
			)
		));

		//редактирование текущей страницы
		Coffe_ModuleManager::addMenu('_pages_edit', $Lang->get('menu_pages_edit'), array(
			'position' => '_pages',
			'be_module' => '_liveform',
			'be_params' => array(
				'table' => 'page',
				'primary' => $page['uid']
			)
		));
	}


	//управление модул€ми
	Coffe_ModuleManager::addMenu('_mod_manager', $Lang->get('menu_mod_manager'), array(
		'be_module' => '_mod_manager',
	));

	//управление компонентами
	Coffe_ModuleManager::addMenu('_cp_manager', $Lang->get('menu_cp_manager'), array(
		'be_module' => '_cp_manager',
	));

	//управление пользовател€ми
	Coffe_ModuleManager::addMenu('_user', $Lang->get('menu_user'), array(
		'be_module' => '_user',
	));

	//группы
	Coffe_ModuleManager::addMenu('_user_group', $Lang->get('menu_user_group'), array(
		'position' => '_user',
		'be_module' => '_user',
		'be_params' => array(
			'action' => 'groups',
		)
	));

}

if (Coffe::isAdmin()){
	Coffe_Event::register('beforePanelRender', '__create_menu_main_module__');
}

/**
 * »нициализаци€ параметров head
 *
 * @param Coffe_Head $head
 */
function main_afterInitHead($head)
{
	$page = Coffe::getPage();
	$keywords[] = Coffe::getConfig('keywords');
	$keywords[] = $page['keywords'];
	Coffe_Functions::clearEmptyArray($keywords);
	$description[] = Coffe::getConfig('description');
	$description[] = $page['description'];
	Coffe_Functions::clearEmptyArray($description);
	$head->addData('charset', '<meta http-equiv="Content-Type" content="text/html; charset='.Coffe::getConfig('charset').'" ' . $head->getCloseTag());
	$forceTitle = Coffe::getConfig('forceTitle');
	Coffe::setTitle(empty($forceTitle) ? $page['title'] : $forceTitle);
	if (count($keywords))
		$head->addData('keywords', '<meta name="keywords" content="'. implode(',',$keywords) .'" ' . $head->getCloseTag());
	if (count($description))
		$head->addData('description', '<meta name="description" content="'. implode(',',$description) .'" ' . $head->getCloseTag());
}

Coffe_Event::register('afterInitHead', 'main_afterInitHead');


/**
 * ѕодключение компонента
 */
function main_afterComponentInclude($content, $id, $template, $config, $data, $key = null , $list = null)
{
	$class = 'cf-cmp';
	$cp_data = '';
	if (is_array($data) && isset($data['uid']) && intval($data['cache'])){
		$class .= ' cf-cmp-' . $data['uid'];
		$cp_data = 'data-id="' . $data['uid'] . '"';
	}
	$content = '<div ' . $cp_data . ' class="'. $class .'">' . $content . '<span class="cf-cmp-after"></span></div>';

	Coffe_ModuleManager::loadModuleLang('main');
	$Lang = $GLOBALS['LANG'];

	if (Coffe::isEditMode()){
		$class = 'cf-cmp-edit';

		if (is_array($data) && isset($data['uid']) && intval($data['cache'])){
			$class .= ' cf-cmp-cache';
		}
		else{
			$class .= ' cf-cmp-uncache';
		}

		$menu = array();
		if (is_array($data) && isset($data['uid']))
		{
			$edit_url = Coffe_ModuleManager::getBackendModuleUrl('_liveform', array(
				'table' => 'component',
				'primary' => $data['uid'],
				'back_url' => urlencode(Coffe_Functions::getAbsPrefixUrl() . 'coffe/includes/close_update.html')
			));

			$remove_url = Coffe_ModuleManager::getBackendModuleUrl('_liveform', array(
				'table' => 'component',
				'primary' => $data['uid'],
				'operation' => 'remove',
				'back_url' => urlencode(Coffe_Functions::getAbsPrefixUrl() . 'coffe/includes/close_update.html')
			));

			$move_up_url = false;
			if (($key !== null) && (isset($list[$key - 1])))
				$move_up_url = Coffe_ModuleManager::getBackendModuleUrl('_liveform', array(
					'table' => 'component',
					'primary' => $data['uid'],
					'operation' => 'moveup',
					'target' => $list[$key - 1]['uid'],
					'back_url' => urlencode(Coffe_Functions::getAbsPrefixUrl() . 'coffe/includes/close_update.html')
				));

			$move_down_url = false;
			if (($key !== null) && (isset($list[$key + 1])))
				$move_down_url = Coffe_ModuleManager::getBackendModuleUrl('_liveform', array(
					'table' => 'component',
					'primary' => $data['uid'],
					'operation' => 'movedown',
					'target' => $list[$key + 1]['uid'],
					'back_url' => urlencode(Coffe_Functions::getAbsPrefixUrl() . 'coffe/includes/close_update.html')
				));

			$add_url = Coffe_ModuleManager::getBackendModuleUrl('_cp_manager', array(
				'action' => 'addComponent',
				'data' => array('pid' => $data['pid']),
				'back_url' => urlencode(Coffe_Functions::getAbsPrefixUrl() . 'coffe/includes/close_update.html'),
                'sorting' => array('target' => $data['uid'], 'after' => '1')
			));

			$menu['main_edit'] = array('onclick' => "COFFE_PANEL.goto('" . urlencode($edit_url). "')", 'icon' => 'coffe/admin/icons/;common/edit');
			$menu['main_add'] = array('onclick' => "COFFE_PANEL.goto('" . urlencode($add_url). "')", 'icon' => 'common/addr');
			if ($move_up_url){
				$menu['main_move_up'] = array('onclick' => "COFFE_PANEL.doAjaxOperation('" . urlencode($move_up_url). "',true)", 'icon' => 'common/moveu');
			}
			if ($move_down_url){
				$menu['main_move_down'] = array('onclick' => "COFFE_PANEL.doAjaxOperation('" . urlencode($move_down_url). "',true)", 'icon' => 'common/moved');
			}
			$menu['main_remove'] = array('onclick' => "if (confirm('". $Lang->get('remove_component_confirm', array('COMPONENT' => $data['id']))."')) COFFE_PANEL.doAjaxOperation('" . urlencode($remove_url). "',true)", 'icon' => 'common/remove');

		}
		$content = '<div class="'. $class .'">' . Coffe_CpManager::getComponentMenu($menu). $content . '</div>';
	}
}

Coffe_Event::register('afterComponentInclude', 'main_afterComponentInclude');


function main_setUserKeyAjax()
{
	$array = (isset($_POST['key'])) ? $_POST : $_GET;
	if (isset($array['key']) && isset($array['data'])){
		$key = (string)$array['key'];
		Coffe_User::getInstance()->setPermanentData($key, $array['data']);
	}
}

if (Coffe::isAdmin()){
	Coffe_Event::register('cfAjax.setUserKey','main_setUserKeyAjax');
}