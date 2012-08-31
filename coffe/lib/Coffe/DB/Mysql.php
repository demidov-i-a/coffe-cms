<?php

/**
 * Адаптер Mysql для базы данных
 *
 * @package coffe_cms
 */

class Coffe_DB_Mysql extends Coffe_DB_Abstract
{


	/**
	 * TODO: Обратить внимание на тип [NATIONAL] VARCHAR(M) [BINARY] а так же параметр ZEROFILL
	 *
	 * @var array
	 */
	protected $shemeTypesTransform = array(
		'tinyint' => 'tinyint',
		'bit' => 'tinyint',
		'bool' => 'tinyint',
		'smallint' => 'int',
		'mediumint' => 'int',
		'int' => 'int',
		'integer' => 'int',
		'bigint' => 'bigint',
		'float' => 'float',
		'double' => 'double',
		'real' => 'double',
		'date' => 'date',
		'datetime' => 'datetime',
		'timestamp' => 'timestamp',
		'tinytext' => 'tinytext',
		'tinyblob' => 'tinyblob',
		'varchar' => 'tinytext',
		'char' => 'tinytext',
		'text' => 'text',
		'blob' => 'blob',
	);




	protected function _connect()
	{
		if ($this->isConnected())
			return true;

		if (!isset($this->_config['host']) || !isset($this->_config['username']) || !isset($this->_config['password']) || !isset($this->_config['dbname'])){
			throw new Coffe_DB_Exception('It is not set connection parameters!');
		}

		if ($this->sql_pconnect($this->_config['host'], $this->_config['username'], $this->_config['password'])) {
			if (!$this->_config['dbname']) {
				throw new Coffe_DB_Exception('No database selected!');
			} elseif (!$this->selectDB($this->_config['dbname'])) {
				throw new Coffe_DB_Exception('Cannot connect to the current database, "' . $this->_config['dbname'] . '"!');
			}
		} else {
			throw new Coffe_DB_Exception('The current username, password or host was not accepted when the connection to the database was attempted to be established!');
		}

		if (isset($this->_config['init']) && is_string($this->_config['init'])){
			$this->query($this->_config['init']);
		}

		return true;
	}

	private function sql_pconnect($host, $username, $password)	{
		// check if MySQL extension is loaded
		if (!extension_loaded('mysql')) {
			throw new Coffe_DB_Exception('Database Error: It seems that MySQL support for PHP is not installed!');
		}
		// Check for client compression
		$isLocalhost = ($host == 'localhost' || $host == '127.0.0.1');
		if (isset($this->_config['no_pconnect']) && $this->_config['no_pconnect']) {
			if ($this->_config['dbClientCompress'] && !$isLocalhost) {
				// We use PHP's default value for 4th parameter (new_link), which is false.
				// See PHP sources, for example: file php-5.2.5/ext/mysql/php_mysql.c,
				// function php_mysql_do_connect(), near line 525
				$this->_connection = @mysql_connect($host, $username, $password, false, MYSQL_CLIENT_COMPRESS);
			} else {
				$this->_connection = @mysql_connect($host, $username, $password);
			}
		} else {
			if (isset($this->_config['dbClientCompress']) && $this->_config['dbClientCompress'] && !$isLocalhost) {
				// See comment about 4th parameter in block above
				$this->_connection = @mysql_pconnect($host, $username, $password, MYSQL_CLIENT_COMPRESS);
			} else {
				$this->_connection = @mysql_pconnect($host, $username, $password);
			}
		}
		return $this->_connection;
	}

	public function isConnected()
	{
		return is_resource($this->_connection);
	}

	public function closeConnection()
	{
		$ret = mysql_close($this->_connection);
		if ($ret) unset($this->_connection);
		return $ret;
	}

	public function selectDB($db_name)
	{
		return @mysql_select_db($db_name, $this->_connection);
	}

	public function escapeString($value, $type = null)
	{
		$this->_connect();
		return mysql_real_escape_string($value, $this->_connection);
	}

	private function fullEscapeArray(array &$arr)
	{
		foreach ($arr as $name => $value) {
			$arr[$name] = $this->fullEscapeString($value);
		}
	}

	public function fetch($res, $assoc = true)
	{
		if (!$res) return false;
		if ($assoc)
			return mysql_fetch_assoc($res);
		else
			return mysql_fetch_row($res);
	}

	public function fetchAll($res, $assoc = true)
	{
		if (!$res) return false;
		$rows = array();
		while($row = $this->fetch($res,$assoc)){
			$rows[] = $row;
		}
		return $rows;
	}

	public function select($fields, $table, $where = '', $groupBy = '', $orderBy = '', $limit = '')
	{
		$query = $this->buildSelectQuery($fields, $table, $where, $groupBy, $orderBy, $limit);
		return $this->query($query);
	}


	public function insert($table, array $bind)
	{
		$query = $this->buildInsertQuery($table, $bind);
		$done = $this->query($query);
		return ($done) ? $this->lastInsertId($table) : false;
	}

	public function update($table, array $bind, $where = '')
	{
		$query = $this->buildUpdateQuery($table, $bind, $where);
		return $this->query($query);
	}

	public function delete($table, $where = '')
	{
		$query = $this->buildDeleteQuery($table,$where);
		return $this->query($query);
	}

	public function query($sql)
	{
		$this->_connect();
		if ($this->isDebug()){
			$this->_queries[] = $sql;
		}
		return mysql_query($sql, $this->_connection);
	}

	public function lastInsertId($tableName = null, $primaryKey = null)
	{
		$this->_connect();
		return mysql_insert_id($this->_connection);
	}

	private function buildSelectQuery($fields, $table, $where, $groupBy = '', $orderBy = '', $limit = '')
	{
		// Table and fieldnames should be "SQL-injection-safe" when supplied to this function
		// Build basic query:
		$query = 'SELECT ' . $fields . ' FROM ' . $table .
			(strlen($where) > 0 ? ' WHERE ' . $where : '');
		$query .= (strlen($groupBy) > 0 ? ' GROUP BY ' . $groupBy : '');
		$query .= (strlen($orderBy) > 0 ? ' ORDER BY ' . $orderBy : '');
		$query .= (strlen($limit) > 0 ? ' LIMIT ' . $limit : '');
		return $query;
	}

	private function buildInsertQuery($table, array $bind)
	{
		// quote and escape values
		$this->fullEscapeArray($bind);
		// Build query:
		$query = 'INSERT INTO ' . $table .
			' (' . implode(',', array_keys($bind)) . ') VALUES ' .
			'(' . implode(',', $bind) . ')';

		return $query;
	}

	private function buildUpdateQuery($table, array $bind, $where)
	{
		if (!is_string($where)){
			throw new Coffe_DB_Exception('"Where" clause argument for UPDATE query was not a string');
		}
		$fields = array();
		if (is_array($bind) && count($bind)) {
			$this->fullEscapeArray($bind);
			foreach ($bind as $name => $value) {
				$fields[] = $name . '=' . $value;
			}
		}
		$query = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) .
			(strlen($where) > 0 ? ' WHERE ' . $where : '');
		return $query;
	}

	private function buildDeleteQuery($table,$where)
	{
		if (!is_string($where)){
			throw new Coffe_DB_Exception('"Where" clause argument for DELETE query was not a string');
		}
		$query = 'DELETE FROM ' . $table .
			(strlen($where) > 0 ? ' WHERE ' . $where : '');
		return $query;
	}

	/**
	 * Получение описания таблицы
	 *
	 * @param $table
	 * @return array|bool
	 */
	public function getScheme($table)
	{
		$res = $this->query('DESCRIBE ' . $table);
		if ($status = $this->fetchAll($res)){

			$scheme = array();
			$scheme['name'] = $table;
			$scheme['columns'] = array();
			if (is_array($status)){
				foreach ($status as $column){
					$scheme['columns'][$column['Field']] = $this->getSchemeType($column);
				}
			}

			$this->transformSchemeTypes($scheme);

			if ($primary = $this->getPrimaryKey($scheme['name'])){
				$scheme['primary'] = $primary;
			}

			return $scheme;
		}
		return false;
	}



	public function installScheme($scheme, &$errors, &$warnings)
	{
		//такой таблицу нету в базе
		if (!($current = $this->getScheme($scheme['name']))){
			echo 'create';
			if (isset($scheme['columns']) && is_array($scheme['columns']) && count($scheme['columns'])){
				return $this->createTable($scheme, $errors);
			}
			else{
				$errors[] = 'Coffe_DB_Mysql: it is not possible to create the table without columns';
			}
		}
		//таблица уже есть, сверяем наличие колонок и обновляем таблицу, если нужно
		else{
			$columns = Coffe_ModuleManager::getSchemeDifference($scheme , $current, $other);
			if (count($columns) || $other){
				$this->getPrimaryKey($scheme['name']);
				return $this->changeColumns($scheme ,$current , $errors);
			}
		}
		return true;
	}

	public function uninstallScheme($scheme, &$errors, &$warnings)
	{

	}

	/**
	 * Получить PrimaryKey для таблицы
	 *
	 * @param $table
	 * @return bool|string
	 */
	protected function getPrimaryKey($table)
	{
		$res = $this->query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");
		if ($row = $this->fetch($res)){
			return isset($row['Column_name']) ? $row['Column_name']: false;
		}
		return false;

	}

	protected function changeColumns($scheme, $current, &$errors)
	{
		$alter_array = array();

		$need_create_primary = false;
		if (isset($scheme['primary'])){

			if ($primary = $this->getPrimaryKey($scheme['name'])){

				if ($primary != $scheme['primary']){
					$alter_array[] = "DROP PRIMARY KEY";
					$need_create_primary = true;
				}
			}
			else{
				$need_create_primary = true;
			}
		}

		foreach($scheme['columns'] as $name => $params){
			if ($create_sql = $this->getColumnCreateSql($name, $params)){
				$more = '';
				if ($need_create_primary && ($name == $scheme['primary'])){
					$more = ", ADD PRIMARY KEY ({$scheme['primary']})";
				}
				if (isset($current['columns'][$name])){
					$alter_array[] = "CHANGE `{$name}` " . $create_sql . $more;
				}
				else{
					$alter_array[] = "ADD " . $create_sql . $more;
				}
			}
		}

		if (count($alter_array)){
			$sql = "ALTER TABLE `{$scheme['name']}` " . implode(', ', $alter_array);
			echo $sql;
			if (!$this->query($sql)){
				$errors[] = mysql_error();
				return false;
			}
		}

		return true;
	}

	/**
	 * Преобразует описание схемы из mysql в csm
	 *
	 * @param $scheme
	 */
	protected function transformSchemeTypes(&$scheme)
	{
		if (is_array($scheme['columns'])){
			foreach ($scheme['columns'] as &$column){
				if (isset($column['type']) && isset($this->shemeTypesTransform[$column['type']])){
					$column['type'] = $this->shemeTypesTransform[$column['type']];
				}
				else{
					$column['type'] = 'unknown';
				}
			}
		}
	}


	/**
	 * Создает новую схему (таблицы нет в базе)
	 *
	 * @param $scheme
	 * @param $errors
	 * @return bool
	 */
	protected function createTable($scheme, &$errors)
	{
		if (isset($scheme['name'])){
			$sql_columns = array();
			$primary = false;
			foreach ($scheme['columns'] as $name => $params){
				//елис задано поле с автоинкременом, то делаем его первичным ключём
				if (isset($params['autoincrement']) && $params['autoincrement']){
					$primary = $name;
				}
				if ($sql = $this->getColumnCreateSql($name , $params)){
					$sql_columns[] = $sql;
				}
			}
			if (count($sql_columns)){
				if (isset($scheme['primary'])){
					$sql_columns[] = "PRIMARY KEY  (`" . $scheme['primary'] . "`)";
				}
				$sql = "CREATE TABLE IF NOT EXISTS `" . $scheme['name'] ."` (" . implode(',',$sql_columns) .")";
				if ($this->query($sql)){
					return true;
				}
				$errors[] = mysql_error();
				return false;
			}
		}
		return false;
	}

	/**
	 * Выдает sql для создания новой колонки в формате mysql, на основе параметров
	 *
	 * @param $name
	 * @param $params
	 * @return bool|string
	 */
	protected function getColumnCreateSql($name, $params)
	{
		/**
		 * TODO: экранировнаие, параметр binary
		 */
		if (isset($params['type'])){
			//tintext с заданной длиной преобразуем в varchar
			if (($params['type'] == 'tinytext')  && isset($params['length']) && $params['length'] <= 255){
				$params['type'] = 'varchar';
			}

			$sql = "`{$name}` {$params['type']}";

			if (isset($params['length'])){
				$sql .= '(' . $params['length'] .')';
			}
			$autoincrement = false;
			if (isset($params['autoincrement']) && $params['autoincrement']){
				$autoincrement = true;
			}
			if (isset($params['unsigned']) && isset($params['unsigned'])){
				$sql .= ' unsigned';
			}
			if (!isset($params['null']) || !$params['null'] || $autoincrement){
				$sql .= ' NOT NULL';
			}
			if (isset($params['default']) && $params['default']){
				$sql .= ' default '.$this->escapeString($params['default']);
			}
			if ($autoincrement){
				$sql .= ' auto_increment';
			}
			return $sql;
		}
		return false;
	}

	/**
	 * Отдает описание близкое к описанию csm, на основе mysql - описания колонки
	 *
	 * Данная функция должна устанавливать максимальное количество ключей в массиве. Так, если не autoincrement,
	 * то должен быть ключ autoincrement = false
	 *
	 * @param $column
	 * @return array
	 */
	protected function getSchemeType($column)
	{
		$type = array();

		$type['null'] = (strtolower($column['Null']) == 'no') ? false : true;
		$type['default'] = $column['Default'];
		$type['autoincrement'] = false;

		if (isset($column['Extra'])){
			switch(strtolower($column['Extra'])){
				case 'auto_increment': $type['autoincrement'] = true; break;
			}
		}
		$types = Coffe_Func::trimExplode(' ', strtolower($column['Type']));

		if (count($types)){
			/**
			 * TODO: проверить, что это так
			 */
			//если у varchar указан параметр national
			if (in_array('varchar',$types) && count($types) == 3){
				$type['national'] = array_shift($types);
			}

			$part = array_shift($types);
			$pattern = '#\(([0-9\,]+?)\)#';
			if (preg_match($pattern,$part,$out)){
				$type['length'] = $out[1];
				$type['type'] = preg_replace($pattern,'',$part);
			}
			else{
				$type['type'] = $part;
			}
			if (count($types)){
				$part = array_shift($types);
				switch($part){
					case 'unsigned': $type['unsigned'] = true; break;
					case 'binary': $type['binary'] = true; break;
				}
			}
		}
		return $type;
	}

	/**
	 * Получение последней ошибки
	 *
	 * @return bool|string
	 */
	public function lastError()
	{
		return mysql_error($this->_connection);
	}
}