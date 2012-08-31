<?php

/**
 * Работа со страницами
 *
 * @package coffe_cms
 */

class Coffe_Page extends Coffe_Tree_DB
{

	protected $table = 'page';

	protected $table_cache = 'page_cache';

	protected $key_parent = 'pid';

	protected $key_primary = 'uid';

	protected $key_sorting = 'sorting ASC';

	protected $key_name = 'title';

	protected $root = array('title' => '', 'uid' => 0);

	/**
	 * @var Coffe_Page
	 */
	private static $instance = null;


	public function __construct($directCall = true)
	{
		if ($directCall) {
			trigger_error("It is impossible to use the construct for class Singleton creation.
			Use the static getInstance() method.",E_USER_ERROR);
		}

		$this->root['title'] = Coffe::getConfig('sitename');
		parent::__construct();
	}

	/**
	 * Получение страницы по id
	 *
	 * @param $primary
	 * @param string $add_where
	 * @param string $fields
	 * @return array|bool
	 */
	public function getById($primary, $add_where = 'NOT hidden', $fields = '*')
	{
		$primary = intval($primary);
		return parent::getById($primary, $add_where, $fields);
	}

	/**
	 * Получение страниц одной ветки
	 *
	 * @param $primary
	 * @param string $add_where
	 * @param string $fields
	 * @return array|bool
	 */
	public function getByPid($primary, $add_where = 'NOT hidden', $fields = '*', $check_children = true)
	{
		$primary = intval($primary);
		return parent::getByPid($primary, $add_where, $fields, $check_children);
	}

	/**
	 * Добавление подстраницы
	 *
	 * @param $pid
	 * @param $data
	 * @param $position
	 * @param $target
	 * @return bool|int
	 * @throws Coffe_Exception
	 */
	public function add($pid, $data, $position, $target)
	{
		$data['pid'] = intval($pid);
		if (!trim($data['title'])){
			throw new Coffe_Exception('The empty title of page isn\'t admissible');
		}
		$data['sorting'] = $this->getSortingValue($position, $target);

		if (!is_numeric($data['sorting'])){
			throw new Coffe_Exception('There was a mistake in attempt to receive a sorting index');
		}
		$charset = Coffe::getConfig('charset','UTF-8');
		$data['alias'] = trim($data['alias'])
			? Coffe_Func::strToUrl($data['alias'], $charset)
			: Coffe_Func::strToUrl($data['title'], $charset);

		if (!strlen($data['alias'])){
			throw new Coffe_Exception('There was a mistake in attempt to receive a page alias');
		}
		$this->checkAlias($data['alias']);
		$new_uid = $this->db->insert($this->table, $data);
		if ($new_uid !== false){
			$this->resortBranch($data['pid']);
			Coffe_Event::call('Page.AfterAdd', array(&$new_uid, &$data, &$this));
		}
		return $new_uid;
	}

	/**
	 * Редактирование страницы
	 *
	 * @param $uid
	 * @param $data
	 * @return bool
	 * @throws Coffe_Exception
	 */
	public function edit($uid, $data)
	{
		if (!trim($data['title'])){
			throw new Coffe_Exception('The empty title of page isn\'t admissible');
		}
		//корректируем псевдоним
		$charset = Coffe::getConfig('charset','UTF-8');
		$data['alias'] = trim(trim($data['alias'])
			? Coffe_Func::strToUrl($data['alias'], $charset)
			: Coffe_Func::strToUrl($data['title'], $charset));

		if (!strlen($data['alias'])){
			throw new Coffe_Exception('There was a mistake in attempt to receive a page alias');
		}
		$this->checkAlias($data['alias'],$uid);
		if ($res = $this->db->update($this->table, $data,'`uid` = ' . intval($uid))){
			Coffe_Event::call('Page.AfterUpdate', array(&$uid, &$data, &$this));
		}
		return $res;
	}

	/**
	 * Проверка уникальности alias в базе
	 *
	 * @param $alias
	 * @param null $uid
	 */
	public function checkAlias(&$alias, $uid = null)
	{
		$new_alias = $alias;
		$where = ($uid === null) ? '' : ' AND `uid` <> ' . intval($uid);
		$res = $this->db->select('count(*) as count',$this->table,"`alias` = '" . $this->db->escapeString($new_alias) . "'" . $where);
		$row = $this->db->fetch($res);
		$counter = 2;
		while ($row['count'] > 0){
			$new_alias = $alias . $counter;
			$res = $this->db->select('count(*) as count','page',"`alias` = '" . $this->db->escapeString($new_alias) . "'" . $where);
			$row = $this->db->fetch($res);
			$counter++;
		}
		$alias = $new_alias;
	}

	/**
	 * Получение индекса сортировки для добавления страницы
	 *
	 * @param $position - after - после или before - перед target
	 * @param $target целевая страница
	 * @return int
	 */
	public function getSortingValue($position,$target)
	{
		$target_page = $this->getById($target,'');
		if ($target_page){
			switch($position){
				case 'after': return ($target_page['sorting'] + 1); break;
				case 'before': return ($target_page['sorting'] - 1); break;
			}
		}
		return -1;
	}

	/**
	 * Перемещение страницы
	 *
	 * @param $uid
	 * @param $pid
	 * @param $position
	 * @param $target
	 * @return bool
	 * @throws Coffe_Exception
	 */
	public function move($uid, $pid, $position, $target)
	{
		$sorting = $this->getSortingValue($position,$target);
		if (!is_numeric($sorting)){
			throw new Coffe_Exception('There was a mistake in attempt to receive a sorting index');
		}
		if ($res = $this->db->update($this->table,array('pid' => intval($pid),'sorting' => $sorting),'uid = ' . intval($uid))){
			$this->resortBranch($pid);
		}
		return $res;
	}

	/**
	 * Пересчитывает индексы сортировки для ветви дерева
	 *
	 * @param $pid
	 */
	public function resortBranch($pid)
	{
		$res = $this->db->select('uid, sorting', $this->table, '`pid` = ' . intval($pid), '', 'sorting ASC');
		$sorting = 0;
		while($row = $this->db->fetch($res)){
			$this->db->update($this->table, array('sorting' => $sorting), 'uid = ' . $row['uid']);
			$sorting += 2;
		}
	}

	/**
	 * Удаление страницы по ID
	 *
	 * @param $uid
	 * @return bool
	 */
	public function remove($uid)
	{
		$uid = intval($uid);
		if ($res = $this->db->delete($this->table,'uid = ' . $uid)){
			Coffe_Event::call('Page.AfterRemove', array(&$uid, &$this));
		}
		return $res;
	}

	/**
	 * Ищет страницу в кеше
	 *
	 * @param $cacheID
	 * @param $pid
	 * @return array|bool
	 */
	public function findInCache($cacheID, $pid)
	{
		$res = $this->db->select(
			'*',
			$this->table_cache,
			"pid = " . intval($pid) . " AND cacheID = '" .$this->db->escapeString($cacheID) . "'"
		);
		return $this->db->fetch($res);
	}

	public function savePageCache($pid, $cacheID, $content)
	{
		$this->db->delete($this->table_cache, "cacheID = '" .$this->db->escapeString($cacheID) . "'");
		$insert = array(
			'content' => $content,
			'cacheID' => $cacheID,
			'pid' => intval($pid),
			'config' => serialize($GLOBALS['CFA']),
			'head' => serialize(Coffe::getHead()),
		);
		return $this->db->insert($this->table_cache, $insert);
	}


	/**
	 * Очистка кеша страниц
	 *
	 * @param null $uid
	 * @return bool
	 */
	public function clearCache($uid = null)
	{
		if ($uid !== null)
			$res = $this->db->delete($this->table_cache, 'pid = ' . intval($uid));
		else
			$res = $this->db->delete($this->table_cache, '');

		Coffe_Event::call('Page.onClearCache', array($uid));

		return $res;
	}


	/**
	 * Получение единственного экземпляра класса
	 *
	 * @static
	 * @return Coffe_Page
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self(false);
		}
		return self::$instance;
	}





}
