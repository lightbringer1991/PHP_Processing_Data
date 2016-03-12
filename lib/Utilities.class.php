<?php
class Utilities {
	// create 2D array(m,n) with predefined value
	public static function createArray($m, $n, $value = 0) {
		$arr = array();
		for ($i = 0; $i < $m; $i++) {
			for ($j = 0; $j < $n; $j++) {
				$arr[$i][$j] = $value;
			}
		}
		return $arr;
	}

	// if show on web browser, 	$ch_newLine = "<br />"
	// 							$ch_delimiter = "\t"
	public static function showArray($arr, $m, $n = 1, $ch_newLine = "\n", $ch_delimiter = " ") {
		if ($n == 1) {
			for ($x = 0; $x < $m; $x++) {
				self::showLongFormat($arr[$x], $ch_delimiter);
				echo $ch_newLine;
			}
		} else {
			$k = 0;
			while ($k < $n) {
				$k1 = $k + 8;
				if ($k1 > $n) { $k1 = $n; }
				printf("Column from %d to %d $ch_newLine", $k+1, $k1);
				for ($x = 0; $x < $m; $x++)  {
					for ($y = $k; $y < $k1; $y++) {
						self::showLongFormat($arr[$x][$y], $ch_delimiter);
					}
					echo $ch_newLine;
				}
				$k += 8;
			}
		}
	}

	public static function showLongFormat($a, $ch_delimiter = " ") {
		$l = printf($a);
		for ($i = 0; $i < 20 - $l; $i++) {
			echo $ch_delimiter;
		}
	}

	public static function removeRow($arr, $xv) {
		$v = 0;
		$A = array();
		for ($i = 0; $i < count($arr) - count($xv); $i++) {
			if (isset($xv[$v]) && ($xv[$v] == $i + $v)) {
			// if ($xv[$v] == $i + $v) {
				$v++;
			}
			$A[$i] = $arr[$i + $v];
		}
		return $A;
	}

	public static function removeCol($arr, $xv) {
		$A = array();
		for ($i = 0; $i < count($arr); $i++) {
			$A[$i] = self::removeRow($arr[$i], $xv);
		}
		return $A;
	}

	public static function replaceVec($arr, $xv, $v) {
		$A = $arr;
		for ($i = 0; $i < count($v); $i++) {
			$A[$xv[$i]] = $v[$i];
		}
		return $A;
	}

	public static function LUPsolve($src, $b) {
		$rows = count($b);
		$X = array_fill(0, $rows, 0.0);
		$P = array_fill(0, $rows, 0);
		$k = 0;
		$kd = 0;
		$T = 0;
		$t = 0.0;
		$Y = array_fill(0, $rows, 0.0);

		$A = self::createArray($rows, $rows);
		for ($i = 0; $i < $rows; $i++) {
			for ($j = 0; $j < $rows; $j++) {
				$A[$i][$j] = $src[$i][$j];
			}
		}

		//initialise X,Y
		for ($n = 0; $n < $rows; $n++) {
			$X[$n] = 0;
			$Y[$n] = 0;
		}
		//decompose
		for ($i = 0; $i < $rows; $i++) {
			$P[$i] = $i;
		}
		for ($k = 0; $k < $rows; $k++) {
			$p = 0;
			for ($i = $k; $i < $rows; $i++) {
				$t = $A[$i][$k];
				if ($t < 0) { $t = -$t; }
				if ($t > $p) {
					$p = $t;
					$kd = $i;
				}
			}
			if ($p == 0) {
				printf("singular Matrix, no possible solutions\n");
				return;
			}

			$T = $P[$kd];
			$P[$kd] = $P[$k];
			$P[$k] = $T;
			for ($i = 0; $i < $rows; $i++) {
				$t = $A[$kd][$i];
				$A[$kd][$i] = $A[$k][$i];
				$A[$k][$i] = $t;
			}
			for ($i = $k + 1; $i < $rows; $i++) {
				$A[$i][$k] = $A[$i][$k] / $A[$k][$k];
				for ($j = $k + 1; $j < $rows; $j++) {
					$A[$i][$j] = $A[$i][$j] - $A[$i][$k] * $A[$k][$j];
				}
			}
		}

		//now solve
		for ($n = 0; $n < $rows; $n++) {
			$t = 0;
			for ($m = 0; $m < $n; $m++) {
				$t += $A[$n][$m] * $Y[$m];
			}
			$Y[$n] = $b[$P[$n]] - $t;
		}
		for ($n = $rows - 1; $n >= 0; $n--) {
			$t = 0;
			for ($m = $n + 1; $m < $rows; $m++) {
				$t += $A[$n][$m] * $X[$m];
			}
			$X[$n] = ($Y[$n] - $t) / $A[$n][$n];
		}
		//X now contains the solution;
		return $X;
	}

	public static function writeToFile($fileName, $content) {
		$fh = fopen($fileName, 'w');
		fwrite($fh, $content);
		fclose($fh);
	}
}
?>