<?php
/*
	Node class
		($x, $y, $z): contains coordinate of the node
		$loading: contains the force/moment data at that node
		($elementStart, $elementEnd) contains the right-left hand side elements
		$value: contains the pre-defined value, if applicable (only hard nodes)
*/
require_once "Loading.class.php";

class Node {
	public $x;
	public $y;
	public $z;
	public $loading;
	public $elementStart;		// contains the Element object of which this node is the start node
	public $elementEnd; 		// contains the Element obj of which this node is the end node
	public $value;				// value of the node (only applies for hard nodes that has force applies to it)

	// acceptable distance between Nodes to be accepted as the same Node
	public static $delta = 0.0000001;

	public function __construct($x, $y, $z) {
		$this -> x = $x;
		$this -> y = $y;
		$this -> z = $z;
		$this -> loading = new Loading();
		$this -> value = null;
		$this -> elementStart = null;
		$this -> elementEnd = null;
	}

	// check if a node is between 2 given nodes
	// this function currently only applies for 1D beam
	public function is_between($n1, $n2) {
		if ( ($this -> compare($n1) == -1) && ($this -> compare($n2) == 1) ) { return true; }
		if ( ($this -> compare($n2) == -1) && ($this -> compare($n1) == 1) ) { return true; }
		return false;
	}

	// compare this node with a given node
	// return -1 if $this < $n1
	// return 0 if $this = $n1
	// return 1 if $this > $n1
	public function compare($n1) {
		$deltaValue = $this -> x - $n1 -> x;
		if ( (abs($deltaValue) > self::$delta) && ($deltaValue > 0) ) { return 1; }
		elseif ( abs($deltaValue) <= self::$delta ) { return 0; }
		else { return -1; }
	}

	// calculate the distance between this node and a node provided
	public function distance($n) {
		return abs($this -> x - $n -> x);
	}

	public static function quickSort($nodeList) {
		$length = count($nodeList);
		if ($length <= 1) { return $nodeList; }
		else {
			$pivot = $nodeList[0];
			$left = $right = array();

			for ($i = 1; $i < $length; $i++) {
				if ($pivot -> compare($nodeList[$i]) == 1) {
					$left[] = $nodeList[$i];
				} else {
					$right[] = $nodeList[$i];
				}
			}

			return array_merge(self::quickSort($left), array($pivot), self::quickSort($right));
		}
	}
}
?>