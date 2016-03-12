<?php
require_once "Loading.class.php";
require_once "ModelBase.class.php";

class Material extends ModelBase {
	public function __construct($fields = array()) {
		parent::__construct($fields);
	}

	public function getTableName() {
		return 'ba_materials';
	}

	public static function getAllRecordsByCondition($condition) {
		return parent::getAllObjectByCondition('Material', $condition);
	}

	public static function getAllRecordsByUsersCalcPK($user_no) {
		return self::getAllRecordsByCondition("`userscalcPK`=$user_no");
	}

	public static function getRecordByRcdNo($rcdNo) {
		$materialList = self::getAllRecordsByCondition("`rcdNo`=$rcdNo");
		if (empty($materialList)) { return null; }
		else { return $materialList[0]; }
	}
}
?>