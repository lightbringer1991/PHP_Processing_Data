<?php
require_once "ModelBase.class.php";
require_once "Node.class.php";

class Support extends ModelBase {

	public function __construct($fields = array()) {
		parent::__construct($fields);
	}

	public function getTableName() {
		return 'ba_supports';
	}

	public function getSupportNode() {
		return new Node(array('y' => $this -> get('location')));
	}

	public static function getAllRecordsByCondition($condition) {
		return parent::getAllObjectByCondition('Support', $condition);
	}

	public static function getAllRecordsByUsersCalcPK($user_no) {
		return self::getAllRecordsByCondition("`userscalcPK`=$user_no");
	}
}
?>