<?php
/*
 * ����� ������ � ��������� �������
 *
 * @package coffe_cms
*/

class Coffe_Translate
{

	/**
	 * ������� ����
	 *
	 * @var string
	 */
	protected static $lang = "ru";


	/**
	 * ���� �� ���������
	 *
	 * @var string
	 */
	protected static $default_lang = "ru";


	/**
	 * ����� ��� ����� �� ���������
	 *
	 * @var array
	 */
	public $default_buffer = array();

	/**
	 * ����� ��� �������� �����
	 *
	 * @var array
	 */
	public $buffer = array();

	/**
	 * ����������� �����
	 *
	 * @var array
	 */
	protected $loaded_files = array();

	/**
	 * ������� ����
	 *
	 * @var null
	 */
	protected $current_path = null;



	/**
	 * ��������� ������� ����������� ������
	 *
	 * @return array
	 */
	public function getLoadedFiles()
	{
		return $this->loaded_files;
	}

	/**
	 * ������� ������� ����������� ������
	 *
	 * @return Coffe_Translate
	 */
	public function clearLoadedFiles()
	{
		$this->loaded_files = array();
		return $this;
	}

	/**
	 * ��������� �������� ���� ������ ���������� �������
	 *
	 * @param $path
	 * @param bool $force
	 * @return Coffe_Translate
	 */
	public function loadFile($path, $force = false)
	{
		$ext = Coffe_Functions::getFileExt($path);
		switch($ext){
			case 'xml': $this->loadXmlFile($path, $force); break;
		}
		return $this;
	}


	/**
	 * ��������� xml - ����
	 *
	 * @param $path
	 * @return Coffe_Translate
	 */
	public function loadXmlFile($path)
	{
		$path = realpath($path);
		//���� ��� ��� ��������
		if (in_array($path, $this->loaded_files)){
			$this->current_path = $path;
			return $this;
		}
		if (is_file($path) && is_readable($path)){
			$array = @simplexml_load_file($path);
			if (is_object($array) && count($array->data)){
				$default = null; $current = null;
				foreach($array->data->children() as $name){
					if ($name['id'] == self::$default_lang){
						$default = $name;
					}
					if ($name['id'] == self::$lang){
						$current = $name;
					}
				}
				//���� �� ���������
				if (is_object($default)){
					$this->xmlSectionToBuffer($default, $this->buffer, $path);
				}
				//������� ����
				if (is_object($current)){
					$this->xmlSectionToBuffer($current, $this->buffer, $path);
				}
			}
			$this->loaded_files[] = $path;
			$this->current_path = $path;
		}
		return $this;
	}

	/**
	 * ��������� �������� xml-������ � �����
	 *
	 * @param $section
	 * @param $buffer
	 * @param $path
	 */
	private function xmlSectionToBuffer($section, &$buffer, $path)
	{

		$children = @$section->children();
		if (is_object($children)){
			foreach ($section->children() as $label){
				$id =  (string)$label['id'];
				$buffer[$path][$id]  = (string)$label;
			}
		}
	}

	/**
	 * ��������� �������� �����
	 *
	 * @param $lang
	 * @return Coffe_Translate
	 */
	public function setLang($lang)
	{
		self::$lang = trim($lang);
		return $this;
	}

	/**
	 * ��������� �������� �����
	 *
	 * @return string
	 */
	public function getLang()
	{
		return self::$lang;
	}

	/**
	 * ��������� ����� �� ���������
	 *
	 * @param $lang
	 * @return Coffe_Translate
	 */
	public function setDefaultLang($lang)
	{
		self::$default_lang = trim($lang);
		return $this;
	}

	/**
	 * ��������� ����� �� ���������
	 *
	 * @return string
	 */
	public function getDefaultLang()
	{
		return self::$default_lang;
	}

	/**
	 * ������� ����� �������� �����
	 *
	 * @return Coffe_Translate
	 */
	public function clearBuffer()
	{
		$this->buffer = array();
		return $this;
	}

	/**
	 * ������� ����� ����� �� ���������
	 *
	 * @return Coffe_Translate
	 */
	public function clearDefaultBuffer()
	{
		$this->default_buffer = array();
		return $this;
	}

	/**
	 * ������ �������
	 *
	 * @return Coffe_Translate
	 */
	public function clearAll()
	{
		$this->clearBuffer();
		$this->clearDefaultBuffer();
		return $this;
	}

	/**
	 * ��������� �������� �������� ����������
	 *
	 * @param $name
	 * @param null $marker_array
	 * @param string $default
	 * @return mixed
	 */
	public function get($name, $marker_array = null, $default = '')
	{
		$msg = false;
		if (substr($name, 0, 5) == 'LANG:'){
			$name = substr($name, 5);
			$parts = explode(';', $name);
			//������ ���� � �����, ��������� ���
			if (count($parts) > 1){
				$this->loadFile(Coffe_Functions::makePath($parts[0]));
				$name = $parts[1];
			}
		}
		//�������� ����� � ��������� ����������� �����
		if ($this->current_path !== null){
			if (isset($this->buffer[$this->current_path][$name])){
				$msg = $this->buffer[$this->current_path][$name];
			}
			if (isset($this->default_buffer[$this->current_path][$name])){
				$msg = $this->default_buffer[$this->current_path][$name];
			}
		}
		//���� � ������ �������� �����
		if ($msg === false){ $msg = $this->findInBuffer($this->buffer, $name);}
		//���� � ������ ����� �� ���������
		if ($msg === false){ $msg = $this->findInBuffer($this->default_buffer, $name);}
		if ($msg === false) $msg = $default;
		return $this->returnStr($msg, $marker_array);
	}

	/**
	 * ����� � ������ �� �����
	 *
	 * @static
	 * @param $input
	 * @param $name
	 * @return bool
	 */
	protected static function findInBuffer($input, $name)
	{
		$list = array_reverse($input);
		foreach ($list as $buffer){
			if (isset($buffer[$name])){
				return $buffer[$name];
			}
		}
		return false;
	}

	/**
	 * ��������� ����������� �������� � ������
	 *
	 * @param $str
	 * @param $marker_array
	 * @return mixed
	 */
	private function returnStr($str, $marker_array)
	{
		return is_array($marker_array) ? Coffe_Functions::strReplaceMarkers($str, $marker_array) : $str;
	}


}
