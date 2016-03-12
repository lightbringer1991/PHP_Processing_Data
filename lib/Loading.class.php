<?php
class Loading {
	public $fx;
	public $fy;
	public $fz;
	public $mx;
	public $my;
	public $mz;

	public function __construct($fx = 0, $fy = 0, $fz = null, $mx = 0, $my = null, $mz = 0) {
		$this -> fx = $fx;
		$this -> fy = $fy;
		$this -> fz = $fz;
		$this -> mx = $mx;
		$this -> my = $my;
		$this -> mz = $mz;
	}

	public function exportToArray() {
		// $arr = array($this -> fx, $this -> fy, $this -> fz, $this -> mx, $this -> my, $this -> mz);
		$arr = array($this -> fz, $this -> my);
		for ($i = 0; $i < count($arr); $i++) {
			if ($arr[$i] == null) { $arr[$i] = 0; }
		}
		return $arr;
	}

	// assuming $nodeList is sorted in ascending order
	public static function calculateLoading($nodeList, $fs, $fe) {
		// get distance of each node to its neighbors
		$eArray = array();
		$totalLength = 0;
		for ($i = 1; $i < count($nodeList); $i++) {
			$eArray[$i - 1] = $nodeList[$i] -> elementEnd -> getLength();
			$totalLength += $nodeList[$i] -> elementEnd -> getLength();
		}

		// get all f for each node
		$fArray = array($fs);
		for ($i = 1; $i < count($nodeList) - 1; $i++) {
			$fArray[$i] = $fs + ($fe - $fs) / $totalLength * self::sumArray($eArray, 0, $i - 1);
		}
		// generate last element
		$fArray[] = $fe;
		// print var_dump($fArray);
		// print var_dump($eArray);


		// check if there is a node with 0 value
		// if there is, split the array into 2, recursively run calculateLoading() again with 2 sections
		$zeroIndex = -1;
		for ($i = 0; $i < count($fArray); $i++) {
			if ($fArray[$i] < Node::$delta) {
				$zeroIndex = $i;
				break;
			}
		}
		if ( ($zeroIndex != -1) && ($zeroIndex != 0) && ($zeroIndex != count($nodeList) - 1) ) {
			$arr1 = array();
			$arr2 = array($nodeList[$zeroIndex]);
			for ($i = 0; $i < count($nodeList); $i++) {
				if ($i <= $zeroIndex) {
					array_push($arr1, $nodeList[$i]);
				} else {
					array_push($arr2, $nodeList[$i]);
				}
			}
			$vArray1 = self::calculateLoading($arr1, $fs, 0);
			$vArray2 = self::calculateLoading($arr2, 0, $fe);

			// unshift the first index of $vArray2, as it will be the zero node, add to $arr1
			$vArray2zero = array_shift($vArray2);
			$vArray1[count($vArray1) - 1] += $vArray2zero;
			return array_merge($vArray1, $vArray2);
		}

		// generate V at all nodes
		// calculate V at start node
		$vs = $fArray[1] * $eArray[0] / 2 + ($fArray[0] - $fArray[1])  * $eArray[0] / 3;
		$vArray = array($vs);
		// generate V at all nodes except the last node
		for ($i = 1; $i < count($nodeList) - 1; $i++) {
			$A = $fArray[$i] * $eArray[$i - 1] / 2;
			$B = ($fArray[$i - 1] - $fArray[$i]) * $eArray[$i - 1] / 2;
			$C = $fArray[$i + 1] * $eArray[$i] / 2;
			$D = ($fArray[$i] - $fArray[$i + 1]) * $eArray[$i] / 2;

			$vi = $A + $B / 3 + $C + $D * 2 / 3;
			array_push($vArray, $vi);
		}
		// calculate V at end node
		$endIndex = count($nodeList) - 1;
		$ve = $fArray[$endIndex] * $eArray[$endIndex - 1] / 2 + ($fArray[$endIndex - 1] - $fArray[$endIndex]) * $eArray[$endIndex - 1] / 6;
		array_push($vArray, $ve);
		
		return $vArray;
	}

	private static function sumArray($arr, $start, $end) {
		$total = 0;
		for ($i = $start; $i <= $end; $i++) {
			$total += $arr[$i];
		}
		return $total;
	}
}
?>
