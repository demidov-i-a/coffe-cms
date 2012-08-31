<?php

/**
 * ����� �����
 *
 * @package coffe_cms
 */

class Coffe_TableEditor
{

	/**
	 * ������ ���������
	 *
	 * @var array
	 */
	protected $elements = array();

	/**
	 * ��� �����
	 *
	 * @var null|string
	 */
	protected $name = null;

	/**
	 * Action �����
	 *
	 * @var string
	 */
	protected $action = '';

	/**
	 * ����� �����
	 *
	 * @var string
	 */
	protected $method = 'post';

	/**
	 * ������ ���������
	 *
	 * @var array
	 */
	protected $groups = array();

	/**
	 * ��������� �������� ���� �����
	 *
	 * @var array
	 */
	protected $style = array();

	/**
	 * ������������ ������ ���������� �����
	 *
	 * @var array
	 */
	protected $submit = array();

	/**
	 * ������ ���������
	 *
	 * @var array
	 */
	protected $buffer = array();

	/**
	 * ������ �������� ���������
	 *
	 * @var array
	 */
	protected $values = array();

	/**
	 * ��������, ������� ���� ����������
	 *
	 * @var array
	 */
	protected $render_history = array();

	/**
	 * ������� ������
	 *
	 * @var string
	 */
	protected $active_group = '';

	/**
	 * ���� ������
	 */
	protected $group_key = '_group_';

	/**
	 * ����������
	 *
	 * @var null
	 */
	protected $lang = null;

	/**
	 * ������ � ������ ��� �����������
	 *
	 * @var array
	 */
	protected static $translate_path = array('coffe/admin/lang/form.xml');


	/**
	 * ������ ������
	 *
	 * @var array
	 */
	protected $buttons = array('apply', 'cancel', 'submit');


	/**
	 * ����� ��� ������
	 *
	 * @var string
	 */
	protected $button_class = 'btn btn-theme';

	/**
	 * �����������
	 *
	 * @param $name ��� �����
	 */
	public function __construct($name)
	{
		$this->name = $name;
		$this->lang = new Coffe_Translate();
		foreach(self::$translate_path as $path)
			$this->lang->loadFile(PATH_ROOT . $path);
	}

	/**
	 * ��������� �����������
	 *
	 * @return Coffe_Translate|null
	 */
	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * ��������� �����������
	 *
	 * @param $lang
	 * @return Coffe_TableEditor
	 */
	public function setLang($lang)
	{
		$this->lang = $lang;
		return $this;
	}

	/**
	 * ���������� action ��� �����
	 *
	 * @param $action
	 * @return Coffe_TableEditor
	 */
	public function setAction($action)
	{
		$this->action = $action;
		return $this;
	}

	/**
	 * �������� action �����
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * ���������� method ��� �����
	 *
	 * @param $method
	 * @return Coffe_TableEditor
	 */
	public function setMethod($method)
	{
		$this->method = $method;
		return $this;
	}

	/**
	 * �������� method �����
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * ��������� ����� �����
	 *
	 * @param $name
	 * @return Coffe_TableEditor
	 */
	public function setName($name)
	{
		$this->name = $name;
		foreach ($this->elements as &$element){
			$element['object']->setParent($this->name);
		}
		return $this;
	}

	/**
	 * ��������� ����� �����
	 *
	 * @return null|string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * �������� �������� ������
	 *
	 * @return string
	 */
	public function getActiveGroup()
	{
		if (!$this->active_group){
			foreach ($this->groups as $key => $group){
				$this->active_group = $key;
				break;
			}
		}
		return $this->active_group;
	}

	/**
	 * ���������� �������� ������
	 *
	 * @param $group
	 * @return Coffe_TableEditor
	 */
	public function setActiveGroup($group)
	{
		$this->active_group = (string)$group;
		return $this;
	}

	/**
	 * ���������� �������� � �����
	 *
	 * @param $class
	 * @param $name
	 * @param null $config
	 * @param string $group
	 * @return Coffe_TableEditor
	 * @throws Coffe_Exception
	 */
	public function addElement($class, $name, $config = null, $group = 'default')
	{
		if (isset($this->elements[$name])){
			throw new Coffe_Exception('TableEditor: element with name ' . $name . ' already registered!');
		}
		$element_class = 'Coffe_TableEditor_Element_' . $class;
		if (!class_exists($element_class)){
			throw new Coffe_Exception('TableEditor: the element with class ' . $element_class . ' is not found!');
		}
		$this->elements[$name]['object'] = new $element_class($name, isset($config['config']) ? $config['config'] : null);
		$this->elements[$name]['config'] = $config;
		$this->elements[$name]['object']->setParent($this->getName());
		$this->groups[$group]['elements'][] = $name;
		return $this;
	}

	/**
	 * ��������� ��������
	 *
	 * @param $name
	 * @return null|array
	 */
	public function getElement($name)
	{
		return (isset($this->elements[$name])) ? $this->elements[$name] : null;
	}

	/**
	 * ��������� ������� ��������
	 *
	 * @param $name
	 * @return null|object
	 */
	public function getElementObject($name)
	{
		return (isset($this->elements[$name])) ? $this->elements[$name]['object'] : null;
	}

	/**
	 * ����� ���������
	 *
	 * @return string
	 */
	public function renderElements()
	{
		$buffer = array();
		foreach ($this->elements as $name => $element){
			$buffer[$name] = $element['object']->render();
		}
		$content = '';
		foreach ($buffer as $name => $element){
			if (in_array($name, $this->render_history)) continue;
			$content .= '<div class="element-wrapper">';
			$type = $this->elements[$name]['config']['type'];
			$label_class = '';
			if ($type == 'checkbox') $label_class = 'label-inline-left';
			$content .= '<label class="' . $label_class . '" for="'. $this->elements[$name]['object']->getID() .'">'. $this->elements[$name]['object']->getLabel() .'</label>';
			$content .= $element;
			$content .= '</div>';
			$this->render_history[] = $name;
		}
		return $content;
	}

	/**
	 * ���������� ����� �� ������ ������� ������������
	 *
	 * @param array $config
	 * @return Coffe_TableEditor
	 */
	public function build(array $config)
	{
		if (isset($config['elements']) && is_array($config['elements'])){
			foreach ($config['elements'] as $name => $conf){
				if (is_array($conf))
					$this->addElement($conf['type'], $name, $conf);
			}
		}
		if (isset($config['groups']) && is_array($config['groups'])){
			$this->groups = $config['groups'];
		}
		if (isset($config['buttons']) && is_array($config['buttons'])){
			$this->buttons = $config['buttons'];
		}
		return $this;
	}

	/**
	 * ��������� ����� ���������
	 *
	 * @param $groups
	 * @return Coffe_TableEditor
	 */
	public function setGroups($groups)
	{
		$this->groups = (array)$groups;
		return $this;
	}

	/**
	 * ��������� �����
	 *
	 * @return array
	 */
	public function getGroups()
	{
		return $this->groups;
	}

	/**
	 * ���������� ����� �������
	 *
	 * @param $data
	 * @param bool $from_db �������� ���������� �� ����
	 * @return Coffe_TableEditor
	 */
	public function populate($data, $from_db = false)
	{
		if (is_array($data)){
			foreach ($data as $name => $value){
				if (isset($this->elements[$name])){
					if ($from_db)
						$this->elements[$name]['object']->setValueFromDB($value, $data);
					else
						$this->elements[$name]['object']->setValue($value, $data);
				}
			}
			if (isset($data[$this->group_key])){
				$this->setActiveGroup($data[$this->group_key]);
			}
		}
		return $this;
	}

	/**
	 * ����� ��������� ���������� ������
	 *
	 * @param $group
	 * @return string
	 */
	public function renderGroup($group)
	{
		$this->checkGroups();
		$content = '';
		if (!isset($this->groups[$group])) return $content;
		if (isset($this->groups[$group]) && isset($this->groups[$group]['elements'])){
			foreach ($this->groups[$group]['elements'] as $name){
				if (!isset($this->elements[$name]) || in_array($name, $this->render_history)) continue;
				$content .= '<div class="element-wrapper">';
				$type = strtolower($this->elements[$name]['config']['type']);
				$label_class = '';

				if ($type != 'tableeditor'){
					if ($type == 'checkbox') $label_class = 'label-inline-left';
					$content .= '<label class="' . $label_class . '" for="'. $this->elements[$name]['object']->getID() .'">'. $this->elements[$name]['object']->getLabel() .'</label>';
				}

				$content .= $this->elements[$name]['object']->render();
				$errors = $this->elements[$name]['object']->getErrors();
				if (count($errors)){
					$content .= '<div class="element-errors">';
					foreach($errors as $error){
						$content .= '<div class="element-error">';
						$content .= $this->lang->get($error, null, $error);
						$content .= '</div>';
					}
					$content .= '</div>';
				}
				$content .= '</div>';
				$this->render_history[] = $name;
			}
		}
		return $content;
	}

	/**
	 * ����� ������ ������ � ��������
	 *
	 * @param $group
	 * @return string
	 */
	public function renderGroupWithWrapper($group)
	{
		$active_group = $this->getActiveGroup();
		$content = '<div class="coffe-form-tab-item tab-' . $group . '-block" ' .(($active_group == $group) ? '' : 'style="display:none"') . ' >';
		$content .= $this->renderGroup($group);
		$content .= '</div>';
		return $content;
	}

	/**
	 * ������� ������� ������ ���������
	 */
	public function clearHistory()
	{
		$this->render_history = array();
	}

	/**
	 * ������������ �������� �� �������
	 */
	protected function checkGroups()
	{
		if (!isset($this->groups['default']['elements']))
			$this->groups['default']['elements'] = array();

		//��������� ������� ��������� �� ���� �������
		foreach ($this->elements as $name => $element){
			$find = false;
			foreach ($this->groups as $group){
				//���� ������� � �����-�� ������
				if (isset($group['elements']) && is_array($group['elements']) && in_array($name, $group['elements'])){
					$find = true;
					break;
				}
			}
			//���� �� �����, ��������� ������� � ������ �� ���������
			if (!$find){
				$this->groups['default']['elements'][] = $name;
			}
		}
		//�������� ������ �������� � �������
		foreach ($this->groups as $group_name => $group){
			//���� ������� � �����-�� ������
			if (isset($group['elements']) && is_array($group['elements'])){
				foreach($group['elements'] as $key => $name){
					if (!isset($this->elements[$name])){
						unset($this->groups[$group_name]['elements'][$key]);
					}
				}
			}
		}
	}

	/**
	 * �������� ����� ��������� � ������
	 *
	 * @param $group
	 * @return int
	 */
	public function getCountGroup($group)
	{
		$this->checkGroups();
		return (isset($this->groups[$group]['elements'])) ? count($this->groups[$group]['elements']) : 0;
	}

	/** ��������� �������� �����
	 *
	 * @param $data
	 * @return array
	 */
	public function getValues($data)
	{
		$this->values = array();
		foreach ($this->elements as $name => $element){
			if (!(isset($element['config']['exclude']) && $element['config']['exclude']))
				$this->values[$name] = $element['object']->setValue(isset($data[$name]) ?  $data[$name] : null)->getValue();
		}
		if (isset($data[$this->group_key])){
			$this->setActiveGroup($data[$this->group_key]);
		}
		return $this->values;
	}

	/** ��������� �������� ����� ��� ����
	 *
	 * @param $data
	 * @return array
	 */
	public function getValuesForDB($data)
	{
		$this->values = array();
		foreach ($this->elements as $name => $element){
			if (!(isset($element['config']['exclude']) && $element['config']['exclude']))
				$element['object']->setValue((isset($data[$name]) ?  $data[$name] : null), $data);
			$this->values[$name] = $element['object']->prepareForDB(null, $data);
		}
		if (isset($data[$this->group_key])){
			$this->setActiveGroup($data[$this->group_key]);
		}
		return $this->values;
	}

	/**
	 * ��������� ���������� ������
	 *
	 * @param $data
	 * @return bool
	 */
	public function isValid($data)
	{
		$is_valid = true;
		foreach ($this->elements as $name => $element){
			if (!(isset($element['config']['exclude']) && $element['config']['exclude'])){
				$valid = $element['object']->isValid((isset($data[$name]) ?  $data[$name] : null), $data);
				if (!$valid){
					$is_valid = false;
				}
			}
		}
		return $is_valid;
	}

	/**
	 * ����� �������
	 */
	public function renderTabs()
	{
		$this->checkGroups();
		$active_group = $this->getActiveGroup();
		$content = '<ul>';
		foreach($this->groups as $group => $params){
			if ($this->getCountGroup($group)){
				$content .= '<li '.($active_group == $group ? 'class="active"' : '').' data-tab="' . $group . '">';
				$content .= isset($params['label']) ? htmlspecialchars($params['label']) : '???';
				$content .= '</li>';
			}
		}
		$content .= '</ul>';
		return $content;
	}


	public function render()
	{
		$active = $this->getActiveGroup();
		$content = '<form enctype="multipart/form-data" action="'. $this->getAction() .'" method="'. $this->getMethod() .'">';
		$content .= '<input class="form_tab" type="hidden" value="' . $active . '" name="'.$this->name . '[' .$this->group_key.']"/>';
		$content .= '<div class="coffe-form-block">';
		$content .= '<div class="coffe-form-tabs-menu">';
		$content .= $this->renderTabs();
		$content .= '<div class="clearfix"></div>';
		$content .= '</div>';
		$content .= '<div class="coffe-form-body">';
		foreach($this->groups as $group => $params){
			if ($this->getCountGroup($group)){
				$content .= $this->renderGroupWithWrapper($group);
			}
		}
		$content .= $this->renderButtons();
		$content .= '</div>';
		$content .= '</div>';
		$content .= '</form>';
		return $content;
	}

	/**
	 * ������ ������
	 *
	 * @return string
	 */
	public function renderButtons()
	{
		$content = '';
		if (count($this->buttons)){
			$content .= '<div class="element-wrapper tableeditor-buttons">';
			if (in_array('submit', $this->buttons))
				$content .= '<input class="' . $this->button_class . '" id="submit" type="submit" value="'.$this->lang->get('SUBMIT').'" />';
			if (in_array('apply', $this->buttons))
				$content .= '<input class="' . $this->button_class . '" id="apply" type="submit" value="'.$this->lang->get('APPLY').'" name="apply"/>';
			if (in_array('cancel', $this->buttons))
				$content .= '<input class="' . $this->button_class . '" id="cancel" type="submit" value="'.$this->lang->get('CANCEL').'" name="cancel"/>';
			$content .= '</div>';
		}
		return $content;
	}

	/**
	 * ������������� ������ ������ �����
	 *
	 * @param $buttons
	 * @return Coffe_TableEditor
	 */
	public function setButtons($buttons)
	{
		$this->buttons = (array)$buttons;
		return $this;
	}

	/**
	 * ������������� ����� ������
	 *
	 * @param $class
	 * @return Coffe_TableEditor
	 */
	public function setButtonsClass($class)
	{
		$this->button_class = $class;
		return $this;
	}

	/**
	 * ���������� ���������� ���������
	 *
	 * @return int
	 */
	public function getCount()
	{
		return count($this->elements);
	}

}