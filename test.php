<html>
<head>
	<title>PHP Processing Data</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous" />
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
	<script src="https://code.highcharts.com"></script>
</head>
<body>

<?php
require_once 'lib/Database.class.php';
require_once 'lib/FEA.class.php';
require_once 'lib/Utilities.class.php';
require_once 'lib/Lcs.class.php';

// predefined variables
$userscalcPK = 14;
$el_max = 0.5;

$db = new Database();
$data = $db -> getData($userscalcPK);

$fea = new FEA($data, $el_max);

echo "<h2 style='color: red;'>Test Mesh data </h2>";
?>
<h3 style='color: blue;'>Data extracted for userscalcPK=<?php echo $userscalcPK; ?> AND el_max=<?php echo $el_max; ?></h3>
<div class='row'>
	<div class='col-sm-offset-1 col-md-offset-1 col-lg-offset-1 col-sm-6 col-md-6 col-lg-6'>
	<table class='table table-bordered table-striped'>
		<thead>
		<tr>
			<th class='col-sm-3 col-md-3 col-lg-3'>Node ID</th>
			<th class='col-sm-3 col-md-3 col-lg-3'>X</th>
			<th class='col-sm-3 col-md-3 col-lg-3'>Y</th>
			<th class='col-sm-3 col-md-3 col-lg-3'>Z</th>
		</tr>
		</thead>
		<tbody>
<?php
for ($i = 0; $i < count($fea -> mesh -> nodes); $i++) {
	echo "	<tr>
				<td>$i</td>
				<td>" . $fea -> mesh -> nodes[$i] -> get('x') . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> get('y') . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> get('z') . "</td>
			</tr>";
}
?>
		</tbody>
	</table>
	</div>
</div>

<div class='row'>
	<div class='col-sm-offset-1 col-md-offset-1 col-lg-offset-1 col-sm-8 col-md-8 col-lg-8'>
	<table class='table table-bordered table-striped'>
		<thead>
		<tr>
			<th class='col-sm-2 col-md-2 col-lg-2'>Element ID</th>
			<th class='col-sm-2 col-md-2 col-lg-2'>Start Node ID</th>
			<th class='col-sm-2 col-md-2 col-lg-2'>End Node ID</th>
			<th class='col-sm-3 col-md-3 col-lg-3'>PK4ba_mat</th>
			<th class='col-sm-3 col-md-3 col-lg-3'>PK4ba_g</th>
		</tr>
		</thead>
		<tbody>
<?php
for ($i = 0; $i < count($fea -> mesh -> nodes) - 1; $i++) {
	echo "	<tr>
				<td>" . $i . "</td>
				<td>" . array_search($fea -> mesh -> nodes[$i] -> elementStart -> startNode, $fea -> mesh -> nodes) . "</td>
				<td>" . array_search($fea -> mesh -> nodes[$i] -> elementStart -> endNode, $fea -> mesh -> nodes) . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> elementStart -> PK4ba_mat . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> elementStart -> PK4ba_g . "</td>
			</tr>";
}
?>
		</tbody>
	</table>
	</div>
</div>

<div class='row'>
	<div class='col-sm-offset-1 col-md-offset-1 col-lg-offset-1 col-sm-8 col-md-8 col-lg-8'>
	<table class='table table-bordered table-striped'>
		<thead>
		<tr>
			<th class='col-sm-1 col-md-1 col-lg-1'>Node ID</th>
			<th class='col-sm-1 col-md-1 col-lg-1'>fx</th>
			<th class='col-sm-2 col-md-2 col-lg-2'>fy</th>
			<th class='col-sm-2 col-md-3 col-lg-2'>fz</th>
			<th class='col-sm-2 col-md-3 col-lg-2'>mx</th>
			<th class='col-sm-2 col-md-3 col-lg-2'>my</th>
			<th class='col-sm-2 col-md-3 col-lg-2'>mz</th>
		</tr>
		</thead>
		<tbody>
<?php
for ($i = 0; $i < count($fea -> mesh -> nodes); $i++) {
	echo "	<tr>
				<td>$i</td>
				<td>" . $fea -> mesh -> nodes[$i] -> loading -> fx . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> loading -> fy . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> loading -> fz . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> loading -> mx . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> loading -> my . "</td>
				<td>" . $fea -> mesh -> nodes[$i] -> loading -> mz . "</td>
			</tr>";
}
?>
		</tbody>
	</table>
	</div>
</div>

<h2 style='color: red;'>Test Generate F</h2>
<h3 style='color: blue;'>Generate F on 1 ilcs geometry=Point</h3>
<?php
$ilcsList = Ilcs::getAllRecordsByUsersCalcPK($userscalcPK);
$lcsObj = Lcs::getRecordByRcdNo(25);
?>
<b>Ilcs data</b>
<?php echo var_dump($ilcsList[0]); ?>
<b>F</b>
<?php echo var_dump( $fea -> generateF(array($ilcsList[0])) ); ?>

<h3 style='color: blue;'>Generate F on 1 ilcs geometry=Distributed</h3>
<b>Ilcs data</b>
<?php echo var_dump($ilcsList[1]); ?>
<b>F</b>
<?php echo var_dump( $fea -> generateF(array($ilcsList[1])) ); ?>

<h3 style='color: blue;'>Generate F on 1 lcs</h3>
<b>ilcs ids</b>
<?php echo $lcsObj -> get('params') . "<br />"; ?>
<b>F</b>
<?php 
	echo var_dump($fea -> generateF($lcsObj -> getIlcsList()));
?>


<h2 style='color: red;'>Test runAnalysis</h2>
<h3 style='color: blue;'>Run analysis on 2 ilcs rcd 93 and 94</h3>
<?php echo var_dump($ilcsList[1]); ?>
<?php echo var_dump($ilcsList[2]); ?>
<b>Analysis</b>
<?php echo var_dump($fea -> runAnalysis(array( $ilcsList[1], $ilcsList[2] ))); ?>
<h3 style='color: blue;'>Run analysis on an lcs rcdNo=25</h3>
<?php echo var_dump($fea -> runAnalysis($lcsObj -> getIlcsList())); ?>

<h3 style='color: blue;'>Run analysis on the whole beam</h3>
<?php echo var_dump($fea -> runAnalysis()); ?>