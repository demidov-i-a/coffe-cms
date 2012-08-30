<?php

function __afterBuild__($table, $table_ext, $primary, $primary_value, $data, $form, $row, $module_title)
{
	//загружаем шаблоны для компонента
	if ($table == 'component'){
		$id = isset($row['id']) ? $row['id'] : (isset($data['id']) ? $data['id'] : false);
		if ($id){
			$templateObj = $form->getElementObject('template');
			if (is_object($templateObj)){
				$templateObj->setOptions(Coffe_CpManager::getComponentTemplates($id));
			}
		}
	}

	//при редактировании пользователя делаем поле с паролем не обязательным для заполнения
	if ($table == 'user' && !empty($primary_value)){
		$form->getElementObject('password')->setRequire(false);
	}

}

function __beforeBuild__($table, $table_ext, $primary, $primary_value, $data, $form, $row, $module_title)
{
	//загружаем шаблоны для компонента
	if ($table == 'component'){
		$id = isset($row['id']) ? $row['id'] : (isset($data['id']) ? $data['id'] : false);
		if ($id){
			$ext_component = Coffe_CpManager::getOneComponent($id);
			if ($ext_component && isset($ext_component['description']['liveForm']) && is_array($ext_component['description']['liveForm'])){
				$ext = $ext_component['description']['liveForm'];
				$table_ext = Coffe_ModuleManager::mergeExtTables($table_ext, $ext);
			}
		}
		///скрываем группу, если это компонент страницы
		$pid = isset($row['pid']) ? $row['pid'] : (isset($data['pid']) ? $data['pid'] : 0);
		if (intval($pid) > 0){
			unset($table_ext['elements']['cp_group']);
		}
	}
}

function __beforePageOperation__($table, $primary, $primary_value, $operation, $cancel, $flash)
{
	if ($table == 'page' && $operation == 'remove'){
		$CPage = Coffe_Page::getInstance();
		$children = $CPage->getByPid($primary_value,'');
		//проверяем наличие детей у страницы
		if (is_array($children) && count($children)){			
			Coffe_ModuleManager::loadModuleLang('main');
			$flash->pushError($GLOBALS['LANG']->get('cant_remove_page'));
			$cancel = true;
		}
	}
}

function __beforeAdd__($table, $primary, $data, $cancel, $flash)
{
	if ($table == 'page'){
		if (isset($data['alias'])){
			if (!trim($data['alias'])){
				if (isset($data['title'])){
					$data['alias'] = Coffe_Functions::strToUrl($data['title'], Coffe::getConfig('charset'));
				}
			}
			__checkPageAlias__($data['alias']);
		}
	}

	//сохранение поля с паролем
	if ($table == 'user'){
		if (Coffe_Event::isRegister('userSavePassword')){
			$result = Coffe_Event::callLast('userSavePassword', array($data['password'], $data, null));
			if (trim($result)){
				$data['password'] = $result;
			}
		}
	}
}

function __beforeUpdate__($table, $primary, $primary_value, $data, $cancel, $flash)
{
	if ($table == 'page'){
		if (isset($data['alias'])){
			if (!trim($data['alias'])){
				if (isset($data['title'])){
					$data['alias'] = Coffe_Functions::strToUrl($data['title'], Coffe::getConfig('charset'));
				}
			}
			__checkPageAlias__($data['alias'], $primary_value);
			if (!trim($data['alias'])){
				$cancel = true;				
				Coffe_ModuleManager::loadModuleLang('main');
				$flash->pushError($GLOBALS['LANG']->get('page_bad_alias'));
			}
		}
	}

	if ($table == 'user'){
		//при редактировании пользователя, если пароль пустой, не обновляем его
		if (!trim($data['password'])){
			 unset($data['password']);
		}
		else{
			if (Coffe_Event::isRegister('userSavePassword')){
				$result = Coffe_Event::callLast('userSavePassword', array($data['password'], $data, $primary_value));
				if (trim($result)){
					$data['password'] = $result;
				}
			}
		}
	}
}

function __afterRecordAdd__($table, $primary, $primary_value, $data, $cancel, $flash)
{
	if ($table == 'component'){
		if (isset($data['pid'])){
			Coffe::clearPageCache($data['pid']);
		}
	}
}

function __afterRecordUpdate__($table, $primary, $primary_value,  $data, $cancel, $flash)
{
	if ($table == 'page'){
		Coffe::clearPageCache($primary_value);
	}
	if ($table == 'component'){
		if (isset($data['pid'])){
			Coffe::clearPageCache($data['pid']);
		}
	}
}

function __checkPageAlias__(&$alias, $uid = null)
{
	$new_alias = $alias;
	$where = ($uid === null) ? '' : ' AND `uid` <> ' . intval($uid);
	$res = $GLOBALS['COFFE_DB']->select('count(*) as count', 'page', "`alias` = '" . $GLOBALS['COFFE_DB']->escapeString($new_alias) . "'" . $where);
	$row = $GLOBALS['COFFE_DB']->fetch($res);
	$counter = 2;
	while ($row['count'] > 0){
		$new_alias = $alias . $counter;
		$res = $GLOBALS['COFFE_DB']->select('count(*) as count','page',"`alias` = '" . $GLOBALS['COFFE_DB']->escapeString($new_alias) . "'" . $where);
		$row = $GLOBALS['COFFE_DB']->fetch($res);
		$counter++;
	}
	$alias = $new_alias;
}



//обработка компонентов
Coffe_Event::register('LiveForm.afterBuild', '__afterBuild__');
Coffe_Event::register('LiveForm.beforeBuild', '__beforeBuild__');

//обработка страниц
Coffe_Event::register('LiveForm.beforeOperation', '__beforePageOperation__');
Coffe_Event::register('LiveForm.beforeAdd', '__beforeAdd__');
Coffe_Event::register('LiveForm.beforeUpdate', '__beforeUpdate__');

Coffe_Event::register('LiveForm.afterUpdate', '__afterRecordUpdate__');
Coffe_Event::register('LiveForm.afterAdd', '__afterRecordAdd__');

//добавление backend-модулей
$be_path =  'coffe/modules/main/';
Coffe_ModuleManager::registerBM($module, '_liveform', $be_path . 'liveform/liveform.php;Liveform_Module->run');
Coffe_ModuleManager::registerBM($module, '_cp_manager', $be_path . 'cp_manager/cp_manager.php;Cp_Manager_Module->run');
Coffe_ModuleManager::registerBM($module,'_login', $be_path . 'login/login.php;Login_Module->run');
Coffe_ModuleManager::registerBM($module,'_user', $be_path . 'user/user.php;User_Module->run');
Coffe_ModuleManager::registerBM($module, '_pages', $be_path . 'pages/pages.php;Pages_Module->run');
$be_path =  'coffe/modules/main/backend/';
Coffe_ModuleManager::registerBM($module, '_mod_manager', $be_path . 'mod_manager/mod_manager.php;Mod_Manager_Module->run');