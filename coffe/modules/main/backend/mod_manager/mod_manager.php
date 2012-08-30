<?php
/**
 * Управление модулями
 *
 * @package coffe_cms
 */

class Mod_Manager_Module extends Coffe_Module
{

	protected $module_id = '_mod_manager';

	/**
	 * Таблица с модулями
	 *
	 * @var string
	 */
	protected $module_table = 'module';

	/**
	 * Точка входа
	 */
	function run()
	{
		$action = $this->_GET('action','index');
		if (method_exists($this, $action . 'Action')){
			call_user_func(array($this, $action . 'Action'));
		}
	}

	/**
	 * Список модулей
	 */
	public function indexAction()
	{
		if (!file_exists(PATH_CONFIG_FILE) || !is_writable(PATH_CONFIG_FILE)){
			$this->flash->pushError($this->lang('FILE_ERROR'));
		}
		$installed_modules = $this->getInstalledModules();
		$this->view->modules = Coffe_ModuleManager::getModulesDescription();
		$this->module_menu[] = array('title' => $this->lang('UPDATE_FILE_TITLE'),'href' => $this->url($this->module_id, array('action' => 'updatefile')));
		foreach($this->view->modules as $name => &$module){
			if (isset($installed_modules[$name])){
				$module['install'] = $installed_modules[$name]['install'] ? true : false;
			}
		}
		$this->module_title = $this->lang('MODULE_LIST');
		return $this->render('index.phtml');
	}

	public function updateFileAction()
	{
		if ($this->updateConfigFile()){
			$this->flash->pushSuccess($this->lang('UPDATE_FILE_SUCCESS'));
		}
		else{
			$this->flash->pushError($this->lang('UPDATE_FILE_ERROR'));
		}
		$this->redirectToModule();
	}

	/**
	 * Инсталляция модуля
	 */
	public function installAction()
	{
		if ($module = $this->_GP('module', false)){
			$module = trim(strtolower($module));
			if ($module == 'main') $this->redirectToModule();
			$res = $this->db->select('*', $this->module_table , 'name = ' . $this->db->fullEscapeString($module));
			if ($module_row = $this->db->fetch($res)){
				$this->db->update($this->module_table, array('install' => 1), 'name = ' . $this->db->fullEscapeString($module));
			}
			else{
				$this->db->insert($this->module_table, array('name' => $module, 'install' => 1));
			}
			$this->updateConfigFile();
		}
		$this->redirectToModule();
	}

	/**
	 * Деинсталляция модуля
	 */
	public function uninstallAction()
	{
		if ($module = $this->_GP('module', false)){
			$module = trim(strtolower($module));
			if ($module == 'main') $this->redirectToModule();
			$res = $this->db->select('*', $this->module_table , 'name = ' . $this->db->fullEscapeString($module));
			if ($module_row = $this->db->fetch($res)){
				$this->db->update($this->module_table, array('install' => 0), 'name = ' . $this->db->fullEscapeString($module));
			}
			$this->updateConfigFile();
		}

		$this->redirectToModule();
	}

	/**
	 * Обновляет файл конфигурации
	 */
	public function updateConfigFile()
	{
		$installed_modules = $this->getInstalledModules();
		$user_modules = Coffe_ModuleManager::getModulesDescription();
		$buffer = array();
		foreach($installed_modules as $name => $module){
			if ($module['install'] && isset($user_modules[$name]))
				$buffer[] = $name;
		}
		if (!Coffe_ModuleManager::updateModulesInConfigFile($buffer)){
			$this->flash->pushError($this->lang('UPDATE_FILE_ERROR'));
			return false;
		}
		return true;
	}

	/**
	 * Просмотр модуля
	 */
	public function moduleAction()
	{
		if ($module = $this->_GP('module',false)){

			$user_modules = Coffe_ModuleManager::getModulesDescription();

			if (!isset($user_modules[$module])){
				return $this->renderContent($this->lang('MODULE_NOT_FOUND'));
			}
			$this->view->moduleFlash = new Coffe_Flash();
			$this->view->moduleFlash->setSessionKey('coffe_module_' .$module);

			$this->module_title = $this->lang('MODULE_TITLE', array('MODULE' => $module));

			$this->view->module = $module;
			$this->view->module_info = $user_modules[$module];

			$this->view->have_scheme = false;
			if (isset($this->view->module_info['description']['scheme']) && is_array($this->view->module_info['description']['scheme'])){
				$this->view->have_scheme = true;
				//требуется обновить базу
				$this->view->need_db_update = false;
				$this->view->scheme = array();

				$scheme = $this->view->module_info['description']['scheme'];
				$this->view->scheme_differences = array();

				foreach ($scheme as $name => $description){
					$errors = array(); $warnings = array();
					if (Coffe_ModuleManager::validateScheme($description, $errors, $warnings)){
						$this->view->scheme[$name] = $description;
						//если вообще такая схема
						if ($current = $this->db->getScheme($name)){
							$other = false;
							$dif = Coffe_ModuleManager::getSchemeDifference($description, $current, $other);
							if (count($dif) || $other){
								$this->view->need_db_update = true;
								if (count($dif))
									$this->view->scheme_differences[$name] = $dif;
							}
						}
						else{
							$this->view->need_db_update = true;
							$this->view->scheme_differences[$name] = $description['columns'];
						}
					}
					if (count($errors)) $this->view->moduleFlash->pushError($errors);
					if (count($warnings)) $this->view->moduleFlash->pushInfo($warnings);
				}

				if ($this->view->need_db_update){
					$this->flash->pushInfo($this->lang('NEED_SCHEME_UPDATE'));
				}
				else{
					if (!$this->view->moduleFlash->count('error'))
						$this->view->moduleFlash->pushInfo($this->lang('NO_NEED_SCHEME_UPDATE'));
				}
			}
			return $this->render('module.phtml');
		}
	}

	/**
	 * Обновление базы данных
	 */
	public function updateDBAction()
	{
		if ($module = $this->_GP('module',false)){
			$user_modules = Coffe_ModuleManager::getModulesDescription();
			if (!isset($user_modules[$module])){
				return $this->renderContent($this->lang('MODULE_NOT_FOUND'));
			}
			$this->view->module_info = $user_modules[$module];
			if (isset($this->view->module_info['description']['scheme']) && is_array($this->view->module_info['description']['scheme'])){
				$this->view->scheme = array();
				$scheme = $this->view->module_info['description']['scheme'];
				foreach ($scheme as $name => $description){
					$errors = array(); $warnings = array();
					if (Coffe_ModuleManager::validateScheme($description, $errors, $warnings)){
						$this->db->installScheme($description, $errors, $warnings);
					}
					if (count($errors)) $this->flash->pushError($errors);
					if (count($warnings)) $this->flash->pushInfo($warnings);
				}
				if ($this->flash->count('error') == 0){
					$this->flash->pushSuccess($this->lang('UPDATE_DB_SUCCESS'));
				}
			}
		}
		$this->redirectToModule(null, array('action' => 'module', 'module' => $module));
	}

	/**
	 * Получение списка установленных модулей
	 *
	 * @return array
	 */
	public function getInstalledModules()
	{
		$modules = array();
		$res = $this->db->select('*', $this->module_table,'');
		while($row = $this->db->fetch($res)){
			$modules[$row['name']] = $row;
		}
		return $modules;
	}


}