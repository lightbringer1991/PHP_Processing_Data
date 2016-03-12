<?php
require_once "ModelBase.class.php";
require_once "Node.class.php";

class Section extends ModelBase {

	public function __construct($fields = array()) {
		parent::__construct($fields);
	}

	public function getTableName() {
		return 'ba_sections';
	}

	public function getStartNode() {
		$condition = "`userscalcPK`={$this -> get('userscalcPK')} AND `rcdNo`={$this -> get('n_start')}";
		$nodeList = Node::getAllRecordsByCondition($condition);
		if (empty($nodeList)) { return null; }
		else { return $nodeList[0]; }
	}

	public function getEndNode() {
		$condition = "`userscalcPK`={$this -> get('userscalcPK')} AND `rcdNo`={$this -> get('n_end')}";
		$nodeList = Node::getAllRecordsByCondition($condition);
		if (empty($nodeList)) { return null; }
		else { return $nodeList[0]; }
	}

	public static function getAllRecordsByCondition($condition) {
		return parent::getAllObjectByCondition('Section', $condition);
	}

	public static function getAllRecordsByUsersCalcPK($user_no) {
		return self::getAllRecordsByCondition("`userscalcPK`=$user_no");
	}

	public static function getRecordByRcdNo($rcdNo) {
		$sectionList = self::getAllRecordsByCondition("`rcdNo`=$rcdNo");
		if (empty($sectionList)) { return null; }
		else { return $sectionList[0]; }
	}
}
?>