<?php

class DatabaseTest extends PHPUnit_Framework_TestCase {
	/**
	 * Database object
	 * @var Object
	 */
	private $db;

	public function setUp() {
		$this->db = new Database($GLOBALS['db_dbname'], $GLOBALS['db_username'], $GLOBALS['db_password'], $GLOBALS['host']);
		$this->db->exec("CREATE TABLE IF NOT EXISTS `userstest` (`id` int(11) NOT NULL AUTO_INCREMENT,`login` varchar(50) NOT NULL, `password` varchar(100) NOT NULL,`activated` tinyint(1) NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

		$this->db->exec("INSERT INTO `userstest` (`login`, `password`, `activated`) VALUES ('root', '7c6a180b36896a0a8c02787eeafb0e4c', 1), ('admin', '6cb75f652a9b52798eb6cf2201057c73', 1), ('polyskalov', '819b0643d6b89dc9b579fdfc9094f28e', 0);");

	}

	public function tearDown() {
		$this->db->exec("DROP TABLE `userstest`");
	}

	public function testSelect() {

		$users = $this->db->select('userstest');

		$this->assertEquals('polyskalov', $users[2]['login'], 'Select failed! Selected value not equals.');
	}

	public function testSelectWithSomeFieldsAsArray() {

		$users = $this->db->select('userstest', array('id', 'login') );

		$this->assertEquals('polyskalov', $users[2]['login'], 'Select with fields defined by array failed! Selected value not equals.');
		$this->assertNull($users[2]['password'], 'Select with fields defined by array failed! Field not listed in params are axists');
	}

	public function testSelectWithSomeFieldsAsString() {

		$users = $this->db->select('userstest', 'id,login');

		$this->assertEquals('polyskalov', $users[2]['login'], 'Select with fields defined by array failed! Selected value not equals.');
		$this->assertNull($users[2]['password'], 'Select with fields defined by array failed! Field not listed in params are axists');

	}

	public function testSingle() {

		$user = $this->db->single('userstest', 'id,login');

		$this->assertEquals('root', $user['login'], 'Select single failed! Needed value not exuals');
	}
}
