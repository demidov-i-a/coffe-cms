<?php

/**
 * ������������
 *
 * @package coffe_cms
 */
class Coffe_View
{

	/**
	 * ���������� �� ���������
	 *
	 * @var array
	 */
	private $_dir = array();

	/**
	 * ���������
	 *
	 * @var array
	 */
	protected $_helpers = array();

	/**
	 * ������ �������
	 * @param string $file
	 * @return string
	 */
	public function render($file)
	{
		$file = trim($file);
		//���� ���� �������
		foreach ($this->_dir as $dir){
			if (file_exists($dir['path'] . $file)){
				ob_start();
				require($dir['path'] . $file);
				return ob_get_clean();
			}
		}
		throw new Coffe_Exception('The template "' . htmlspecialchars($file) . '" isn\'t found');
	}

	/**
	 * ������� ����������
	 *
	 * @return Coffe_View
	 */
	public function clearVars()
	{
		$vars   = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if ('_' != substr($key, 0, 1)) {
				unset($this->$key);
			}
		}

		return $this;
	}

	/**
	 * ��������� ������� ����������
	 *
	 * @return array
	 */
	public function getVars()
	{
		$vars   = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if ('_' == substr($key, 0, 1)) {
				unset($vars[$key]);
			}
		}
		return $vars;
	}

	/**
	 * ��������� ���������� � ���������
	 *
	 * @param $dir
	 * @param int $priority
	 * @return Coffe_View
	 */
	public function setDir($dir, $priority = 0)
	{
		$this->_dir = array();
		$this->_dir[] = array('path' => $dir,'priority' => intval($priority));
		return $this;
	}

	/**
	 * ���������� ���������� ��������
	 *
	 * @param $dir
	 * @param int $priority
	 * @return Coffe_View
	 */
	public function addDir($dir, $priority = 0)
	{
		$this->_dir[] = array('path' => $dir,'priority' => intval($priority));
		usort($this->_dir,array($this,"sortDirArray"));
		return $this;
	}

	/**
	 * ���������� ���������� �� ����������
	 *
	 * @param $a
	 * @param $b
	 * @return int
	 */
	private function sortDirArray($a, $b)
	{
		if ($a['priority'] == $b['priority']) {
			return 0;
		}
		return ($a['priority'] < $b['priority']) ? 1 : -1;
	}

	/**
	 * ��������� ���������� � ���������
	 *
	 * @return array
	 */
	public function getDir()
	{
		return $this->_dir;
	}

	/**
	 * ������� ����������
	 *
	 * @return Coffe_View
	 */
	public function clearDir()
	{
		$this->_dir = array();
		return $this;
	}

	/**
	 * ���������� ��������� ����
	 *
	 * @param $name
	 * @param $callback
	 * @return Coffe_View
	 * @throws Coffe_Exception
	 */
	public function addHelper($name, $callback)
	{
		if (!is_callable($callback)){
			throw new Coffe_Exception('Not correct callback');
		}
		if ($this->helperRegistered($name)){
			throw new Coffe_Exception('The helper is already registered');
		}
		$this->_helpers[$name] = $callback;
		return $this;
	}

	/**
	 * ���������, ��������������� �� ��������
	 *
	 * @param $helper
	 * @return bool
	 */
	public function helperRegistered($helper)
	{
		return (isset($this->_helpers[$helper])) ? true : false;
	}


	/**
	 * ��������� ����������
	 *
	 * @return array
	 */
	public function getHelpers()
	{
		return $this->_helpers;
	}

	/**
	 * ��������� ����������
	 *
	 * @param $helpers
	 * @return Coffe_View
	 */
	public function setHelpers($helpers)
	{
		$this->_helpers = (array)$helpers;
		return $this;
	}


	/**
	 * ������ ������� � ���������� �����������
	 *
	 * @param $name
	 * @param array $values
	 * @return string
	 * @throws Coffe_Exception
	 */
	public function partial($name, $values = array())
	{
		$view = clone $this;
		$view->clearVars();
		if (is_array($values)){
			$error = false;
			foreach ($values as $key => $val) {
				if ('_' == substr($key, 0, 1)) {
					$error = true;
					break;
				}
				$view->$key = $val;
			}
			if ($error) {
				throw new Coffe_Exception('Setting private or protected class members is not allowed');
			}
		}
		return $view->render($name);
	}


	/**
	 * ����� ��������� ����
	 *
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws Coffe_Exception
	 */
	public function __call($name, $arguments) {
		if (isset($this->_helpers[$name])){
			return call_user_func_array($this->_helpers[$name] , $arguments);
		}
		throw new Coffe_Exception('The helper "' . $name . '" isn\'t found');
	}

	/**
	 * ������ print_r ���� ����������
	 *
	 * @return string
	 */
	public function debug()
	{
		$vars  = get_object_vars($this);
		$debug = '';
		foreach ($vars as $key => $value) {
			if (substr($key, 0, 1) != '_') {
				$debug .= '<div style="margin:10px 0; padding: 0 10px; background: #e2e2e2; border: 1px solid #afafaf">';
				$debug .= '<pre>';
				$debug .= '<b>' . $key .  '</b>' . PHP_EOL ;
				$debug .= print_r($value, true) . PHP_EOL;
				$debug .= '</pre>';
				$debug .= '</div>';
			}
		}
		return $debug;
	}
}