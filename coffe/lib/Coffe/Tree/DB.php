<?php

/**
 * Класс для работы с таблицами,
 * описывающими древовидную структуру
 *
 * @package coffe_cms
 */

class Coffe_Tree_DB
{

	/**
	 * первичный столбец таблицы (PK)
	 *
	 * @var string
	 */
	protected $key_primary = '';

	/**
	 * столбец с id родителя (FK)
	 *
	 * @var string
	 */
	protected $key_parent = '';

	/**
	 * Массив, определяющий корневой элемент дерева
	 *
	 * @var array
	 */
	protected $root = array();

	/**
	 * Ключ по которому выполняется сортировка элементов дерева
	 *
	 * @var string
	 */
	protected $key_sorting = '';


	/**
	 * Ключ с названием элемента
	 *
	 * @var string
	 */
	protected $key_name = '';

	/**
	 * Объект базы данных
	 *
	 * @var Coffe_DB_Abstract
	 */
	protected $db = null;

	/**
	 * Название таблицы
	 *
	 * @var string
	 */
	protected $table = '';

	/**
	 * Массив открытых веток
	 *
	 * @var array
	 */
	protected $opened_id_array = array();

	public function __construct()
	{
		$this->db = $GLOBALS['COFFE_DB'];
	}

	/**
	 * Установка таблицы
	 *
	 * @param $table
	 * @return Coffe_Tree_DB
	 */
	public function setTable($table)
	{
		$this->table = (string)$table;
		return $this;
	}

	/**
	 * Получение таблицы
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Установка PK
	 *
	 * @param $key
	 * @return Coffe_Tree_DB
	 */
	public function setPrimaryKey($key)
	{
		$this->key_primary = (string)$key;
		return $this;
	}

	/**
	 * Получение PK
	 *
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return $this->key_primary;
	}

	/**
	 * Установка ключа-родителя
	 *
	 * @param $key
	 * @return Coffe_Tree_DB
	 */
	public function setParentKey($key)
	{
		$this->key_parent = (string)$key;
		return $this;
	}

	/**
	 * Получение ключа-родителя
	 *
	 * @return string
	 */
	public function getParentKey()
	{
		return $this->key_parent;
	}

	/**
	 * Установка ключа сортировки
	 *
	 * @param $key
	 * @return Coffe_Tree_DB
	 */
	public function setSortingKey($key)
	{
		$this->key_sorting = (string)$key;
		return $this;
	}

	/**
	 * Получение ключа сортировки
	 *
	 * @return string
	 */
	public function getSortingKey()
	{
		return $this->key_sorting;
	}

	/**
	 * Установка корневого элеемнта дерева
	 *
	 * @param $root
	 * @return Coffe_Tree_DB
	 */
	public function setRoot($root)
	{
		if (is_array($root)){
			$this->root = $root;
		}
		return $this;
	}

	/**
	 * Получение корневого элемента дерева
	 *
	 * @return array
	 */
	public function getRoot()
	{
		return $this->root;
	}

	/**
	 * Проверяет наличие необходимых ключей
	 *
	 * @throws Coffe_Exception
	 */
	protected function checkKeys()
	{
		if ($this->key_parent === null)
			throw new Coffe_Exception("The parent key of an array isn't set");

		if ($this->key_primary === null)
			throw new Coffe_Exception("The primary key of an array isn't set");

		if (!isset($this->root[$this->key_primary]))
			throw new Coffe_Exception("The primary key of a root element isn't set");

		if (!trim($this->table))
			throw new Coffe_Exception("The name of the table isn't set");
	}

	/**
	 * Получение элемента по PK из базы данных
	 *
	 * @param $primary
	 * @param string $add_where
	 * @param string $fields
	 * @return array|bool
	 */
	public function getById($primary, $add_where = '', $fields = '*')
	{
		$this->checkKeys();
		$primary = trim($primary);
		$where = $this->key_primary . " = '" . $this->db->escapeString($primary) . "'";
		$where .= (!empty($add_where)) ? (' AND ' . $add_where) : '';
		return $this->db->fetch($this->db->select($fields, $this->table, $where));
	}

	/**
	 * Получение элементов по FK из базы данных
	 *
	 * @param $parent
	 * @param string $add_where
	 * @param string $fields
	 * @param bool $check_children
	 * @return array|bool
	 */
	public function getByPid($parent, $add_where = '', $fields = '*', $check_children = true)
	{
		$this->checkKeys();
		$parent = trim($parent);
		$where = $this->key_parent . " = '" . $this->db->escapeString($parent) . "'";
		$where .= (!empty($add_where)) ? (' AND ' . $add_where) : '';
		$items = $this->db->fetchAll($this->db->select($fields, $this->table, $where, '', $this->getOrderBy()));

		//собираем информацию о наличии дочерних элементов
		if (is_array($items) && $check_children){
			foreach ($items as $key => $item){
				$where = $this->key_parent . " = '" . $this->db->escapeString($item[$this->key_primary]) . "'";
				$where .= (!empty($add_where)) ? (' AND ' . $add_where) : '';
				$count = $this->db->fetch($this->db->select('COUNT(*) as count', $this->table, $where));
				$items[$key]['children_count'] = $count['count'];
			}
		}
		return $items;
	}

	/**
	 * Получение ветки вверх от указанного элемента
	 *
	 * @param $primary
	 * @param string $add_where
	 * @param string $fields
	 * @param int $depth
	 * @return array
	 */
	public function getBranchUp($primary, $add_where = '', $fields = '*', $depth = 9999)
	{
		$this->checkKeys();
		$branch = array();
		$primary = trim($primary);
		$where = $this->key_primary . " = '" . $this->db->escapeString($primary) . "'";
		$where .= (!empty($add_where)) ? (' AND ' . $add_where) : '';
		$item = $this->db->fetch($this->db->select($fields, $this->table, $where));
		if ($item){
			$branch[] = $item;
			while($item
				&& isset($item[$this->key_parent])
				&& ($item[$this->key_parent] != $this->root[$this->key_primary])
				&& ($depth > 0)
			){
				$where = $this->key_primary . " = '" . $this->db->escapeString($item[$this->key_parent]) . "'";
				$where .= (!empty($add_where)) ? (' AND ' . $add_where) : '';
				$item = $this->db->fetch($this->db->select($fields, $this->table, $where, '', $this->getOrderBy()));
				if ($item) $branch[] = $item;
				$depth--;
			}
		}
		return $branch;
	}

	/**
	 * Выдает дерево элементов
	 *
	 * @param null $parent
	 * @param string $add_where
	 * @param string $fields
	 * @param int $depth
	 * @return array
	 */
	public function getTree($parent = null, $add_where = '', $fields = '*', $depth = 1)
	{
		$this->checkKeys();
		$fields = $this->addKeysToFields($fields);
		//от рута
		if (!$parent){
			$tree = $this->root;
		}
		//от заданного элемента
		else{
			$tree = $this->getById($parent, $add_where, $fields);
			if (!is_array($tree)){
				return array();
			}
		}
		$this->getTreeRecursive($tree, $parent, $add_where, $fields, $depth);
		return $tree;
	}

	/**
	 * Рекурсивно загружает дерево элементов
	 *
	 * @param $tree
	 * @param null $parent
	 * @param string $add_where
	 * @param string $fields
	 * @param int $depth
	 * @param bool $use_opened_array
	 */
	protected function getTreeRecursive(&$tree, $parent = null, $add_where = '', $fields = '*', $depth = 1, $use_opened_array = true)
	{
		$opened_array = $this->opened_id_array;

		$where = $this->key_parent . " = '" . $this->db->escapeString($parent) . "'";
		$where .= (!empty($add_where)) ? (' AND ' . $add_where) : '';
		$count = $this->db->fetch($this->db->select('COUNT(*) as count', $this->table, $where));
		$tree['children_count'] = $count['count'];
		//if ((($use_opened_array && in_array($tree[$this->key_primary],$opened_array)) || ($depth > 0)) && $tree['children_count']){
		if ((($use_opened_array && in_array($tree[$this->key_primary],$opened_array)) || ($tree[$this->key_primary] == $this->root[$this->key_primary])) && $tree['children_count']){
			$depth --;
			$tree['children'] = $this->db->fetchAll($this->db->select($fields, $this->table, $where,'',$this->getOrderBy()));
			if (is_array($tree['children']) && count($tree['children'])){
				foreach ($tree['children'] as &$item){
					$this->getTreeRecursive($item , $item[$this->key_primary],$add_where , $fields , $depth, $use_opened_array);
				}
			}
			else{
				unset($tree['children']);
			}
		}
	}

	/**
	 * Добавляет недостающие столбцы к fields
	 *
	 * @param $fields
	 * @return string
	 */
	protected function addKeysToFields($fields)
	{
		if (trim($fields) == '*') return $fields;
		$columns = explode(',',$fields);
		if (!in_array($this->key_parent,$columns))
			$columns[] = $this->key_parent;
		if (!in_array($this->key_primary,$columns))
			$columns[] = $this->key_primary;
		return implode(',', $columns);
	}

	/**
	 * получение orderby секции запроса
	 *
	 * @return null|string
	 */
	protected function getOrderBy()
	{
		return ($this->key_sorting) ? $this->key_sorting : '';
	}

	/**
	 * Вывод дерева меню для backend
	 *
	 * @param $tree
	 * @param null $callback
	 * @return bool|null|string
	 */
	public function printTree($tree, $callback = null)
	{
		return $this->printTreeRecursive($tree, $callback);
	}


	/**
	 * Рекурсивно рисует дерево элементов
	 *
	 * @param $tree
	 * @param $callback
	 * @param null $content
	 * @param int $level
	 * @return bool|null|string
	 */
	private function printTreeRecursive($tree, $callback, &$content = null, $level = 0)
	{
		if (!is_array($tree)) return false;

		$opened_array = $this->opened_id_array;

		$have_children = ((isset($tree['children_count']) && ($tree['children_count'] > 0)));

		$content .= '<div class="tree-line">';

		if ($level > 0){
			$data = 'data-id = "' . $tree[$this->key_primary] . '" data-level="' . $level . '"';
			$plus = in_array($tree[$this->key_primary], $opened_array) ? '' : ' plus';
			$class = ($have_children) ? ('item-button minus' . $plus) : '';
			$content .= '<span ' . $data . ' class="item ' . $class . '"></span>';
		}

		if (is_callable($callback)){
			$content .= call_user_func($callback , $tree, $level);
		}
		else{
			$content .= isset($tree[$this->key_name]) ? htmlspecialchars($tree[$this->key_name]) : 'item';
		}

		$content .= "<div class='clearfix'></div>";

		if (isset($tree['children'])){
			$content .= '<div class="children">';
			$counter = 0;
			foreach ($tree['children'] as $page){
				$this->printTreeRecursive($page, $callback, $content, $level + 1);
				$counter ++;
			}
			$content .= '</div>';
		}
		$content .= '</div>';
		return ($level) ? $content : ('<div class="coffe-db-tree">' . $content . '</div>');
	}

	/**
	 * Устанавливает массив открытых ветвей
	 *
	 * @param $arr
	 */
	public function setOpenedIDArray($arr)
	{
		$this->opened_id_array = (array)$arr;
	}


	/**
	 * Выводит только одну ветку (ajax запрос)
	 *
	 * @param $tree
	 * @param $callback
	 * @param null $content
	 * @param int $level
	 * @return null|string
	 */
	public function printTreeChildren($tree, $callback, &$content = null, $level = 0)
	{
		if (isset($tree['children']) && is_array($tree['children'])){
			$content .= '<div class="children">';
			$counter = 0;
			foreach ($tree['children'] as $page){
				//передаем детям сведения, имеют ли братья ниже по дереву их родителей
				$parents[$level] = ($counter != count($tree) - 1);
				$this->printTreeRecursive($page, $callback,  $content, $level + 1);
				$counter ++;
			}
		}
		return $content;
	}

	/**
	 * Получение массива первичных ключей вниз по дереву
	 *
	 * @param $uid
	 * @param array $array
	 * @return array
	 */
	public function getPrimaryArrayDown($uid, &$array = array())
	{
		$array[] = $uid;
		$fields = $this->key_primary . ',' .  $this->key_parent;
		$res = $this->db->select($fields,$this->table,$this->key_parent. '=' .$this->db->fullEscapeString($uid));
		while ($row = $this->db->fetch($res)){
			$this->getPrimaryArrayDown($row[$this->key_primary], $array);
		}
		return $array;
	}

}







