<?php
require_once "Node.class.php";
require_once "Connect.class.php";

class Mesh_1D {
	private $rawData;
	public $nodes;
	public $connections;
	public $el_max;			// element max length

	// data is retrieved from Database::getData() function
	public function __construct($data, $el_max) {
		$this -> rawData = $data;
		$this -> nodes = array();
		$this -> connections = array();
		$this -> el_max = $el_max;
	}

	public function run() {
		// generate hard nodes
		$this -> getHardNodes_geometry();
		$this -> getHardNodes_support();
		$this -> getHardNodes_ilcs();
		$this -> generateNodes();
		
		// generate elements
		$this -> generateElements();

		// // generate loadings
		$this -> generateLoadings();

	}

	// get all nodes within an ilcs
	public function getNodesByIlcs($ilcsObj) {
		$nodeList = array();
		$nodes = $ilcsObj -> getNodes();
		if (count($nodes) > 1) {
			$i = 0;
			while ( ($i < count($this -> nodes)) && ($this -> nodes[$i] -> compare($nodes[0]) != 0) ) { $i++; }
			while ( ($i < count($this -> nodes)) && ($this -> nodes[$i] -> compare($nodes[1]) != 0) ) {
				array_push($nodeList, $this -> nodes[$i]);
				$i++;
			}
			array_push($nodeList, $this -> nodes[$i]);
		} else {
			$i = 0;
			while ( ($i < count($this -> nodes)) && ($this -> nodes[$i] -> compare($nodes[0]) != 0) ) { $i++; }
			array_push($nodeList, $this -> nodes[$i]);
		}

		return $nodeList;
	}

	// must be executed after run() function
	public function toJSON() {
		$dataArray = array(
			'nodes' => array(),
			'elements' => array(),
			'ilc' => array(),
			'lc' => null
		);

		// generate nodes data
		for ($i = 0; $i < count($this -> nodes); $i++) {
			$nodeData = array(
				'id' => $i,
				'x' => $this -> nodes[$i] -> get('x'),
				'y' => $this -> nodes[$i] -> get('y'),
				'z' => $this -> nodes[$i] -> get('z')
			);
			array_push($dataArray['nodes'], $nodeData);
		}

		// generate connect (element) data
		for ($i = 0; $i < count($this -> connections); $i++) {
			$elementData = array(
				'id' => $i,
				'n_start' => array_search($this -> connections[$i] -> startNode, $this -> nodes),
				'n_end' => array_search($this -> connections[$i] -> endNode, $this -> nodes)
			);

			// get material information
			$materialObj = Material::getRecordByRcdNo($this -> connections[$i] -> PK4ba_mat );
			if ($materialObj != null) {
				$elementData['E'] = $materialObj -> get('E');
				$elementData['Rho'] = $materialObj -> get('Rho');
				$elementData['G'] = $materialObj -> get('G');
			} else {
				$elementData['E'] = '';
				$elementData['Rho'] = '';
				$elementData['G'] = '';
			}

			// get section information
			$sectionObj = Section::getRecordByRcdNo($this -> connections[$i] -> PK4ba_g );
			if ($sectionObj != null) {
				$elementData['A_start'] = $sectionObj -> get('A_start');
				$elementData['A_end'] = $sectionObj -> get('A_end');
				$elementData['Ix_start'] = $sectionObj -> get('Ix_start');
				$elementData['Ix_end'] = $sectionObj -> get('Ix_end');
				$elementData['Iy_start'] = $sectionObj -> get('Iy_start');
				$elementData['Iy_end'] = $sectionObj -> get('Iy_end');
			} else {
				$elementData['A_start'] = '';
				$elementData['A_end'] = '';
				$elementData['Ix_start'] = '';
				$elementData['Ix_end'] = '';
				$elementData['Iy_start'] = '';
				$elementData['Iy_end'] = '';
			}
			array_push($dataArray['elements'], $elementData);
		}

		// generate ilc data
		for ($i = 0; $i < count($this -> nodes); $i++) {
			$ilcData = array(
				'node_id' => $i,
				'fz' => $this -> nodes[$i] -> loading -> fz,
				'my' => $this -> nodes[$i] -> loading -> my
			);
			array_push($dataArray['ilc'], $ilcData);
		}

		return json_encode($dataArray);
	}

	// functions getHardNodes_xxx() should always be run in order
	// get hard nodes from geometry data
	// this function assumes that the provided data is well-formed
	// i.e. end of a geometry is the beginning of another geometry
	// assuming data is for  1D beam
	private function getHardNodes_geometry() {
		foreach ($this -> rawData['geometry'] as $sectionObj) {
			$startNode = $sectionObj -> getStartNode();
			$endNode = $sectionObj -> getEndNode();
			$this -> addNode($startNode);
			$this -> addNode($endNode);
		}
		$this -> nodes = Node::quickSort($this -> nodes);
	}

	// get hard nodes from support data
	private function getHardNodes_support() {
		foreach ($this -> rawData['support'] as $supportObj) {
			$newNode = $supportObj -> getSupportNode();
			$this -> addNode($newNode);
		}
		$this -> nodes = Node::quickSort($this -> nodes);
	}

	// get hard nodes from loading data
	// geometry == Distributed => collect startLocation and endLocation, plus zeroLocation if startValue and endValue have different signs
	// geometry == Point => collect startLocation only
	private function getHardNodes_ilcs() {
		$nodeList = array();
		foreach ($this -> rawData['ilcs'] as $ilcsObj) {
			$nodeList = array_merge($nodeList, $ilcsObj -> getNodes());
		}
		foreach ($nodeList as $n) {
			$this -> addNode($n);
		}
		$this -> nodes = Node::quickSort($this -> nodes);
	}

	// generate extra nodes to fit $el_max data
	// this function assumes that node array are sorted in ascending order
	private function generateNodes() {
		$newNodeList = array();
		for ($i = 0; $i < count($this -> nodes) - 1; $i++) {
			$length = $this -> nodes[$i] -> distance($this -> nodes[$i + 1]);
			if ($length > $this -> el_max) {
				// generate new nodes
				$distance = $length / ceil($length / $this -> el_max);			// new distance between nodes
				$element = new Connect(clone($this -> nodes[$i]), clone($this -> nodes[$i]), 0, 0);
				do {
					$element -> increaseLength($distance);
					array_push($newNodeList, clone($element -> endNode));					
				}
				while ($element -> endNode -> compare($this -> nodes[$i + 1]) == -1);
			}
		}
		foreach ($newNodeList as $n) {
			$this -> addNode($n);
		}
		$this -> nodes = Node::quickSort($this -> nodes);
	}

	// only invoke this function AFTER all nodes are generated
	private function generateElements() {
		for ($i = 0; $i < count($this -> nodes) - 1; $i++) {
			// initiate values
			$startNode = $this -> nodes[$i];
			$endNode = $this -> nodes[$i + 1];
			$pk4ba_mat = 0;
			$pk4ba_g = 0;

			foreach ($this -> rawData['geometry'] as $sectionObj) {
				$sNode = $sectionObj -> getStartNode();
				$eNode = $sectionObj -> getEndNode();
				if ( ($startNode -> compare($sNode) >= 0) && ($endNode -> compare($eNode) <= 0) ) {
					$pk4ba_mat = $sectionObj -> get('matID');
					$pk4ba_g = $sectionObj -> get('rcdNo');
					break;
				}
			}
			$e = new Connect($startNode, $endNode, $pk4ba_mat, $pk4ba_g);
			$startNode -> elementStart = $e;
			$endNode -> elementEnd = $e;
			array_push($this -> connections, $e);
		}
	}

	// only invoke this function AFTER all nodes are generated
	private function generateLoadings() {
		// get all predefined loading data
		foreach ($this -> nodes as $n) {
			foreach ($this -> rawData['ilcs'] as $ilcsObj) {
				if ( ($ilcsObj -> get('geometry') == 'Point') 
					&& ($ilcsObj -> get('component') == 'FX') 
					&& ($ilcsObj -> get('startLocation') == $n -> get('y')) ) {
						$n -> loading -> fz = $ilcsObj -> get('startValue');
				}
				if ( ($ilcsObj -> get('geometry') == 'Point') 
					&& ($ilcsObj -> get('component') == 'MZ') 
					&& ($ilcsObj -> get('startLocation') == $n -> get('y')) ) {
						$n -> loading -> my = $ilcsObj -> get('startValue');
				}
			}
		}

		// begin calculating the rest
		foreach ($this -> rawData['ilcs'] as $ilcsObj) {

			if ($ilcsObj -> get('geometry') == 'Distributed') {
				$startNode = new Node( array('y' => doubleval($ilcsObj -> get('startLocation'))) );
				$endNode = new Node( array('y' => doubleval($ilcsObj -> get('endLocation'))) );
				$nodeList = $this -> getAllNodesBetweenNodes($startNode, $endNode);

				$vList = Loading::calculateLoading($nodeList, $ilcsObj -> get('startValue'), $ilcsObj -> get('endValue'));

				for ($i = 0; $i < count($nodeList); $i++) {
					if ($ilcsObj -> get('component') == 'FX') {
						if ($nodeList[$i] -> loading -> fz == null) {
							$nodeList[$i] -> loading -> fz = 0;
						}
						$nodeList[$i] -> loading -> fz += $vList[$i];
					} elseif ($ilcsObj -> get('component') == 'MZ') {
						if ($nodeList[$i] -> loading -> my == null) {
							$nodeList[$i] -> loading -> my = 0;
						}
						$nodeList[$i] -> loading -> my += $vList[$i];
					}
				}
			}
		}
	}

	// ignore new node if it's already in the list
	// otherwise add it in
	private function addNode($newNode) {
		// prevent duplication
		foreach ($this -> nodes as $n) {
			if ($n -> compare($newNode) == 0) { return; }
		}
		array_push($this -> nodes, $newNode);
	}

	// $data: a row from loading table
	// private function calculateZeroPosition($data) {
	// 	$loadingRatio = abs($data['startValue'] / $data['endValue']);	//2.4
	// 	$loadingLength = $data['endLocation'] - $data['startLocation'];
	// 	$zeroPoint = $data['startLocation'] + ($loadingLength * $loadingRatio) / (1 + $loadingRatio);
	// 	return new Node($zeroPoint, 0, 0);
	// }

	// obtain the list of nodes between 2 given nodes, sorted in order
	private function getAllNodesBetweenNodes($n1, $n2) {
		$output = array();
		foreach ($this -> nodes as $n) {
			if ( ($n1 -> compare($n) <= 0) && ($n2 -> compare($n) >= 0) ) { array_push($output, $n); }
		}
		return Node::quickSort($output);
	}
}
?>