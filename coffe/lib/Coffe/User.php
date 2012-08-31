<?php

/**
 * Работа с пользователем
 *
 * @package coffe_cms
 */
class Coffe_User
{

	/**
	 * Таблица с пользователями
	 *
	 * @var string
	 */
	protected $table = 'user';


	/**
	 * Таблица с группами
	 *
	 * @var string
	 */
	protected $table_group = 'user_group';

	/**
	 * Таблица с данными сессий пользователя
	 *
	 * @var string
	 */
	protected $table_session = 'session_data';


	/**
	 * Колонка в базе с id пользователя
	 *
	 * @var string
	 */
	protected $id_column = 'uid';


	/**
	 * Колонка в базе с полем логина
	 *
	 * @var string
	 */
	protected $login_column = 'username';

	/**
	 * Колонка в базе с полем запоминания пользователя
	 *
	 * @var string
	 */
	protected $remember_column = 'remember';

	/**
	 * Колонка в базе с полем пароля
	 *
	 * @var string
	 */
	protected $password_column = 'password';

	/**
	 * Имя куки "запомнить меня"
	 *
	 * @var string
	 */
	protected $remember_cookie_name = 'COFFE_USER_TOKEN';

	/**
	 * Время жизни куки "запомнить меня"
	 *
	 * @var int
	 */
	protected $remember_live_time = 2592000;

	/**
	 * Ключ в массиве _SESSION
	 *
	 * @var string
	 */
	protected $session_key = 'user';

	/**
	 * Текущий авторизованный пользователь
	 *
	 * @var array|null
	 */
	protected $user = null;

	/**
	 * @var Coffe_User
	 */
	protected static $instance = null;

	/**
	 * Разрешить запоминание в куках
	 *
	 * @var bool
	 */
	protected $allow_remember = true;

	/**
	 * Дополнительное условие для таблицы пользователей
	 *
	 * @var string
	 */
	protected $add_where = ' AND NOT `disable`';


	/**
	 * Автоматически сохранять постоянные данные пользователя в базе
	 *
	 * @var bool
	 */
	protected $auto_save_permanent_data = true;

	/**
	 * Коды и расшифровка ошибок
	 *
	 * @var array
	 */
	protected $errors = array(
		'1' => 'Login required',
		'2' => 'Password required',
		'3' => 'The user isn\'t found',
		'4' => 'Not a valid username and password',
	);

	/**
	 * @var Coffe_DB_Abstract
	 */
	private $db = null;


	/**
	 * Проверяет авторизацию пользователя
	 */
	public function start()
	{
		//если авторизованы, выбираем пользователя из базы по id
		if ($this->isLogin()){
			$res = $this->db->select('*', $this->table, $this->id_column . " = '"
				. $this->db->escapeString($_SESSION[$this->session_key][$this->id_column]). "' " . $this->add_where);
			if ($user = $this->db->fetch($res)){
				$this->user = $user;
			}
			//не нашли пользователя в базе
			else{
				$this->clear();
			}
		}
		elseif($this->allow_remember){
			$this->checkRemember();
		}
	}

	/**
	 * Получение данных текущего авторизованого пользователя
	 *
	 * @return array|null
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Получение данных текущего авторизованого пользователя
	 *
	 * @static
	 * @return array|null
	 */
	public static function get()
	{
		return self::getInstance()->getUser();
	}

	/**
	 * Очистка сессии пользователя
	 */
	public function clear()
	{
		unset($this->user);
		unset($_SESSION[$this->session_key]);
		$this->forgot();
	}

	/**
	 * Авторизован ли пользователь
	 *
	 * @return bool
	 */
	public function isLogin()
	{
		return (isset($_SESSION[$this->session_key]) && is_array($_SESSION[$this->session_key]) && isset($_SESSION[$this->session_key][$this->id_column])) ? true : false;
	}

	/**
	 * Авторизация пользователя
	 *
	 * @param $login
	 * @param $password
	 * @param $error_code
	 * @return bool
	 */
	public function authorize($login, $password, &$error_code = null)
	{
		$this->clear();
		$login = trim($login); $password = trim($password);
		if (empty($login)) {$error_code = '1'; return false;}
		if (empty($password)) {$error_code = '2'; return false;}
		$res = $this->db->select('*', $this->table, 'LOWER(' . $this->login_column . ") = '" . $this->db->escapeString(strtolower($login)). "' " . $this->add_where);
		$user = $this->db->fetch($res);
		if (!$user){$error_code = '3'; return false;}
		//hook
		if (Coffe_Event::isRegister('User.authorize')){
			$result = Coffe_Event::callLast('User.authorize', array($user, $login, $password));
			if (!$result){
				$error_code = '4'; return false;
			}
		}
		else{
			if ($user[$this->password_column] != $password){
				$error_code = '4'; return false;
			}
		}
		$this->createSession($user);
		$this->recoverPermanentData();
		return true;
	}

	/**
	 * Получение хеша пароля
	 *
	 * @param $user
	 * @param $login
	 * @param $password
	 * @return mixed|string
	 */
	private function getPasswordHash($user, $login, $password)
	{
		//Hook
		if (Coffe_Event::isRegister('User.getPasswordHash')){
			$result = Coffe_Event::callLast('User.getPasswordHash',array($password, $login, $user));
			if (trim($result)) return $result;
		}
		return $password;
	}

	/**
	 * Авторизация пользователя по ID
	 *
	 * @param $uid
	 * @param $error_code
	 * @return bool
	 */
	public function authorizeById($uid, &$error_code)
	{
		$this->clear();
		$uid = trim($uid);
		$res = $this->db->select('*', $this->table, $this->id_column . " = '" . $this->db->escapeString($uid). "' " . $this->add_where);
		$user = $this->db->fetch($res);
		if (!$user){$error_code = '3'; return false;}
		$this->createSession($user);
		$this->recoverPermanentData();
		return true;
	}

	/**
	 * Создание сессии
	 *
	 * @param array $user
	 */
	private function createSession(array $user)
	{
		$_SESSION[$this->session_key] = array();
		$_SESSION[$this->session_key]['uid'] = $user[$this->id_column];
		$this->user = $user;
		Coffe_Event::call('User.afterAuthorization',array(&$this));
	}

	/**
	 * Запомнить текущего авторизованного пользователя
	 *
	 * @return bool
	 */
	public function remember()
	{
		if ($this->allow_remember && $this->isLogin()){
			$token = $this->getRememberToken();
			setcookie($this->remember_cookie_name, $token, time() + $this->remember_live_time);
			$this->db->update($this->table, array($this->remember_column => $token),$this->id_column . " = '" . $this->db->escapeString($this->user[$this->id_column]). "'");
			return true;
		}
		return false;
	}

	/**
	 * Получение хеша для запоминания пользователя
	 *
	 * @return string
	 */
	protected function getRememberToken()
	{
		return md5($this->user[$this->id_column]. md5($this->user[$this->password_column]. Coffe_Func::getClientIp()));
	}

	/**
	 * Получение списка пользователей
	 *
	 * @param bool $not_disable
	 * @param null $filter
	 * @param string $add_where
	 * @return array|bool
	 */
	public function getList($not_disable = true, $filter = null, $add_where = '')
	{
		$where = '1';
		$where .= $not_disable ? ' AND NOT disable' : '';
		$where .= $add_where ? (' AND ' . $add_where) : '';
		$res = $this->db->select('*', $this->table, $where);
		return $this->db->fetchAll($res);
	}

	/**
	 * Вспомнить пользователя
	 *
	 * @return bool
	 */
	public function checkRemember()
	{
		if (!$this->isLogin()){
			if ($this->allow_remember && isset($_COOKIE[$this->remember_cookie_name])){
				$res = $this->db->select('*', $this->table, $this->remember_column . " = '" . $this->db->escapeString($_COOKIE[$this->remember_cookie_name]). "' ". $this->add_where);
				$user = $this->db->fetch($res);
				if ($user){
					$this->createSession($user);
					$this->recoverPermanentData();
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Забыть пользователя
	 */
	public function forgot()
	{
		setcookie($this->remember_cookie_name, '', time() - 3600);
	}

	/**
	 * Получение единственного экземпляра класса
	 *
	 * @static
	 * @return Coffe_User
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Получение расшифровки ошибки
	 *
	 * @param $code
	 * @return mixed
	 */
	public function getError($code)
	{
		return $this->errors[$code];
	}


	protected function __construct()
	{
		Coffe::startSession();
		$this->db = $GLOBALS['COFFE_DB'];
	}


	/**
	 * Установить временные данные
	 *
	 * @param $key
	 * @param $data
	 * @return Coffe_User
	 */
	public function setData($key, $data)
	{
		if ($this->isLogin()){
			$_SESSION[$this->session_key]['data'][$key] = $data;
		}
		return $this;
	}

	/**
	 * Получить временные данные из сессии пользователя
	 *
	 * @param $key
	 * @return null
	 */
	public function getData($key)
	{
		if (!$this->isLogin()) return null;

		return ($key !== null)
			? (isset($_SESSION[$this->session_key]['data'][$key]) ? $_SESSION[$this->session_key]['data'][$key] : null)
			: ($_SESSION[$this->session_key]['data']);
	}

	/**
	 * Установить автосохранение данных
	 *
	 * @param $value
	 * @return Coffe_User
	 */
	public function setAutoSavePermanentData($value)
	{
		$this->auto_save_permanent_data = $value ? true : false;
		return $this;
	}

	/**
	 * Установить постоянные данные
	 *
	 * @param $key
	 * @param $data
	 * @return Coffe_User
	 */
	public function setPermanentData($key, $data)
	{
		if ($this->isLogin()){
			$_SESSION[$this->session_key]['permanent_data'][$key] = $data;
			if ($this->auto_save_permanent_data)
				$this->savePermanentData();
		}
		return $this;
	}

	/**
	 * Сохраняет постоянные данные в базу
	 *
	 * @return bool|int
	 */
	public function savePermanentData()
	{
		if ($this->isLogin()){
			if (isset($_SESSION[$this->session_key]['permanent_data']) && is_array($_SESSION[$this->session_key]['permanent_data'])){
				$res = $this->db->select('*', $this->table_session, 'user = ' . $this->db->fullEscapeString($this->getID()));
				$find = $this->db->fetch($res);
				if ($find){
					return $this->db->update($this->table_session,
						array('data' => serialize($_SESSION[$this->session_key]['permanent_data'])),
						'user =' . $this->db->fullEscapeString($this->getID())
					);
				}
				else{
					return $this->db->insert($this->table_session,
						array(
							'data' => serialize($_SESSION[$this->session_key]['permanent_data']),
							'user' => $this->getID()
						)
					);
				}
			}
		}
		return false;
	}

	/**
	 * Восстанавливает постоянные данные
	 */
	public function recoverPermanentData()
	{
		if ($this->isLogin()){
			$res = $this->db->select('*', $this->table_session, 'user = ' . $this->db->fullEscapeString($this->getID()));
			$data = $this->db->fetch($res);
			if ($data){
				$_SESSION[$this->session_key]['permanent_data'] = unserialize($data['data']);
			}
		}
	}

	/**
	 * Получить id текущего пользователя
	 *
	 * @return null|int
	 */
	public function getID()
	{
		if ($this->isLogin()){
			return $_SESSION[$this->session_key][$this->id_column];
		}
		return null;
	}

	/**
	 * Получение всех групп пользователей
	 *
	 * @return array|bool
	 */
	public function getAllGroups()
	{
		return $this->db->fetchAll($this->db->select('*', $this->table_group));
	}

	/**
	 * Получить постоянные данные из сессии пользователя
	 *
	 * @param $key
	 * @return null
	 */
	public function getPermanentData($key)
	{
		if (!$this->isLogin()) return null;

		return ($key !== null)
			? (isset($_SESSION[$this->session_key]['permanent_data'][$key]) ? $_SESSION[$this->session_key]['permanent_data'][$key] : null)
			: ($_SESSION[$this->session_key]['permanent_data']);
	}

}