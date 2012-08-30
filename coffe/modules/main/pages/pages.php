<?php

/**
 * Управление страницами
 *
 * @package coffe_cms
 */
class Pages_Module extends Coffe_Module
{

	/**
	 * ID backend - модуля
	 *
	 * @var string
	 */
	protected $module_id = '_pages';

	/**
	 * Для хранения информации о перемещаемой страницы
	 *
	 * @var string
	 */
	private $sessionKey = '_module_pages';

	/**
	 * Типы страниц
	 *
	 * @var array
	 */
	protected $page_types = array(10, 20);

	/**
	 * Таблица с компонентами
	 *
	 * @var string
	 */
	protected $table_component = 'component';



	/**
	 * Точка входа
	 */
	function run()
	{
		Coffe::startSession();
		$this->view->update_tree = $this->_GET('update_tree') ? true : false;
		$this->view->page_types = $this->page_types;
		Coffe::getHead()->addJsFile('jquery_ui', Coffe::getUrlPrefix() . 'coffe/admin/js/jquery-ui-1.8.22.custom.min.js');
		parent::run();
	}

	/**
	 * Фрейм с деревом страниц и содержимым страницы
	 */
	public function indexAction()
	{
		$this->render('index.phtml',false, false);
	}

	/**
	 * Вывод дерева страниц
	 */
	public function treeAction()
	{
		Coffe::getHead()->addCssFile('tree_css', Coffe_ModuleManager::getModuleRelPath('main') . 'pages/css/tree.css');
		Coffe::getHead()->addJsFile('tree_js', Coffe::getUrlPrefix(). 'coffe/admin/js/tree.js');
		$this->view->callback = array($this, 'renderPageTreeLink');

		if ($this->_GET('no_layout'))
			$this->render('tree_no_layout.phtml',false);
		else
			$this->render('tree.phtml',true,true);
	}


	/**
	 * Ajax обработчик для разворачивания ветви дерева страниц
	 */
	public function clickPlusAction()
	{
		$pid = $this->_GP('id',false);
		$level = intval($this->_GP('level',0));
		if ($pid){
			$CPage = Coffe_Page::getInstance();
			//открываем только одну ветку, т.к. больше нам не нужно
			$arr = array($pid);
			$CPage->setOpenedIDArray($arr);
			$tree = $CPage->getTree($pid);
			if (is_array($tree)){
				echo $CPage->printTreeChildren($tree, array($this, 'renderPageTreeLink') , $content, $level);
			}
		}
	}

	/**
	 * Ajax обработчик для сворачивания ветви дерева страниц
	 */
	public function clickMinusAction()
	{
		$open = $this->_GP('open','');
		$arr = Coffe_Functions::trimExplode(',', $open, true);
		$CPage = Coffe_Page::getInstance();
		if (is_array($arr)){
			$CPage->setOpenedIDArray($arr);
		}
		Coffe_User::getInstance()->setPermanentData('page_tree_opened_id_arr',$arr);
	}


	/**
	 * Просмотр страницы
	 */
	public function pageAction()
	{
		$CPage = Coffe_Page::getInstance();
		$uid = intval($this->_GET('uid'),0);
		$this->view->page = ($uid != 0) ? $CPage->getById($uid,'') : array('title' => 'Root','uid' => 0, 'pid' => 0, 'id' => 0);
		if ($this->view->page){
			$this->module_title = $this->lang('PAGE',array('PAGE' => $this->view->page['title']));
			$this->createPageMenu($this->view->page,$uid);
			$this->view->position = $this->_GET('cp_position', 0);
			if ($this->view->page['uid'] !== 0){
				$this->view->components = $this->getPageComponents($this->view->page['uid'], array('position' => $this->view->position));
			}
			$this->view->positions = $this->getPositions();
		}
		$this->render('page.phtml');
	}

	/**
	 * Генерирует меню в режиме просмотра страницы
	 *
	 * @param $page
	 * @param $uid
	 */
	public function createPageMenu($page, $uid)
	{
		if (isset($_SESSION[$this->sessionKey]['move_uid']) && $_SESSION[$this->sessionKey]['move_uid'] != $page['uid']){
			$this->module_menu[] = array(
				'title' => $this->lang('INSERT_PAGE'),
				'href' => $this->getPageInsertLink($page['uid'])
			);
		}
		$this->module_menu[] = array(
			'title' => $this->lang('NEW_PAGE'),
			'href' => $this->getPageAddLink($page['uid'])
		);

		if ($uid != 0){
			$this->module_menu[] = array(
				'title' => $this->lang('EDIT_PAGE'),
				'href' => $this->getPageEditLink($page['uid'])
			);
			$this->module_menu[] = array(
				'title' => $this->lang('REMOVE_PAGE'),
				'href' => $this->getPageRemoveLink($page['uid']),
				'onclick' => 'return confirm(\''.htmlspecialchars($this->lang('REMOVE_CONFIRM',array('PAGE' => $page['title']))).'\')'
			);

			$this->module_menu[] = array(
				'title' => $this->lang('MOVE_PAGE'),
				'href' => $this->getPageMoveLink($page['uid']),
			);
		}
	}

	/**
	 * Добавление новой страницы
	 */
	public function addAction()
	{
		$uid = intval($this->_GET('uid',0));
		$CPage = Coffe_Page::getInstance();
		$this->view->page = ($uid > 0) ? $CPage->getById($uid,'') : array('pid' => 0, 'uid' => 0,'title' => 'Root');
		$this->view->errors = array();
		if (!$this->view->page){
			return $this->renderContent($this->lang('PAGE_NOT_FOUND'));
		}
		$this->module_title = ($this->lang('NEW_PAGE_TITLE',array('PAGE' => $this->view->page['title'])));
		$this->view->sub_pages = $CPage->getByPid($uid,'');
		if ($position = $this->_GET('position',false)){
			return $this->redirect($this->url('_liveform',array(
						'table' => 'page',
						'data' => array('pid' => $this->view->page['uid']),
						'back_url' => urlencode($this->url(null, array('action' => 'page', 'uid' => '_PRIMARY_', 'update_tree' => '1'))),
						'sorting' => array(
							'target' => $this->_GET('target',0),
							'after' => ($this->_GET('position') == 'after') ? '1' : '0',
						)
					)
				)
			);
		}
		return $this->render('select.phtml');
	}

	/**
	 * Перемещение страницы
	 */
	public function moveAction()
	{
		$uid = intval($this->_GET('uid'));
		if ($uid){
			$_SESSION[$this->sessionKey]['move_uid'] = $uid;
		}
		$this->flash->pushInfo($this->lang('MOVE_INFO'));
		$this->redirectToModule($this->module_id,array('action' => 'page','uid' => $uid));
	}

	/**
	 * Вставка страницы
	 */
	public function insertAction()
	{
		if (!isset($_SESSION[$this->sessionKey]['move_uid'])){
			return $this->renderContent($this->lang('MOVE_PAGE_NOT_SELECT'));
		}
		$move_uid = $_SESSION[$this->sessionKey]['move_uid'];
		$uid = intval($this->_GET('uid',0));
		$CPage = Coffe_Page::getInstance();
		$this->view->page = ($uid > 0) ? $CPage->getById($uid,'') : array('pid' => 0, 'uid' => 0,'title' => 'Root');
		if (!$this->view->page){
			return $this->renderContent($this->lang('PAGE_NOT_FOUND'));
		}
		$this->view->sub_pages = $CPage->getByPid($this->view->page['uid'],'');

		if ($position = $this->_GET('position',false)){
			//проверяем возможность пермещения
			if ($this->checkUidInTree($uid, $move_uid)){
				$this->flash->pushError($this->lang('MOVE_ERROR1'));
				return $this->renderContent('');
			}

			$done = $CPage->move($move_uid, $uid, $this->_GET('position'),$this->_GET('target'));
			unset($_SESSION[$this->sessionKey]['move_uid']);

			//ошибка
			if (!$done){
				$this->flash->pushError($this->db->lastError());
				return $this->redirectToModule($this->module_id, array('update_tree' => '1','action' => 'page'));
			}
			return $this->redirectToModule($this->module_id,array('action' => 'page','update_tree' => '1','uid' => $move_uid));
		}
		$this->view->action = 'insert';
		return $this->render('select.phtml');
	}

	/**
	 * Получение ссылки на страницу
	 *
	 * @param $uid
	 * @return string
	 */
	public function getPageEditLink($uid)
	{
		$back_url = $this->url(null, array('action' => 'page','uid' => $uid,'update_tree' => '1'));
		return $this->url('_liveform',array('table' => 'page','primary' => $uid, 'back_url' => urlencode($back_url)));
	}

	/**
	 * Получение ссылки на добавление страницы
	 *
	 * @param $uid
	 * @return string
	 */
	public function getPageAddLink($uid)
	{
		return $this->url($this->module_id,array('action'=>'add','uid' => $uid));
	}

	/**
	 * Получение ссылки на перемещение страницы
	 *
	 * @param $uid
	 * @return string
	 */
	public function getPageMoveLink($uid)
	{
		return $this->url($this->module_id,array('action'=>'move','uid' => $uid));
	}

	/**
	 * Получение ссылки на перемещение страницы
	 *
	 * @param $uid
	 * @return string
	 */
	public function getPageRemoveLink($uid)
	{
		$back_url = $this->url(null,array('action' => 'page', 'uid' => 0,'update_tree' => 1));
		return $this->url('_liveform',array('back_url' => $back_url, 'operation'=>'remove','primary' => $uid, 'table' => 'page'));
	}

	/**
	 * Получение ссылки на вставку страницы
	 *
	 * @param $uid
	 * @return string
	 */
	public function getPageInsertLink($uid)
	{
		return $this->url($this->module_id,array('action'=>'insert','uid' => $uid));
	}


	/**
	 * Функция генерирует ссылку в дереве страниц
	 *
	 * @param $tree
	 * @param $level
	 * @return string
	 */
	function renderPageTreeLink($tree, $level)
	{
		$content = '';

		$page_class = (isset($tree['nav_hide']) && $tree['nav_hide']) ? 'page-nav-hide' : 'page-normal';
		$page_class .= ' page-level-' . $level;
		$page_class .= ' page-' . (($level % 2) ? 'odd' : 'even');

		$content .= '<div class="page ' . $page_class . '">';
		$overs = array();
		if (isset($tree['hidden']) && $tree['hidden']){
			$overs[] = 'page-hidden';
		}

		if (isset($tree['type']) && $tree['type'] == '20'){
			$overs[] = 'page-link';
		}
		foreach ($overs as $over){
			$content .= '<div class="' . $over . '">';
		}

		$content .= '<div class="page-over">';
		$link_to_module = $this->_GET('link_to_module', '_pages');
		$conf = '';
		if (isset($tree['config']) && trim($tree['config'])) $conf .= 'i';
		if (isset($tree['sub_config'] ) && $tree['sub_config'] > 0) $conf .= 's';
		$content .= "<a target='list_frame' class='page-item' onclick='parent.COFFE.gotoList(\"".Coffe_ModuleManager::getBackendModuleUrl($link_to_module, array('action' => 'page','uid' => $tree['uid'])) . "\"); return false;' href='javascript:void(0)'>".
			(trim($tree['title']) ? ($tree['title'] . '[' . $tree['uid'] . ']') : '[' . $tree['uid'] . ']')
			."<span class='page-config-info'>" . $conf . "</span></a>";
		$content .= '</div>';

		$cnt = count($overs);
		for ($i = 0; $i < $cnt; $i++){
			$content .= '</div>';
		}

		$content .= '</div>';
		return $content;
	}

	/**
	 * Проверяет наличие $uid в ветке
	 *
	 * @param $check
	 * @param $top
	 * @return bool
	 */
	public function checkUidInTree($check, $top)
	{
		$CPage = Coffe_Page::getInstance();
		$array = $CPage->getPrimaryArrayDown($top);
		return in_array($check, $array);
	}

	/**
	 * Получение компонентов на странице
	 *
	 * @param $uid
	 * @param null $filter
	 * @return array|bool
	 */
	public function getPageComponents($uid, $filter = null)
	{
		$components = array();
		$where = 'pid = '. intval($uid);
		if (is_array($filter)){
			if (isset($filter['position'])){
				$where .= ' AND position = ' . intval($filter['position']);
			}
		}
		$res = $this->db->select('*', $this->table_component, $where,'','sorting');
		$rows = $this->db->fetchAll($res);
		if (is_array($rows)){
			$components = $rows;
		}
		return $components;
	}

	/**
	 * Получение возможных позиций для компонентов
	 *
	 * @return array
	 */
	public function getPositions()
	{
		$positions = array();
		$ext = Coffe_ModuleManager::getLiveForm('component');
		$form = new Coffe_LiveForm('tmp');
		$form->build($ext);
		$pos = $form->getElementObject('position');
		if (is_object($pos)){
			$positions = $pos->getOptions();
		}
		$form = null;
		return $positions;
	}

}