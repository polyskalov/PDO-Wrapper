<?php

class Database extends PDO {

	protected static $_instance;

	private function __clone() { }

	public static function getInstance() {

		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initialization and connecting
	 * @param string $dbName   Name of your database
	 * @param string $user     Username
	 * @param string $password Password
	 * @param string $host     Database server
	 * @param string $dbType   Type of a base (mysql, ms sql and maybe other)
	 */
	public function __construct($db, $user = 'root', $password = '', $host = 'localhost', $dbType = 'mysql') {

		if(is_array($db)) {
			$user = $db['user'];
			$password = $db['password'];
			$host = $db['host'];
			$dbType = $db['type'];
			$dbName = $db['dbname'];
		} else {
			$dbName = $db;
		}

		parent::__construct("$dbType:host=$host;dbname=$dbName;charset=UTF8",
			$user,
			$password
		);

		parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/**
	 * Select
	 * @param string $sql An SQL string
	 * @param array $array Paramters to bind
	 * @param constant $fetchMode A PDO Fetch mode
	 * @return mixed
	 */
	public function select($sql, $bind = array()) {
		$sth = $this->prepare($sql);

		$this->bind($sth, $bind);

		$sth->execute();

		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Select single row
	 * @param string $sql An SQL string
	 * @param array $array Paramters to bind
	 * @param constant $fetchMode A PDO Fetch mode
	 * @return mixed
	 */
	public function single($sql, $bind = array()) {
		$sth = $this->prepare($sql);

		$this->bind($sth, $bind);

		$sth->execute();

		return $sth->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * insert
	 * @param string $table A name of table to insert into
	 * @param string $data An associative array
	 */
	public function insert($table, $data) {
		ksort($data);

		$fieldNames = implode('`, `', array_keys($data));
		$fieldValues = ':' . implode(', :', array_keys($data));

		$sth = $this->prepare("INSERT INTO $table (`$fieldNames`) VALUES ($fieldValues)");

		$this->bind($sth, $data);

		return $sth->execute();
	}

	/**
	 * Update
	 * @param string $table A name of table to insert into
	 * @param string $data An associative array
	 * @param string $where the WHERE query part
	 */
	public function update($table, $data, $where) {
		ksort($data);

		$fieldDetails = NULL;
		foreach($data as $key=> $value) {
			$fieldDetails .= "`$key`=:$key,";
		}
		$fieldDetails = rtrim($fieldDetails, ',');

		$sth = $this->prepare("UPDATE $table SET $fieldDetails WHERE $where");

		$this->bind($sth, $bind);

		$sth->execute();
	}

	/**
	 * Delete
	 * @param string $table
	 * @param string $where
	 * @param integer $limit
	 * @return integer Affected Rows
	 */
	public function delete($table, $where, $limit = 1) {
		return $this->exec("DELETE FROM $table WHERE $where LIMIT $limit");
		//$sth->rowCount()
	}

	/**
	 * Get count of rows
	 * @param  string $table [description]
	 * @param  string $where [description]
	 * @param  array  $bind  [description]
	 * @return [type]        [description]
	 */
	public function count($table, $where = '', $bind=array()) {
		$sth = $this->prepare("select count(*) from `$table`" . ($where? ' where '.$where: ''). ';');

		$this->bind($sth, $bind);

		$sth->execute();

		return intval($sth->fetchColumn());
	}

	/**
	 * Bind values
	 * @param  PDOStatement $sth   PDO Object
	 * @param  fields       $array Array to bind
	 * @return [type]              [description]
	 */
	private function bind(PDOStatement $sth, $fields = array()) {
		foreach ($fields as $key => $value) {

			if (is_int($value)) {

				$valueType = PDO::PARAM_INT;
			} elseif(is_bool($value)) {

				$valueType = PDO::PARAM_BOOL;
			} else {

				$valueType = PDO::PARAM_STR;
			}

			$sth->bindValue($key, $value, $valueType);
		}

		return $this;
	}
}

class DBException extends Exception { }
