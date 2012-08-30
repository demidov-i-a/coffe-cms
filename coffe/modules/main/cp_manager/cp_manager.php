<?php

/**
 * Управление компонентами
 *
 * @package coffe_cms
 */
class Cp_Manager_Module extends Coffe_Module
{

	/**
	 * ID backend - модуля
	 *
	 * @var string
	 */
	protected $module_id = '_cp_manager';


	/**
	 * Группы компонентов
	 *
	 * @var array
	 */
	protected $component_groups = array('main','user','forms', 'media', 'plugin', 'other');


	/**
	 * список компонентов без привязки к страницам
	 */
	public function indexAction()
	{
		$this->module_title = $this->lang('component_list');
		if ($this->isPost()){
			$groups = $this->_POST('cp_groups', false);
			$this->updateGroupsPosition($groups);
		}
		$this->view->components = Coffe_CpManager::getRowsNoPage();
		$this->module_menu[] = array(
			'title' => $this->lang('new_component'),
			'href' => $this->url(null, array('action' => 'addComponent','back_url' => $this->url()))
		);
		$this->render('index.phtml');
	}

	/**
	 * Обновление групп компонентов
	 *
	 * @param $groups
	 */
	protected function updateGroupsPosition($groups)
	{
		if (is_array($groups)){
			foreach($groups as $uid => $group){
				$this->db->update('component',array('cp_group' => trim($group)), 'uid = ' . intval($uid));
			}
		}
	}

	/**
	 * Добавление компонента
	 */
	public function addComponentAction()
	{
		$CPage = Coffe_Page::getInstance();

		//данные для заполнения формы по умолчанию
		$this->view->data = $this->_GP('data', array());

		//параметры сортировки
		$this->view->sorting = $this->_GP('sorting', array());

		//url для возврата
		$this->view->back_url = $this->_GET('back_url');

		//добавление компонента на страницу
		if (isset($this->view->data['pid']) && $this->view->data['pid'] != '0'){
			$page = $CPage->getById($this->view->data['pid'], '');
			if (!$page){
				$this->flash->pushError($this->lang('page_not_found'));
				return $this->renderContent();
			}
			$this->module_title = $this->lang('add_component_page', array('PAGE' => $page['title']));
		}
		else{
			$this->module_title = $this->lang('add_component');
		}

		if ($this->_GET('cancel')){
			return $this->redirectToBack();
		}

		//получение списка доступных компонентов
		$this->view->components = Coffe_CpManager::getAllComponents();

		$component_sorting = array();
		foreach ($this->view->components as $component){
			if (isset($component['description']['group']) && in_array($component['description']['group'], $this->component_groups)){
				$component_sorting[$component['description']['group']][] = $component['id'];
			}
			else{
				$component_sorting['other'][] = $component['id'];
			}
		}
		$this->view->component_sorting = $component_sorting;
		$this->view->component_groups = $this->component_groups;
		return $this->render('select.phtml');
	}

	/**
	 * Вернуться назад
	 *
	 * @return bool
	 */
	public function redirectToBack()
	{
		if (!empty($this->view->back_url))
		{
			return $this->redirect(urldecode($this->view->back_url));
		}
		return $this->redirectToModule();
	}

}