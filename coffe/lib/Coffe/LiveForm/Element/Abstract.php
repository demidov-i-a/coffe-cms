<?php

/**
 * Абстрактный элемент для LiveForm
 *
 * @package coffe_cms
 *
 */
abstract class Coffe_LiveForm_Element_Abstract
{

	/**
	 * Имя элемента
	 *
	 * @var null
	 */
	protected $_name = null;

	/**
	 * Имя родительского элемента
	 *
	 * @var null
	 */
	protected $_parent = null;

	/**
	 * Заголовок элемента
	 *
	 * @var null
	 */
	protected $_label = null;

	/**
	 * значение элемента
	 *
	 * @var null
	 */
	protected $_value = null;

	/**
	 * Класс элемента
	 *
	 * @var string
	 */
	protected $_class = '';

	/**
	 * конфигурация
	 *
	 * @var null
	 */
	protected $_config = null;

	/**
	 * ошибки
	 *
	 * @var array
	 */
	protected $_errors = array();


	/**
	 * Использовать xml стандарт в html коде
	 *
	 * @var bool
	 */
	protected static $_xml_style = true;


	/**
	 * ID элемента
	 *
	 * @var null
	 */
	protected $_id = null;

	/**
	 * Экранировать ли значение
	 *
	 * @var bool
	 */
	protected $_htmlspecialchars = true;


	/**
	 * Обязательно для заполнения
	 *
	 * @var bool
	 */
	protected $_require = false;


	const IS_EMPTY = 'is_empty';

	/**
	 * Конструктор
	 *
	 * Инициализация имени и конфигурации элемента
	 *
	 * @param $name
	 * @param null $config
	 *
	 */
	public function __construct($name, $config = null)
	{
		$this->_name = $name;
		if (is_array($config)){
			$this->_config = $config;
			$this->initParams();
		}
	}


	/**
	 * Установить обязательность заполнения
	 *
	 * @param $require
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setRequire($require)
	{
		$this->_require = (bool)$require;
		return $this;
	}

	/**
	 * Получить флаг обязательного заполнения
	 *
	 * @return bool
	 */
	public function getRequire()
	{
		return $this->_require;
	}

	/**
	 * Инициализация параметров
	 */
	protected  function initParams()
	{
		if (is_array($this->_config)){
			foreach ($this->_config as $name => $value){
				$function = 'set' . str_replace('_', '', $name);
				if (method_exists($this,$function)){
					call_user_func(array($this, $function), $value);
				}
			}
		}
	}

	/**
	 * @param $value
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setHtmlspecialchars($value)
	{
		$this->_htmlspecialchars = (bool)$value;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getHtmlspecialchars()
	{
		return $this->_htmlspecialchars;
	}

	/**
	 * Получение имени
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Установка имени
	 *
	 * @param $name
	 */
	public function setName($name)
	{
		$this->_name = $name;
	}

	/**
	 * Получение полного имени элемента
	 *
	 * @return string
	 */
	public function getFullName()
	{
		return (is_string($this->_parent)) ? ($this->_parent . '['.$this->_name . ']') : $this->_name;
	}

	/**
	 * Получение родителя
	 *
	 * @return null
	 */
	public function getParent()
	{
		return $this->_parent;
	}

	/**
	 * Установка родителя
	 *
	 * @param $parent
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setParent($parent)
	{
		$this->_parent = $parent;
		return $this;
	}

	/**
	 * Установить заголовок
	 *
	 * @param $label
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setLabel($label)
	{
		$this->_label = $label;
		return $this;
	}

	/**
	 * Получить заголовк
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return htmlspecialchars($this->_label);
	}

	/**
	 * Установить класс
	 *
	 * @param $class
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setClass($class)
	{
		$this->_class = $class;
		return $this;
	}

	/**
	 * Получить класс
	 *
	 * @return string
	 */
	public function getClass()
	{
		return $this->_class;
	}

	/**
	 * Установка значения
	 *
	 * @param $value
	 * @param null $data
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setValue($value, &$data = null)
	{
		$this->_value = $value;
		return $this;
	}

	/**
	 * Установка значения из базы
	 *
	 * @param $value
	 * @param null $data
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setValueFromDB($value, &$data = null)
	{
		$this->_value = $value;
		return $this;
	}

	/**
	 * Получение значения
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * Обработка перед сохранением в базу
	 *
	 * @param null $value
	 * @param null $data
	 * @return mixed|null
	 */
	public function prepareForDB($value = null, &$data = null)
	{
		if ($value === null){
			$value = $this->getValue();
		}
		return $value;
	}

	/**
	 * Проверка валидности данных
	 *
	 * @param $value
	 * @param null $data
	 * @return bool
	 */
	public function isValid($value, &$data = null)
	{
		if ($this->getRequire()){
			if (!trim($value)){
				$this->_errors[] = self::IS_EMPTY;
				return false;
			}
		}
		return true;
	}

	/**
	 * Получение массива ошибок
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * Установка массива ошибок
	 *
	 * @param $errors
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setErrors($errors)
	{
		if (is_array($errors)){
			$this->_errors = $errors;
		}
		return $this;
	}

	/**
	 * Очистка списка ошибок
	 *
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function clearErrors()
	{
		$this->_errors = array();
		return $this;
	}

	/**
	 * Получение строки атрибутов на основе массива
	 *
	 * @param array $attributes
	 * @return string
	 */
	public function getAttributes(array $attributes)
	{
		$result = '';
		foreach ($attributes as $name => $value){
			$result .= $name . ' = "' . $value . '" ';
		}
		return $result;
	}

	/**
	 * Получение закрывающего тэга в зависимости от стиля
	 *
	 * @return string
	 */
	public function getCloseTag()
	{
		return self::$_xml_style ? '/>' : '>';
	}


	/**
	 * Получение ID элемента
	 *
	 * @return mixed
	 */
	public function getID()
	{
		return is_string($this->_id) ? $this->_id : trim(str_replace(array('[',']'),'-', str_replace('][', '-', $this->getFullName())),'-');
	}

	/**
	 * Установка ID элемента
	 *
	 * @param $id
	 * @return Coffe_LiveForm_Element_Abstract
	 */
	public function setID($id)
	{
		$this->_id = $id;
		return $this;
	}


	/**
	 * Рисование элемента
	 *
	 * @abstract
	 * @return mixed
	 */
	abstract public function render();

}
