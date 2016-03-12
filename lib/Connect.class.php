<?php
class Connect {
	public $startNode;
	public $endNode;
	public $PK4ba_mat;
	public $PK4ba_g;

	public function __construct($start, $end, $mat, $g) {
		$this -> startNode = $start;
		$this -> endNode = $end;
		$this -> PK4ba_mat = $mat;
		$this -> PK4ba_g = $g;
	}

	// applies only to 1D element
	public function increaseLength($length) {
		$y = $this -> endNode -> get('y');
		$this -> endNode -> set('y',  $y + $length);
	}

	public function getLength() {
		return abs($this -> endNode -> get('y') - $this -> startNode -> get('y'));
	}

	public function __toString() {
		return $this -> startNode -> get('y') . ", " . $this -> endNode -> get('y') . ", " . $this -> PK4ba_mat . ", " . $this -> PK4ba_g;
	}
}
?>