<?php
require_once('config.php');
require_once('Section.class.php');
require_once('Support.class.php');
require_once('Ilcs.class.php');
require_once('Material.class.php');

class Database {
	private $db;
	private $config;
	private $data;

	public function __construct($database = 'calc') {
		$this -> config = array(
			'host' => DB_HOSTNAME,
			'username' => DB_USERNAME,
			'password' => DB_PASSWORD,
			'db' => ($database == 'shared') ? DB_SHARED_NAME : DB_CALC_NAME
		);
		$this -> db = $this -> initializeConnection();
		$this -> data = array(
			'geometry' => array(),
			'loading' => array(),
			'support' => array(),
			'material' => array()
		);
	}

	private function initializeConnection() {
		$conn = new mysqli(
			$this -> config['host'],
			$this -> config['username'],
			$this -> config['password'],
			$this -> config['db']
		);
		if ($conn -> connect_errno) {
			die("Connection failed: " . $conn -> connect_error);
		}
		return $conn;
	}

	public function getData($userId, $clear = false) {
		if ($clear) {
			// remove all data with userscalcPK = 0
			$this -> db -> query("DELETE FROM `ba_sections` WHERE userscalcPK = 0");
			$this -> db -> query("DELETE FROM `ba_ilcs` WHERE userscalcPK = 0");
			$this -> db -> query("DELETE FROM `ba_supports` WHERE userscalcPK = 0");
			$this -> db -> query("DELETE FROM `ba_materials` WHERE userscalcPK = 0");
		}
		
		$this -> data['geometry'] = Section::getAllRecordsByUsersCalcPK($userId);
		$this -> data['support'] = Support::getAllRecordsByUsersCalcPK($userId);
		$this -> data['ilcs'] = Ilcs::getAllRecordsByUsersCalcPK($userId);
		$this -> data['material'] = Material::getAllRecordsByUsersCalcPK($userId);

		return $this -> data;
	}

	public function clean($string) {
		return $this -> db -> real_escape_string($string);
	}

	public function executeQuery($query) {
		if ($result = $this -> db -> query($query)) {
			return $this -> db -> query($query);
		} else {
			die("Error executing query: " . mysqli_error($this -> db));
		}
	}

	public function get($resultSet) {
		return $resultSet -> fetch_assoc();
	}

	public function numRows($resultSet) {
		return $resultSet -> num_rows;
	}

	public function getEByRDCNo($rcdNo) {
		$query = "SELECT `E` FROM `ba_materials` WHERE `rcdNo`=$rcdNo";
		$result = $this -> executeQuery($query);
		$r = $result -> fetch_assoc();

		return $r['E'];
	}
}
?>