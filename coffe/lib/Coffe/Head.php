<?php

/**
 * Генерация шапки
 *
 * @package coffe_cms
 */
class Coffe_Head
{
	/**
	 * Базовый заголовк сайта
	 *
	 * @var null
	 */
	protected $title_base = null;

	/**
	 * Заголовок сайта
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * Разделить заголовка и основного заголовка сайта
	 *
	 * @var string
	 */
	protected $title_delimiter = ': ';

	/**
	 * Стиль xml
	 *
	 * @var bool
	 */
	protected $xml_style = true;

	/**
	 * Массив данных для вывода
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * @var Coffe_Head
	 */
	private static $instance = null;

	/**
	 * Установка xml - стиля
	 *
	 * @param $xml
	 * @return Coffe_Head
	 */
	public function setXmlStyle($xml)
	{
		$this->xml_style = $xml ? true : false;
		return $this;
	}

	/**
	 * Устанавливает новый заголовок сайта
	 *
	 * @param string $title заголовок
	 * @return Coffe_Head
	 */
	public function	setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * Возвращает заголовок
	 *
	 * @return string
	 */
	public function	getTitle()
	{
		return $this->title;
	}

	/**
	 * Установка базового заголовка
	 *
	 * @param $title
	 * @return Coffe_Head
	 */
	public function setBaseTitle($title)
	{
		$this->title_base = $title;
		return $this;
	}

	/**
	 * Получение базового заголовка
	 *
	 * @return string
	 */
	public function getBaseTitle()
	{
		return $this->title_base;
	}

	/**
	 * Получение разделителя для заголовка
	 *
	 * @return string
	 */
	public function	getTitleDelimiter()
	{
		return $this->title_delimiter;
	}

	/**
	 * Установка разделителя для заголовка
	 *
	 * @param $dilimetr
	 * @return Coffe_Head
	 */
	public function	setTitleDelimiter($delimiter)
	{
		$this->title_delimiter = $delimiter;
		return $this;
	}

	/**
	 * Вывод заголовка
	 *
	 * @return string
	 */
	public function renderTitle()
	{
		return '<title>' . (trim($this->title_base)
			? ((trim($this->title)) ? ($this->title_base . $this->title_delimiter . $this->title) : $this->title_base)
			: $this->title
		) . '</title>' . PHP_EOL;
	}

	/**
	 * Добавление css-файла
	 *
	 * @param $id
	 * @param $href
	 * @param string $media
	 * @param array $attributes
	 * @return Coffe_Head
	 */
	function addCssFile($id, $href, $media = 'all', $attributes = array('type' => 'text/css', 'rel' => 'stylesheet'))
	{
		$attributes['media'] = $media;
		$attributes['href'] = $href;
		$this->data[$id] = '<link ' . $this->getAttributes($attributes) . $this->getCloseTag() . PHP_EOL;
		return $this;
	}

	/**
	 * Добавление js-файла
	 *
	 * @param $id
	 * @param $src
	 * @param array $attributes
	 * @return Coffe_Head
	 */
	function addJsFile($id, $src, $content = '', $attributes = array('type' => 'text/javascript'))
	{
		if (!empty($src))
			$attributes['src'] = $src;
		$this->data[$id] = '<script ' . $this->getAttributes($attributes) .'>' . $content . '</script>' . PHP_EOL;
		return $this;
	}


	/**
	 * Добавление данных
	 *
	 * @param $id
	 * @param $content
	 * @return Coffe_Head
	 */
	public function addData($id, $content)
	{
		$this->data[$id] = $content;
		return $this;
	}

	/**
	 * Получение данных
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Установка данных
	 *
	 * @param $data
	 * @return Coffe_Head
	 */
	public function setData($data)
	{
		$this->data = (array)$data;
		return $this;
	}

	/**
	 * Вывод шапки
	 *
	 * @return string
	 */
	public function renderHead()
	{
		$head = $this->renderTitle();
		foreach ($this->data as $data){
			$head .= $data . PHP_EOL;
		}
		return $head;
	}

	/**
	 * Получение атрибутов
	 *
	 * @param $attributes
	 * @return string
	 */
	public function getAttributes($attributes)
	{
		$result = '';
		$attributes = (array)$attributes;
		foreach ($attributes as $name => $value){
			$result .= $name . '="' . htmlspecialchars($value) .'" ';
		}
		return $result;
	}

	/**
	 * Получение закрывающего тэга
	 *
	 * @return string
	 */
	public function getCloseTag()
	{
		return ($this->xml_style) ? '/>' : '>';
	}

	/**
	 * Очистка всех элементов по ID
	 *
	 * @param $id
	 * @return Coffe_Head
	 */
	public function clearById($id)
	{
		unset($this->data[$id]);
		return $this;
	}

	/**
	 * Полная очистка
	 * @return Coffe_Head
	 */
	public function resetAll()
	{
		$this->data = array();
		return $this;
	}


	/**
	 * Заполнение из массива
	 *
	 * @param $head
	 * @return mixed
	 */
	public function init($head)
	{
		if (is_array($head)){
			if (isset($head['js'])){
				foreach ($head['js'] as $id => $data){
					$this->addJsFile($id, $data);
				}
			}

			if (isset($head['css'])){
				foreach ($head['css'] as $id => $data){
					$this->addCssFile($id, $data);
				}
			}

			if (isset($head['basetitle'])){
				$this->title_base = $head['basetitle'];
			}

			if (isset($head['data']) && is_array($head['data'])){
				foreach ($head['data'] as $id => $data){
					$this->addData($id, $data);
				}
			}

			if (isset($head['xml_style'])){
				self::setXmlStyle($head['xml_style']);
			}
		}

	}

	/**
	 * Создает объект из серилиазованной строки
	 *
	 * @static
	 * @param $string
	 * @throws Coffe_Exception
	 */
	public static function createFormSerialize($string)
	{
		$obj = unserialize($string);
		if (is_object($obj) && ($obj instanceof Coffe_Head)){
			self::$instance = $obj;
		}
		else{
			throw new Coffe_Exception('The string shall be the serialized object of Coffe_Head');
		}
	}

	/**
	 * Получение единственного экземпляра класса
	 *
	 * @static
	 * @return Coffe_Head
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Переопределяем для реализации Singleton
	 */
	private function __construct(){}

}
