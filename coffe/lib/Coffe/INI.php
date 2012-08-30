<?php

/**
 * Парсер INI файлов
 *
 * @package coffe_cms
 */


class Coffe_INI
{
	/**
	 * Массив конфигупации
	 *
	 * @var array
	 */
	protected $ini = array();

	/**
	 * Получение конфигурации
	 */
	public function getIni()
	{
		return $this->ini;
	}

	/**
	 * Установка ini
	 *
	 * @param array $ini
	 * @return Coffe_INI
	 */
	public function setIni(array $ini)
	{
		if (is_array($ini)){
			$this->ini = $ini;
		}
		return $this;
	}

	/**
	 * Очистка INI
	 *
	 * @return Coffe_INI
	 */
	public function clearIni()
	{
		$this->ini = array();
		return $this;
	}

	/**
	 * Чтение ini файла
	 *
	 * @param $filename
	 * @return Coffe_INI
	 * @throws Coffe_Exception
	 */
	public function readFile($filename)
	{
		if (!file_exists($filename))
			throw new Coffe_Exception('Ini: the file '. htmlspecialchars($filename) . 'is not found');
		$lines = @file($filename);
		if (is_array($lines))
			$this->parseLines($lines);
		return $this;
	}

	/**
	 * Чтение данных из строки
	 *
	 * @param $string
	 * @param string $deimiter
	 * @return Coffe_INI
	 */
	public function readString($string , $deimiter = null)
	{
		if ($deimiter === null){
			$lines = preg_split('#\\r\\n?|\\n#', $string);;
		}
		else{
			$lines = explode($deimiter, $string);
		}
		$this->parseLines($lines);
		return $this;
	}


	/**
	 * Разбирает массив строк
	 *
	 * @param $lines
	 * @return Coffe_INI
	 */
	private function parseLines($lines)
	{
		if (is_array($lines)){
			$multi = false;
			$tmp = '';
			$key = '';
			foreach($lines as $line) {
				$line = trim($line);
				//строки с комментариями пропускаем
				if ((substr($line, 0, 1) == ';') || (substr($line, 0, 1) == '#')) continue;
				if (!$multi){
					if (preg_match('/^([\@a-z0-9_.\[\]-]+)\s*\=\s*(.*)$/i', $line, $out)) {
						$key = $out[1];
						$val = $out[2];
						if (substr($val, 0, 1) !== "(" || (substr(rtrim($val), -1) == ")")) {
							$this->manage_keys($this->ini,$key, $val);
						} else {
							$multi = true;
							$tmp = substr($val, 1). PHP_EOL;
						}
					}
				}
				else{
					if (substr($line, -1) != ")"){
						$tmp .= $line. PHP_EOL;
					}
					else
					{
						$tmp .= substr($line, 0, -1);
						$this->manage_keys($this->ini, $key, $tmp);
						$tmp = '';
						$multi = false;
					}
				}
			}
		}
		return $this;
	}

	/**
	 * Формирует массив параметров
	 *
	 * @param $ini
	 * @param $key
	 * @param $val
	 */
	private function manage_keys(&$ini, $key, $val)
	{
		if (preg_match('/^([\@a-z0-9_-]+)\.(.*)$/i', $key, $m)) {
			$this->manage_keys($ini[$m[1]], $m[2], $val);
		} else if (preg_match('/^([\@a-z0-9_-]+)\[(.*)\]$/i', $key, $m)) {
			if ($m[2] !== '') {
				$ini[$m[1]][$this->get_key($m[2])] = $this->get_value($val);
			} else {
				$ini[$m[1]][] = $this->get_value($val);
			}
		} else {
			$ini[$this->get_key($key)] = $this->get_value($val);
		}
	}


	/**
	 * Получение значения ключа
	 *
	 * @param $val
	 * @return int|string
	 */
	private function get_key($val)
	{
		if (preg_match('/^[0-9]$/i', $val)) { return intval($val); }
		return $val;
	}

	/**
	 * Получение значения
	 *
	 * @param $val
	 * @return bool|int|string
	 */
	private function get_value($val)
	{
		if (preg_match('/^-?[0-9]$/i', $val)) { return intval($val); }
		else if (strtolower($val) === 'true') { return true; }
		else if (strtolower($val) === 'false') { return false; }
		else if (preg_match('/^"(.*)"$/i', $val, $m)) { return $m[1]; }
		else if (preg_match('/^\'(.*)\'$/i', $val, $m)) { return $m[1]; }
		return $val;
	}

}