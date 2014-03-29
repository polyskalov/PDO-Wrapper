<?php

class Database extends PDO {

	protected static $_instance;
	protected static $_sth;
	private function __clone() { }

	public static function getInstance() {

		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Where condition
	 * @var string
	 */
	protected static $where;

	/**
	 * Initialization and connecting
	 * @param mixed  $db       Name of your database or connection parameters as associative array
	 * @param string $user     Username
	 * @param string $password Password
	 * @param string $host     Database server
	 * @param string $dbType   Type of a base (mysql, ms sql and maybe other)
	 * @return null
	 */
	public function __construct($db, $user = 'root', $password = '', $host = 'localhost', $dbType = 'mysql') {

		if(is_array($db)) {
			if(!empty( $db['user'] )) $user = $db['user'];
			if(!empty( $db['password'] )) $password = $db['password'];
			if(!empty( $db['host'] )) $host = $db['host'];
			if(!empty( $db['type'] )) $dbType = $db['type'];
			if(!empty( $db['dbname'] )) $dbName = $db['dbname'];
		} else {
			$dbName = $db;
		}

		parent::__construct("$dbType:host=$host;dbname=$dbName;charset=utf8", $user, $password);

		parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Bind values
	 * @param  PDOStatement $sth    PDO Object
	 * @param  array        $fields Array to bind
	 * @return self
	 */
	private function bind($fields = array()) {
		foreach ($fields as $key => $value) {

			if (is_int($value)) {

				$valueType = PDO::PARAM_INT;
			} elseif(is_bool($value)) {

				$valueType = PDO::PARAM_BOOL;
			} else {

				$valueType = PDO::PARAM_STR;
			}

			$this->_sth->bindValue($key, $value, $valueType);
		}

		return $this;
	}

	/**
	 * Return where condition as part of sql query
	 * @return string Where part of sql query
	 */
	private static function _where() {
		if( !empty(self::$where) ) {

			$where = self::$where;

			// reset where
			self::$where = null;

			return ' WHERE ' . $where;
		}
	}

	/**
	 * Select
	 * @param  mixed  $tables  Needed table (as string) or tables (as array)
	 * @param  mixed  $fields String or array of fields you want to select
	 * @return array  Result as associative array
	 */
	public function select($tables, $fields = '*') {

		if( is_array($tables) ) {
			$tables = implode('`, `', $tables);
		}

		if( is_array($fields) ) {
			$fields = implode(', ', $fields);
		}

		$this->_sth = $this->prepare("select $fields from `$tables`" . self::_where());

		$this->_sth->execute();

		return $this->_sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Select single row
	 * @param  string $table  Table
	 * @param  mixed  $fields String or array of fields you want to select
	 * @return array  Result as associative array
	 */
	public function single($table, $fields = '*') {

		if( is_array($fields) ) {
			$fields = implode(', ', $fields);
		}

		$this->_sth = $this->prepare("select $fields from $table " . self::_where() . " limit 1");

		$this->_sth->execute();

		return $this->_sth->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Insert
	 * @param  string  $table  A name of table to insert into
	 * @param  string  $data   An associative array with data
	 * @return integer ID of inserted record
	 */
	public function insert($table, $data) {
		ksort($data);

		$fieldNames = implode('`, `', array_keys($data));
		$fieldValues = ':' . implode(', :', array_keys($data));

		$this->_sth = $this->prepare("INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)");

		$this->bind($data);

		$this->_sth->execute();

		return $this->_sth->lastInsertId();
	}

	/**
	 * Update
	 * @param string $table A name of table to insert into
	 * @param string $data  An associative array with data
	 */
	public function update($table, $data) {
		ksort($data);

		$fieldDetails = NULL;
		foreach($data as $key => $value) {
			$fieldDetails .= "`$key`=:$key,";
		}
		$fieldDetails = rtrim($fieldDetails, ',');

		$this->_sth = $this->prepare("UPDATE $table SET $fieldDetails" . self::_where());

		$this->bind($data);

		$this->_sth->execute();

		return $this->_sth->rowCount();
	}

	/**
	 * Delete
	 * @param  string  $table          Table to remove from
	 * @param  integer $limit          Limit deletions
	 * @param  boolean $allowRemoveAll Allow removing all records from table, if "where" not used
	 * @return integer Affected Rows
	 */
	public function delete($table, $limit = 1, $allowRemoveAll = false) {

		if(empty(self::$where) and !$allowRemoveAll) {
			throw new Exception('You must use "where" to delete record, or set third parameter to "true", if you want to delete all records');
		} else {
			$this->_sth = $this->prepare("DELETE FROM $table" . self::_where() . "LIMIT $limit");

			$this->_sth->execute();

			return $this->_sth->rowCount();
		}
	}

	/**
	 * Get count of rows
	 * @param  string  $table Table
	 * @return integer Count rows
	 */
	public function count($table) {
		$this->_sth = $this->prepare("select count(*) from `$table`" . self::_where(). ';');

		$this->_sth->execute();

		return intval($this->_sth->fetchColumn());
	}

	/**
	 * Just execute some request
	 * @param  string    $sql   SQL query
	 * @param  array     $bind  Bind parameters
	 * @return PDOObject        PDO Instance
	 */
	public function exec($sql, $bind = array()) {

		$this->_sth = $this->prepare($sql);

		$this->bind($bind);

		return $this->_sth->execute();
	}

	/**
	 * Add where into query
	 * @param  mixed  $condition sql like condition as string or associative array
	 * @param  string $operator  Operator for arrays of conditions
	 * @return self
	 */
	public function where($conditions, $operator = 'and') {

		if( is_array($conditions) ) {

			$conditions_array = $conditions;
			$conditions = '';

			$end = end($conditions_array);

			foreach ($conditions_array as $key => $value) {
				$conditions .= "`$key`='$value' ".($end != $value? $operator:'');
			}
		}

		self::$where = $conditions;

		return $this;
	}
}

class DBException extends Exception { }
