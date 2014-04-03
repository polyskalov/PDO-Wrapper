<?php

class DatabaseTest extends PHPUnit_Framework_TestCase {
	/**
	 * Database object
	 * @var Object
	 */
	private $db;

	public function setUp() {
		$this->db = new Database($GLOBALS['db_dbname'], $GLOBALS['db_username'], $GLOBALS['db_password'], $GLOBALS['db_host']);
		$this->db->exec("CREATE TABLE IF NOT EXISTS `userstest` (`id` int(11) NOT NULL AUTO_INCREMENT,`login` varchar(50) NOT NULL, `password` varchar(100) NOT NULL,`activated` tinyint(1) NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");

		$this->db->exec("INSERT INTO `userstest` (`login`, `password`, `activated`) VALUES ('root', '7c6a180b36896a0a8c02787eeafb0e4c', 1), ('admin', '6cb75f652a9b52798eb6cf2201057c73', 1), ('polyskalov', '819b0643d6b89dc9b579fdfc9094f28e', 0);");

	}

	public function tearDown() {
		if($this->db) {
			$this->db->exec("DROP TABLE `userstest`");
		}
	}

	public function inputSelect() {
		return array(
			array('id,login'),
			array(array('id', 'login'))
		);
	}

	/**
	 * @dataProvider inputSelect
	 */
	public function testSelect($fields) {

		$users = $this->db->select('userstest', $fields);

		$this->assertArrayHasKey('login', $users[2], 'Needed field is not exists in response');
		$this->assertArrayNotHasKey('password', $users[2], 'The response contains not declared field');

		$this->assertEquals('polyskalov', $users[2]['login'], 'Selected wrong value');
	}

	/**
	 * @dataProvider inputSelect
	 */
	public function testSingle($fields) {

		$user = $this->db->single('userstest', $fields);

		$this->assertArrayHasKey('login', $user, 'Needed field is not exists in response');
		$this->assertArrayNotHasKey('password', $user, 'The response contains not declared field');

		$this->assertEquals('root', $user['login'], 'Selected wrong value');
	}


	public function testInsert() {

		$data = array('login' => 'inserted');

		$new_id = $this->db->insert('userstest', $data);

		$user = $this->db->where('id', $new_id)->single('userstest', 'login');

		$this->assertEquals('inserted', $user['login'], 'Returned bad value');
	}
}
