<?php
require_once "ModelBase.class.php";

class Ilcs extends ModelBase {

	public function __construct($fields = array()) {
		parent::__construct($fields);
	}

	public function getTableName() {
		return 'ba_ilcs';
	}

	// get hard nodes from ilcs data
	// geometry == Distributed => collect startLocation and endLocation, plus zeroLocation if startValue and endValue have different signs
	// geometry == Point => collect startLocation only
	public function getNodes() {
		$nodeList = array();
		if ($this -> get('geometry') == 'Distributed') {
			$startNode = new Node( array('y' => doubleval($this -> get('startLocation'))) );
			$startNode -> value = doubleval($this -> get('startValue'));
			$endNode = new Node( array('y' => doubleval($this -> get('endLocation'))) );
			$endNode -> value = doubleval($this -> get('endValue'));
			array_push($nodeList, $startNode);
			array_push($nodeList, $endNode);

			// generate new node if startValue and endValue has different sign
			// this method may cause overflow if startValue and endValue are too big (over 10^5 maybe)
			// can change it to a longer version with overflow safe later
			if ($startNode -> value * $endNode -> value < 0) {
				$zeroNode = $this -> calculateZeroPosition();
				$zeroNode -> value = 0;
				array_push($nodeList, $zeroNode);
			}
		} elseif ($this -> get('geometry') == 'Point') {
			$newNode = new Node( array('y' => doubleval($this -> get('startLocation'))) );
			$newNode -> value = doubleval($this -> get('startValue'));
			array_push($nodeList, $newNode);
		}
		return $nodeList;
	}

	public static function getAllRecordsByCondition($condition) {
		return parent::getAllObjectByCondition('Ilcs', $condition);
	}

	public static function getAllRecordsByUsersCalcPK($user_no) {
		return self::getAllRecordsByCondition("`userscalcPK`=$user_no");
	}

	public static function getRecordByRcdNo($id) {
		$ilcsList = self::getAllRecordsByCondition("`rcdNo`=$id");
		if (empty($ilcsList)) { return null; }
		else { return $ilcsList[0]; }
	}

	// $data: a row from loading table
	private function calculateZeroPosition() {
		$loadingRatio = abs($this -> get('startValue') / $this -> get('endValue'));
		$loadingLength = $this -> get('endLocation') - $this -> get('startLocation');
		$zeroPoint = $this -> get('startLocation') + ($loadingLength * $loadingRatio) / (1 + $loadingRatio);
		return new Node(array( 'y' => $zeroPoint ));
	}

}
?>
