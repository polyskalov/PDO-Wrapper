<?php

class Database extends PDO {

	protected $_instance;
	protected $_sth;
	private function __clone() { }

	public static function getInstance() {

		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public $pdo_attributes = array(
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_CASE => PDO::CASE_LOWER
	);

	/**
	 * Extra conditions
	 * @var array
	 */
	protected static $extra = array(
		'where' => ''
	);

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

		parent::__construct("$dbType:host=$host;dbname=$dbName;charset=utf8", $user, $password, $this->pdo_attributes);
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
	 * Return extra parameters (where, limit, etc..) as part of sql query
	 * @return string  Part of sql query
	 */
	private static function _extra() {
		$where = '';

		if( !empty(self::$extra['where']) ) {

			$where = ' WHERE ' . self::$extra['where'];

			// reset where
			self::$extra['where'] = null;
		}

		return ' ' . $where . ' ';
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

		$this->_sth = $this->prepare("select $fields from `$tables`" . self::_extra());

		$this->_sth->execute();

		return $this->_sth->fetchAll();
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

		$this->_sth = $this->prepare("select $fields from $table " . self::_extra() . " limit 1");

		$this->_sth->execute();

		return $this->_sth->fetch();
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

		$this->_sth = $this->prepare("UPDATE $table SET $fieldDetails" . self::_extra());

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

		if(empty(self::$extra['where']) and !$allowRemoveAll) {
			throw new Exception('You must use "where" to delete record, or set third parameter to "true", if you want to delete all records');
		} else {
			$this->_sth = $this->prepare("DELETE FROM $table" . self::_extra() . "LIMIT $limit");

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
		$this->_sth = $this->prepare("select count(*) from `$table`" . self::_extra(). ';');

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
	 * @param mixed  $condition  Sql like condition as string or field name
	 * @param mixed  $value      If defined, used for operations with field
	 * @param string $operator   Operator for arrays of conditions
	 * @return self
	 */
	public function where($condition, $value = null, $operator = '=') {
		$finalCondition = null;

		if(!empty($value)) {
			$finalCondition .= "`$condition` $operator $value ";
		}

		self::$extra['where'] = $finalCondition;

		return $this;
	}
}

class DBException extends Exception { }
