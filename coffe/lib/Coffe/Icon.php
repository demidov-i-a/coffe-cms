<?php

/**
 * ����� ��� ������ � ��������
 *
 * @package coffe_cms
 */
class Coffe_Icon
{

	/**
	 * ������ ���������� ��� ������ ������
	 *
	 * @var array
	 */
	protected $dirs = array();

	/**
	 * ��� ������
	 *
	 * @var
	 */
	protected $cache = array();

	/**
	 * ���������� ������ ����������
	 *
	 * @return array
	 */
	public function getDirs()
	{
		return $this->dirs;
	}

	/**
	 * ������������� ������ ����������
	 *
	 * @param $arr
	 * @return Coffe_Icon
	 */
	public function setDirsArray($arr)
	{
		$this->dirs = (array)$arr;
		return $this;
	}

	/**
	 * ��������� ���������� ��� ������
	 *
	 * @param $dir
	 * @return Coffe_Icon
	 */
	public function addDir($dir)
	{
		if (is_string($dir) && !in_array($dir, $this->dirs)){
			$this->dirs[] = $dir;
		}
		return $this;
	}



	public function getIcon($name, $alt = '', $title = null, $width = null, $height = null, $attributes = array())
	{
		$src = $this->getIconSrc($name);
		if (!$src) return '';
		$attributes['src'] = $src;
		$attributes['alt'] = $alt;
		$attributes['title'] = ($title !== null) ? $title : $attributes['alt'];
		if ($width !== null){
			$attributes['width'] = $width;
		}
		if ($height !== null){
			$attributes['height'] = $height;
		}
		return '<img '. $this->getAttributes($attributes) . '/>';
	}

	/**
	 * ��������� ������ ��������� �� ������ �������
	 *
	 * @param array $attributes
	 * @return string
	 */
	public function getAttributes(array $attributes)
	{
		$result = '';
		foreach ($attributes as $name => $value){
			$result .= $name . ' = "' . $value .'" ';
		}
		return $result;
	}

	/**
	 * �������� src ��� ������
	 *
	 * @param $name
	 * @return mixed
	 */
	public function getIconSrc($name)
	{
		$name = trim($name);

		if (isset($this->cache[$name])){
			return $this->cache[$name];
		}
		$parts = explode('/', $name);
		$sub_dir = '';
		$icon = $name;
		if (count($parts) > 1){
			$icon = $parts[count($parts) - 1];
			$sub_dir = substr($name, 0, strrpos($name, '/') + 1);
		}
		if (strrpos($icon, '.'))
			$pattern = $icon;
		else
			$pattern = $icon. '.*';


		foreach ($this->dirs as $dir){
			if (is_dir(PATH_ROOT . $dir)){
				foreach (glob(PATH_ROOT . $dir . $sub_dir. $pattern) as $filename) {
					$this->cache[$name] = Coffe::getUrlPrefix() . $dir . $sub_dir . basename($filename);
					return $this->cache[$name];
				}
			}
		}
		return null;
	}

	/**
	 * ������� ���� ������
	 */
	public function clearCache()
	{
		$this->cache = array();

	}
}