<?php
/*
	Description: base class for other table classes
	Developer: Tuan Nguyen (lightbringer1991)
	Date modified: 02/03/2016
*/
require_once('Database.class.php');

class ModelBase {
	protected $fields;

	public function __construct($fields = array()) {
		$this -> fields = $fields;
	}

	public function getTableName() {
		return "ModelBase";
	}

	// get data of a column
	public function get($field) {
		return $this -> fields[$field];
	}

	// set data of a column
	public function set($field, $value) {
		$this -> fields[$field] = $value;
	}

	public static function getAllObjectByCondition($class, $condition) {
		$obj = new $class();
		$tableName = $obj -> getTableName();
		$db = new Database();
		$objList = array();

		$condition = $db -> clean($condition);
		$query = "SELECT * FROM `$tableName` WHERE $condition";
		
		$result = $db -> executeQuery($query);
		if ($db -> numRows($result) == 0) { return array(); }
		else {
			while ($row = $db -> get($result)) {
				array_push($objList, new $class($row));
			}
		}
		return $objList;
	}
}
?>