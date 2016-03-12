<?php
require "PolynomialEquation.class.php";
require "PolynomialTerm.class.php";
require "Mesh_1D.class.php";
require "Utilities.class.php";

class FEA {
	// data from Database::getData() function
	private $rawData;
	public $mesh;

	public function __construct($data, $el_max) {
		$this -> rawData = $data;
		$this -> mesh = new Mesh_1D($data, $el_max);
		$this -> mesh -> run();
	}

	// F = array($totalNodes * 6) = {fx_1, fy_1, fz_1, mx_1, my_1, mz_1, fx_2, fy_2, ...}
	// $ilcList: list of ilcs to run analysis on
	// empty($ilcsList) => run all ilcs
	public function generateF($ilcsList = array()) {
		$nodeList = array();
		if (empty($ilcsList)) { $nodeList = $this -> mesh -> nodes; }
		else {
			foreach ($ilcsList as $ilcsObj) {
				$nodeList = array_merge($this -> mesh -> getNodesByIlcs($ilcsObj));
			}
			$nodeList = Node::quickSort($nodeList);			
		}

		$output = array();
		foreach ($nodeList as $n) {
			$output = array_merge( $output, $n -> loading -> exportToArray() );
		}
		return $output;
	}

	// generate $k_e for an element
	public function generateKE_1($elementObj) {
		$db = new Database();

		// later use, get A and I from ba_sections
		$sectionObj = Section::getRecordByRcdNo($elementObj -> PK4ba_g);
		$A = array(
			'A_start' => 0,
			'A_end' => 0
		);
		$I = array(
			'Ix_start' => 0,
			'Ix_end' => 0,
			'Iy_start' => 0,
			'Iy_end' => 0
		);
		if ($sectionObj != null) {
			$A = array(
				'A_start' => $sectionObj -> get('A_start'),
				'A_end' => $sectionObj -> get('A_end')
			);
			$I = array(
				'Ix_start' => $sectionObj -> get('Ix_start'),
				'Ix_end' => $sectionObj -> get('Ix_end'),
				'Iy_start' => $sectionObj -> get('Iy_start'),
				'Iy_end' => $sectionObj -> get('Iy_end')
			);
		}


		// get E
		if ($elementObj -> PK4ba_mat == 2) { $materialObj = Material::getRecordByRcdNo(22); }
		elseif ($elementObj -> PK4ba_mat == 0) { $materialObj = Material::getRecordByRcdNo(23); }
		$E = ($materialObj != null) ? $materialObj -> get('E') : 0;
		
		$I = $elementObj -> PK4ba_g;
		$EI = $E * $I;
		$le = $elementObj -> getLength();
		$v1 = $EI * 12 / pow($le, 3);
		$v2 = $EI * 6 / pow($le, 2);
		$v3 = $EI * 4 / $le;
		$v4 = pow($EI, 2) / $le;

		$k_e = array(
			array($v1, $v2, -$v1, $v2),
			array($v2, $v3, -$v2, $v4),
			array(-$v1, -$v2, $v1, -$v2),
			array($v2, $v4, -$v2, $v3)
		);
		return $k_e;
	}

	// generate $k_e for an element with polynomial calculation
	public function generateKE_2($elementObj) {
		$k_e = array();
		$length = $elementObj -> getLength();

		$N = array(
			new \Polynomial\Equation(array( 2/pow($length, 3), -3/pow($length, 2), 0, 1)),
			new \Polynomial\Equation(array( 1/pow($length, 2), -2/$length 	   	 , 1, 0)),
			new \Polynomial\Equation(array(-2/pow($length, 3),  3/pow($length, 2), 0, 0)),
			new \Polynomial\Equation(array( 1/pow($length, 2), -1/$length        , 0, 0))
		);
		$N2 = array();
		for($i = 0; $i < count($N); $i++) {
			$N2[$i] = $N[$i]->getDerivative(2);
		}

		$t = $length * ($i - 1);
		$eq = new \Polynomial\Equation(array(1, 2 * $t, $t * $t + 1));
		for ($i = 0; $i < count($N2); $i++) {
			for ($j = 0; $j < count($N2); $j++) {
				$equation = $N2[$i];
				$equation = $equation -> multiplyBy($eq);
				$equation = $equation -> multiplyBy($N2[$j]);
				$equation = $equation -> getIntegral();
				$k_e[$i][$j] = $equation -> evaluateFor($length) - $equation -> evaluateFor(0);
			}
		}

		return $k_e;
	}

	public function assembleKMatrix() {
		// total of degree of freedoms
		$ndof = (count($this -> mesh -> connections) + 1) * 2;

		$K = Utilities::createArray($ndof, $ndof);

		for ($i = 0; $i < count($this -> mesh -> connections); $i++) {
			$k_e = $this -> generateKE_2($this -> mesh -> connections[$i]);
			for ($row = 0; $row < 4; $row++) {
				$row_g = $i * 2 + $row;
				for ($col = 0; $col < 4; $col++) {
					$col_g = $i * 2 + $col;
					$K[$row_g][$col_g] += $k_e[$row][$col];
				}
			}
		}
		return $K;
	}

	// perform FEA analysis
	// $ilcList: list of ilcs to run analysis on
	// empty($ilcsList) => run all ilcs
	public function runAnalysis($ilcsList = array()) {
		// get Beam length
		$connect = new Connect($this -> mesh -> nodes[0], $this -> mesh -> nodes[count($this -> mesh -> nodes) - 1], 0, 0);
		$beamLength = $connect -> getLength();

		$ndof = (count($this -> mesh -> connections) + 1) * 2;
		$xv = array(0, $ndof - 2);
		$K = $this -> assembleKMatrix();
		$F = $this -> generateF($ilcsList);

		// calculations
		$K = Utilities::removeRow($K, $xv);
		$K = Utilities::removeCol($K, $xv);
		$F = Utilities::removeRow($F, $xv);
		$w0 = Utilities::LUPsolve($K, $F);

		$w1 = array_fill(0, $ndof, 0);
		$xv1 = array_fill(0, $ndof, 0);
		for ($i = 0; $i < $ndof; $i++) { $xv1[$i] = $i; }
		$xv1 = Utilities::removeRow($xv1, $xv);
		$w1 = Utilities::replaceVec($w1, $xv1, $w0);

		$c = 99;
		$w4 = array();
		for ($i = 0; $i <= $c; $i++)  {
			$xi = $i * $beamLength / $c;
			$w4[$i] = 0.01 * pow($xi, 5) - (1 / 30) * pow($xi, 3) + (7 / 300) * $xi;
		}

		// display zone
		// echo "\nK = \n\n";
		// Utilities::showArray($K, count($K), count($K[0]));

		// echo "\nF = \n\n";
		// Utilities::showArray($F, count($F));

		// print "<pre>";
		// print_r($w0);
		// print "</pre>";

		// echo "\nw1 = <br /><br />";
		// Utilities::showArray($w1, $ndof, 1, "<br />");

		// echo "<br />w4 = <br /><br />";
		// Utilities::showArray($w4, $c + 1, 1, "<br />");

		return $w0;
	}
}
?>
