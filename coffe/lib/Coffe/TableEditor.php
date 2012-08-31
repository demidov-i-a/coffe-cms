<?php

/**
 * Живая форма
 *
 * @package coffe_cms
 */

class Coffe_TableEditor
{

	/**
	 * массив элементов
	 *
	 * @var array
	 */
	protected $elements = array();

	/**
	 * Имя формы
	 *
	 * @var null|string
	 */
	protected $name = null;

	/**
	 * Action формы
	 *
	 * @var string
	 */
	protected $action = '';

	/**
	 * Метод формы
	 *
	 * @var string
	 */
	protected $method = 'post';

	/**
	 * Группы элементов
	 *
	 * @var array
	 */
	protected $groups = array();

	/**
	 * Настройка внешнего вида формы
	 *
	 * @var array
	 */
	protected $style = array();

	/**
	 * Конфигурация кнопок сохранения формы
	 *
	 * @var array
	 */
	protected $submit = array();

	/**
	 * буффер элементов
	 *
	 * @var array
	 */
	protected $buffer = array();

	/**
	 * Массив значений элементов
	 *
	 * @var array
	 */
	protected $values = array();

	/**
	 * Элементы, которые были отображены
	 *
	 * @var array
	 */
	protected $render_history = array();

	/**
	 * текущая группа
	 *
	 * @var string
	 */
	protected $active_group = '';

	/**
	 * Ключ группы
	 */
	protected $group_key = '_group_';

	/**
	 * Переводчик
	 *
	 * @var null
	 */
	protected $lang = null;

	/**
	 * Массив с путями для переводчика
	 *
	 * @var array
	 */
	protected static $translate_path = array('coffe/admin/lang/form.xml');


	/**
	 * Массив кнопок
	 *
	 * @var array
	 */
	protected $buttons = array('apply', 'cancel', 'submit');


	/**
	 * Класс для кнопок
	 *
	 * @var string
	 */
	protected $button_class = 'btn btn-theme';

	/**
	 * Конструктор
	 *
	 * @param $name имя формы
	 */
	public function __construct($name)
	{
		$this->name = $name;
		$this->lang = new Coffe_Translate();
		foreach(self::$translate_path as $path)
			$this->lang->loadFile(PATH_ROOT . $path);
	}

	/**
	 * Получение переводчика
	 *
	 * @return Coffe_Translate|null
	 */
	public function getLang()
	{
		return $this->lang;
	}

	/**
	 * Получение переводчика
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
	 * Установить action для формы
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
	 * Получить action формы
	 *
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Установить method для формы
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
	 * Получить method формы
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * Установка имени формы
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
	 * Получение имени формы
	 *
	 * @return null|string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Получить активную группу
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
	 * Установить активную группу
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
	 * Добавление элемента в форму
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
	 * Получение элемента
	 *
	 * @param $name
	 * @return null|array
	 */
	public function getElement($name)
	{
		return (isset($this->elements[$name])) ? $this->elements[$name] : null;
	}

	/**
	 * Получение объекта элемента
	 *
	 * @param $name
	 * @return null|object
	 */
	public function getElementObject($name)
	{
		return (isset($this->elements[$name])) ? $this->elements[$name]['object'] : null;
	}

	/**
	 * Вывод элементов
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
	 * Построение формы на основе массива конфигурации
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
	 * Установка групп элементов
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
	 * Получение групп
	 *
	 * @return array
	 */
	public function getGroups()
	{
		return $this->groups;
	}

	/**
	 * Заполнение формы данными
	 *
	 * @param $data
	 * @param bool $from_db значения передаются из базы
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
	 * Вывод элементов конкретной группы
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
	 * Вывод группы вместе с оберткой
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
	 * Очищает историю вывода элементов
	 */
	public function clearHistory()
	{
		$this->render_history = array();
	}

	/**
	 * Распределяет элементы по группам
	 */
	protected function checkGroups()
	{
		if (!isset($this->groups['default']['elements']))
			$this->groups['default']['elements'] = array();

		//проверяем наличие элементов во всех группах
		foreach ($this->elements as $name => $element){
			$find = false;
			foreach ($this->groups as $group){
				//если элемент в какой-то группе
				if (isset($group['elements']) && is_array($group['elements']) && in_array($name, $group['elements'])){
					$find = true;
					break;
				}
			}
			//если не нашли, добавляем элемент в группу по умолчанию
			if (!$find){
				$this->groups['default']['elements'][] = $name;
			}
		}
		//проверям лишние элементы в группах
		foreach ($this->groups as $group_name => $group){
			//если элемент в какой-то группе
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
	 * Получить число элементов в группе
	 *
	 * @param $group
	 * @return int
	 */
	public function getCountGroup($group)
	{
		$this->checkGroups();
		return (isset($this->groups[$group]['elements'])) ? count($this->groups[$group]['elements']) : 0;
	}

	/** Получение значений формы
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

	/** Получение значений формы для базы
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
	 * Проверяет валидность данных
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
	 * Вывод вкладок
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
	 * Рисует кнопки
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
	 * Устанавливает массив кнопок формы
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
	 * Устанавливает класс кнопок
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
	 * Возвращает количество элементов
	 *
	 * @return int
	 */
	public function getCount()
	{
		return count($this->elements);
	}

}