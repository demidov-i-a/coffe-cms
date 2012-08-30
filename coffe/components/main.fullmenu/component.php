<?php

/**
 *  ��������� ��� ������ ����
 */
class Main_FullMenu_Component extends Coffe_Component
{

	/**
	 * ������������ �� ���������
	 *
	 * @var array
	 */
	public $default_config = array(
		'root_id' => 0, //id �������� ��������, �� ������� ����� ������������� ���� (0 - �� ����� �����)
		'root_level' => 0, //����� ������ ������������ root_id, �� �������� ����� ������������� ����
		'depth' => 99, //������� ������
		'sql_where' => 'NOT hidden AND NOT nav_hide', //�������������� �������
		'show_depth' => 99,
		'open_level' => -1,
		'child_stop_level' => -1,
		'hide_levels' => '',
		'class' => 'fullmenu-block',
		'max_level' => 99, //������� ����������� ����
	);

	/**
	 * ����� �����
	 *
	 * @return string
	 */
	public function main()
	{
		Coffe::getHead()->addJsFile('jquery.effects.core', Coffe::getUrlPrefix() . 'coffe/admin/js/jquery.effects.core.js');
		$rid = $this->conf('root_id', Coffe::getID());
		$rlevel = $this->conf('root_level');
		$depth = $this->conf('depth');
		$sql_where = $this->conf('sql_where','');
		$open_level = $this->conf('open_level');
		$show_depth = $this->conf('show_depth');
		$child_stop_level = $this->conf('child_stop_level');
		$hide_levels = Coffe_Functions::trimExplode(",", $this->conf('hide_levels'));
		$cid = Coffe::getID();
		$branch = array();
		$this->getBranch($branch, $cid);
		if($rlevel) {
			$reverse = array_reverse($branch);
			$rid = isset($reverse[$rlevel-1]) ? $reverse[$rlevel-1] : end($reverse);
		}
		$menu = array(); $level = array(); $link = array();
		$cur_child = false;
		$this->getItems($menu, $level, $link, $rid, 1, $depth, $sql_where, $branch, $cur_child, $hide_levels, $open_level, $show_depth, -1, $child_stop_level);

		$max_level = $this->conf('max_level');
		if ($max_level > 0){
			foreach ($level as $key => $lvl){
				if ($lvl > $max_level){
					$level[$key] = $max_level;
				}
			}
		}
		$this->view->menu = $menu;
		$this->view->level = $level;
		$this->view->link = $link;
		$this->view->cid = $cid;
		$this->view->branch = $branch;
		$this->view->class = $this->conf('class');
		if ($this->conf('debug')){
			echo $this->view->debug();
		}
		return $this->view->render('index.phtml');
	}

	/**
	 * ��������� ����� �� ������� �������� � ����� �� ������
	 *
	 * @param $branch
	 * @param $cur_pid
	 */
	public function getBranch(&$branch, $cur_pid)
	{
		do {
			$res = $this->db->select('uid, pid', 'page', 'uid = ' . $cur_pid);
			$row = $this->db->fetch($res);
			$branch[] = $row['uid'];
			$cur_pid = $row['pid'];
		} while ($cur_pid > 0);
	}


	/**
	 * ������������ ��������� ����
	 *
	 */
	function getItems(&$menu, &$level, &$link, $id, $cur_level, &$depth, &$sql_where, &$branch, $cur_child, &$hide_levels,  $open_level, $show_depth, $show_stop, $child_stop_level)
	{
		$real_where = ($sql_where ? $sql_where.' AND ' : '');
		$res = $this->db->select('uid, title, nav_title', 'page', $real_where . 'pid = ' . $id, '', 'sorting');
		while ($row = $this->db->fetch($res)) {
			// �� ����� �� ������� �������? � � ����� �� ��� ���?
			$hideme = true;
			if (in_array($cur_level, $hide_levels)) {
				if ( (in_array($row['uid'], $branch)) OR ($cur_child) )
					$hideme = false; else $hideme = true;
			} else
				$hideme = false;

			if (!$hideme){

				$menu[$row['uid']] = (isset($row['nav_title']) && trim($row['nav_title'])) ? $row['nav_title'] : $row['title'];
				$level[$row['uid']] = $cur_level;
				$link[$row['uid']] = $this->url($row['uid']);
			}

			// ��� show_depth (������� ������ ������� - ������� ������� �� ����� ���������� �� ��������)
			// ���� show_stop=0, ������������� �������� (��� �������� = -1, � ����� �������� ������� �������� - �������� ������ �������, ����� �� ���� ������ �� ��������)
			if ($row['uid'] == $branch[0]) {
				$show_stop = $show_depth;
			}

			if (($cur_level < $depth) AND ($show_stop <> 0)) {
				if ( ($row['uid'] == $branch[0]) OR ($cur_child) )
					$child = true;
				else
				{
					if ( (in_array($row['uid'], $branch)) and ($cur_level == $open_level) )
						$child = true;
					else
						$child = false;
				}
				if ($cur_level == $child_stop_level) {
					if (in_array($row['uid'], $branch))
						$child = true;
					else $child = false;
				}

				if ($show_stop > 0)
					$show_stop--;
				$this->getItems($menu, $level, $link, $row['uid'], $cur_level+1, $depth, $sql_where, $branch, $child, $hide_levels, $open_level, $show_depth, $show_stop, $child_stop_level);
			}
			//$cur_child = false; #����� ������ �� ����� �� ���������, ��� true (���� ����� ���������� ������ �� �������� ������ ������ ����������)
		}
	}
}