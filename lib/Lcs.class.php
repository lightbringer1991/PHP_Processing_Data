<?php
require_once "ModelBase.class.php";

class Lcs extends ModelBase {
	public $ilcsList = array();			// $data = array($ilcsObj)

	public function __construct($fields = array()) {
		parent::__construct($fields);
	}

	public function getTableName() {
		return 'ba_lcs';
	}

	// $rerun: create $ilcsList again, in case Lcs params field changes
	// $startValue, $endValue will be multiplied by $coefficient
	public function getIlcsList($rerun = false) {
		if ( empty($this -> ilcsList) && ($rerun == false) ) {
			$params = json_decode($this -> get('params'), true);
			foreach ($params as $id => $c) {
				// skip null ilcs
				$ilcsObj = Ilcs::getRecordByRcdNo($id);
				if ($ilcsObj != null) {
					$ilcsObj -> set('startValue', $ilcsObj  -> get('startValue') * $c);
					$ilcsObj -> set('endValue', $ilcsObj  -> get('endValue') * $c);
					array_push( $this -> ilcsList, $ilcsObj );
				}
			}			
		}
		return $this -> ilcsList;
	}

	public static function getRecordByRcdNo($id) {
		$lccList = self::getAllRecordsByCondition("`rcdNo`=$id");
		if (empty($lccList)) { return null; }
		else { return $lccList[0]; }
	}

	public static function getAllRecordsByCondition($condition) {
		return parent::getAllObjectByCondition('Lcs', $condition);
	}

	public static function getAllRecordsByUsersCalcPK($user_no) {
		return self::getAllRecordsByCondition("`userscalcPK`=$user_no");
	}
}
?>