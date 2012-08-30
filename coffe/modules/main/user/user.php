<?php
/**
 * Управление пользователями
 *
 * @package coffe_cms
 */
class User_Module extends Coffe_Module
{

	/**
	 * ID backend - модуля
	 *
	 * @var string
	 */
	protected $module_id = '_user';


	/**
	 * Список параметров
	 */
	function indexAction()
	{
		$this->createMenu();
		$CUser = Coffe_User::getInstance();
		$this->view->users = $CUser->getList(false);
		$this->module_title = $this->lang('user_list_title');
		$this->render('index.phtml');
	}


	/**
	 * Список групп
	 */
	function groupsAction()
	{

		$this->createMenuGroup();
		$this->module_title = $this->lang('groups');
		$this->view->groups = Coffe_User::getInstance()->getAllGroups();
		$this->render('groups.phtml');
	}

	/**
	 * Добавление группы
	 */
	function addGroupAction()
	{
		if ($this->isPost()){

			if ($this->_POST('cancel')){
				return $this->redirectToModule(null, array('action' => 'groups'));
			}
			$this->view->data = $this->_POST('group');
			$this->view->errors = $this->validateData($this->view->data);
			if (!count($this->view->errors)){
				$result = $this->saveData($this->view->data);
				if (!$result){
					$this->flash->pushError($this->db->lastError());
					return $this->renderContent();
				}
				if ($this->_POST('apply')){
					return $this->redirectToModule(null, array('action' => 'editGroup', 'uid' => $result));
				}
				return $this->redirectToModule(null, array('action' => 'groups'));
			}
			return $this->render('addGroup.phtml');
		}
		$this->view->data = array();
		$this->view->errors = array();
		$this->module_title = $this->lang('create_group_title');
		$this->render('addGroup.phtml');
	}

	/**
	 * Создает меню модуля
	 */
	function createMenu()
	{
		$this->module_menu[] = array(
			'title' => $this->lang('new_user'),
			'href' => $this->urlLf('user', null, array('back_url' => $this->url()))
		);

	}


	/**
	 * Создает меню в группах
	 */
	function createMenuGroup()
	{

		$this->module_menu[] = array(
			'title' => $this->lang('new_group'),
			'href' => $this->url(null, array('action' => 'addGroup'))
		);

	}


	/**
	 * Редактирование группы
	 */
	function editGroupAction()
	{
		$group = $this->getGroupById($this->_GET('uid'));
		if (!$group){
			$this->flash->pushError($this->lang('group_not_found'));
			return $this->renderContent();
		}
		if ($this->isPost()){
			if ($this->_POST('cancel')){
				return $this->redirectToModule(null, array('action' => 'groups'));
			}
			$this->view->data = $this->_POST('group');
			$this->view->errors = $this->validateData($this->view->data);

			if (!count($this->view->errors)){
				$result = $this->saveData($this->view->data, $group['uid']);
				if (!$result){
					$this->flash->pushError($this->db->lastError());
					return $this->renderContent();
				}

				if (!$this->_POST('apply')){
					return $this->redirectToModule(null, array('action' => 'groups'));
				}
			}

			return $this->render('editGroup.phtml');
		}
		$this->view->data = $group;
		$this->view->errors = array();
		$this->render('editGroup.phtml');
	}

	/**
	 * Удаление группы
	 */
	function removeGroupAction()
	{
		if ($uid = $this->_GET('uid')){
			if (!$this->db->delete('user_group','uid = ' . intval($uid))){
				$this->flash->pushError($this->db->lastError());
			}
		}
		return $this->redirectToModule(null, array('action' => 'groups'));
	}

	/**
	 * Валидация данных
	 *
	 * @param $data
	 * @return array
	 */
	function validateData($data)
	{
		$errors = array();
		if (!isset($data['title']) || !trim($data['title'])){
			$errors['title'][] = $this->lang('title_empty');
		}
		return $errors;
	}

	/**
	 * Сохранение в базу
	 *
	 * @param $data
	 * @param null $uid
	 * @return bool|int
	 */
	function saveData($data, $uid = null)
	{
		if ($uid === null){
			return $this->db->insert('user_group',$data);
		}
		else{
			return $this->db->update('user_group', $data, 'uid = '. intval($uid));
		}
	}



	/**
	 * Получение группы для редактирования
	 *
	 * @param $uid
	 * @return array|bool
	 */
	function getGroupById($uid)
	{
		$res = $this->db->select('*', 'user_group','uid = ' .intval($uid));
		if ($group = $this->db->fetch($res)){
			return $group;
		}
		return false;
	}
}