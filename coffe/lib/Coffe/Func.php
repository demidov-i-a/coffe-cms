<?php
/**
 * Функции
 *
 * Различные вспомогательные функции
 */
class Coffe_Func
{

	/**
	 * @var Coffe_Icon
	 */
	protected static $icon = null;


	/**
	 * Обрезка строки
	 *
	 * @static
	 * @param $str
	 * @param $maxLen
	 * @param bool $remove_tags
	 * @param string $add
	 * @param string $charset
	 * @return string
	 */
	public static function cutStr($str, $maxLen, $remove_tags = true, $add = '...', $charset = 'UTF-8')
	{
		if ($remove_tags){
			$str = strip_tags($str);
		}
		if(strlen($str) > $maxLen){
			preg_match('/^.{0,'.$maxLen.'} .*?/is', $str, $match);
			$result = $match[0] . $add;
		}else{
			$result = $str;
		}
		return $result;
	}

	/**
	 * Преобразают строку в валидный url адрес
	 *
	 * @static
	 * @param $str
	 * @param string $charset
	 * @return mixed
	 */
	public static function strToUrl($str, $charset = 'UTF-8')
	{
		if ($charset != 'UTF-8'){
			$str = iconv($charset, 'UTF-8', $str);
		}
		$tr = array(
			"А"=>"a","Б"=>"b","В"=>"v","Г"=>"g",
			"Д"=>"d","Е"=>"e","Ж"=>"j","З"=>"z","И"=>"i",
			"Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n",
			"О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t",
			"У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch",
			"Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"yi","Ь"=>"",
			"Э"=>"e","Ю"=>"yu","Я"=>"ya","а"=>"a","б"=>"b",
			"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
			"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
			"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
			"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
			"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
			"ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
			" "=> "_", "."=> "", "/"=> "_"
		);
		$tmp = strtr($str,$tr);

		return preg_replace('/[^A-Za-z0-9_\-]/', '', $tmp);
	}

	/**
	 * Рекурсивное удаление пустых элементов массива
	 *
	 * @static
	 * @param array $array
	 * @param bool $recursive
	 * @param int $count количество не пустых элементов
	 */
	public static function clearEmptyArray(&$array, $recursive = true, &$count = 0)
	{
		if (is_array($array)){
			foreach ($array as $key => $element){
				if (empty($element) && !is_numeric($element)){
					unset($array[$key]);
				}
				else{
					$count++;
				}
				if (is_array($element) && $recursive){
					self::clearEmptyArray($array[$key], true,$count);
				}
			}
		}
	}

	/**
	 * Рекурсивный trim элементов массива
	 *
	 * @param array $array
	 * @param bool $recursive
	 */
	public static function trimArray(&$array, $recursive = true)
	{
		if (is_array($array)){
			foreach ($array as &$element){
				if (is_string($element)){
					$element = trim($element);
				}
				if ($recursive && is_array($element)){
					self::trimArray($element, true);
				}
			}
		}
	}

	/**
	 * Разбивает сроку на массив
	 *
	 * @static
	 * @param $delim
	 * @param $string
	 * @param bool $removeEmptyValues
	 * @param int $limit
	 * @return array
	 */
	public static function trimExplode($delim, $string, $removeEmptyValues = FALSE, $limit = 0) {
		$explodedValues = explode($delim, $string);
		$result = array_map('trim', $explodedValues);
		if ($removeEmptyValues) {
			$temp = array();
			foreach ($result as $value) {
				if ($value !== '') {
					$temp[] = $value;
				}
			}
			$result = $temp;
		}
		if ($limit != 0) {
			if ($limit < 0) {
				$result = array_slice($result, 0, $limit);
			} elseif (count($result) > $limit) {
				$lastElements = array_slice($result, $limit - 1);
				$result = array_slice($result, 0, $limit - 1);
				$result[] = implode($delim, $lastElements);
			}
		}
		return $result;
	}


	/**
	 * Рекурсивный stripslashes элементов массива
	 *
	 * @param array $array
	 * @param bool $recursive
	 */
	public static function stripArray(array &$array, $recursive = true)
	{
		foreach ($array as &$element){
			if (is_string($element)){
				$element = stripslashes($element);
			}
			if ($recursive && is_array($element)){
				self::stripArray($element, true);
			}
		}
	}

	/**
	 * Проверяет валидность Email
	 * @static
	 * @param $mail
	 * @return mixed
	 */
	public static function validateEmail($mail)
	{
		return filter_var($mail, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Функция получения IP клиента
	 *
	 * @static
	 * @return mixed
	 */
	public static function getClientIp()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP'])){
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else{
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	/**
	 * Проводит замену маркеров в тексте
	 *
	 * @static
	 * @param $str исходный текст
	 * @param array $markers массив маркеров
	 * @return mixed
	 */
	public static function strReplaceMarkers($str ,$markers)
	{
		if (is_array($markers)){
			foreach ($markers as $marker => $value){
				$str = str_replace('###' . $marker . '###', $value, $str);
			}
		}
		return $str;
	}

	/**
	 * Заменяет в строке сокращенный путь к модулю на реальный
	 *
	 * @static
	 * @param $matches
	 * @return bool|string
	 */
	static private function pregReplaceModule($matches)
	{
		$matches[1] = trim($matches[1],'/');
		$matches[1] = trim($matches[1],'\\');
		return Coffe_ModuleManager::getModuleAbsPath($matches[1]);
	}

	/**
	 * Выдает реальный путь к файлу
	 *
	 * @static
	 * @param $path
	 * @return mixed
	 */
	public static function makePath($path)
	{
		//заменяем пути к модулям
		$path = preg_replace_callback('#MODULE\:([^\/]+[\\\/]*)#s',array('Coffe_Func','pregReplaceModule'), $path);
		$path = preg_replace('#PATH_ROOT\:#s',PATH_ROOT, $path);
		$path = preg_replace('#PATH_LIB\:#s',PATH_LIB, $path);
		$path = preg_replace('#PATH_COFFE\:#s',PATH_COFFE, $path);
		$path = preg_replace('#PATH_CP\:#s',PATH_CP, $path);
		return $path;
	}

	/**
	 * Выполнение пользовательской callback функции
	 *
	 * @static
	 * @param $callback
	 * @param array $params
	 * @return mixed
	 * @throws Coffe_Exception
	 */
	public static function execCallBack($callback, $params = array())
	{
		if (is_callable($callback)){
			return call_user_func_array($callback, $params);
		}
		$function = self::explodeFunctionPath($callback);
		if (!isset($function['file']) || !is_file($function['file'])){
			throw new Coffe_Exception('The callback file isn\'t found');
		}
		if (!isset($function['function'])){
			throw new Coffe_Exception('The callback function isn\'t found');
		}
		//функция в классе
		if (isset($function['class'])){
			if (!class_exists($function['class'])){
				require_once($function['file']);
			}
			if (method_exists($function['class'], 'getInstance')){
				$obj = call_user_func(array($function['class'], 'getInstance'));
			}
			else{
				$obj = new $function['class'];
			}
			return call_user_func_array(array($obj, $function['function']), $params);
		}
		//фукнция в файле
		else{
			require_once($function['file']);
			return call_user_func_array($function['function'], $params);
		}
	}

	/**
	 * Разбивает путь к функции на части
	 *
	 * Пример MODULE:pages/lib/class.ajax.php;ajax->run.
	 * Возвращает массив, где возможные ключи - function, file, class
	 *
	 * @static
	 * @param $path
	 * @return array
	 */
	public static function explodeFunctionPath($path)
	{
		$function = array();
		$path = self::makePath($path);
		$parts = explode(';', $path);
		if (count($parts)){
			$function['file'] = array_shift($parts);
		}
		if (count($parts)){
			$class = array_shift($parts);
			$parts = explode('->',$class);
			if (count($parts) == 1){
				$function['function'] = $parts[0];
			}
			elseif(count($parts) > 1){
				$function['class'] = $parts[0];
				$function['function'] = $parts[1];
			}
		}
		return $function;
	}

	/**
	 * Получение иконки на основе строки вида dir;name
	 *
	 * @static
	 * @param $icon
	 * @param string $alt
	 * @param null $title
	 * @param null $width
	 * @param null $height
	 * @param array $attributes
	 * @return string
	 */
	public static function getIcon($icon, $alt = '', $title = null, $width = null, $height = null, $attributes = array())
	{
		if (self::$icon === null){
			self::$icon = new Coffe_Icon();
		}
		$parts = explode(';',$icon);
		if (count($parts) == 2){
			$cur_dir_arr = self::$icon->getDirs();
			$new_dir = self::makePath($parts[0]);
			if (!in_array($new_dir, $cur_dir_arr)){
				self::$icon->setDirsArray(array($new_dir));
				self::$icon->clearCache();
			}
			return self::$icon->getIcon($parts[1],$alt, $title, $width, $height, $attributes);
		}
		return self::$icon->getIcon($icon, $alt, $title, $width, $height, $attributes);
	}


	/**
	 * Возвращает абсолютный url префикс
	 *
	 * @static
	 * @return string
	 */
	public static function getAbsPrefixUrl()
	{
		$base_url = Coffe::getUrlPrefix();
		if (trim($base_url) && (trim($base_url,'/') != ''))
			return '/' . trim($base_url, '/') . '/';
		return '/';
	}

	/**
	 * Проходит по массиву и рекурсивно формирует в нем языковые переменные
	 *
	 * @static
	 * @param $array
	 * @param null $lang
	 */
	public static function parseLangInArray(&$array, $lang = null)
	{
		$lang = ($lang instanceof Coffe_Translate) ? $lang : $GLOBALS['LANG'];
		if (is_array($array))
			array_walk_recursive($array, array('Coffe_Func','parseLangRecursive'),$lang);
	}

	/**
	 * Заменяет элемент в массиве языковой переменной
	 *
	 * @static
	 * @param $item
	 * @param $key
	 * @param Coffe_Translate $lang
	 */
	private static function parseLangRecursive(&$item, $key, Coffe_Translate $lang)
	{
		if (is_string($item) && strpos($item, 'LANG:') === 0){
			$item = $lang->get($item);
		}
	}

	/**
	 * Отдает расширение файла
	 *
	 * @static
	 * @param $path
	 * @return mixed
	 */
	public static function getFileExt($path)
	{
		$info = pathinfo($path, PATHINFO_EXTENSION);
		return isset($info) ? strtolower($info) : '';
	}


}