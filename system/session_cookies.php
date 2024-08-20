<?php
defined('_VALID_MOS') or die('Restricted access');

if(!isset($_SESSION["uActive"])){
	// check if exist cookie
	if (isset($_COOKIE['uActive'])) {
		echo "<h1>reasignado session</h1>";
		$_SESSION["uId"]    = $_COOKIE['uId'] ?? null;
		$_SESSION["uName"]  = $_COOKIE['uName'] ?? null;
		$_SESSION["uLocation"]= $_COOKIE['uLocation'] ?? null;
		$_SESSION["uLocationDefault"]= $_COOKIE['uLocationDefault'] ?? null;
		$_SESSION["uActive"]= $_COOKIE['uActive'] ?? null;
		$_SESSION["uMarker"]= $_COOKIE['uMarker'] ?? null;
	} else {
		header('Location: '.BASE_URL.'/admin');
		die();
	}
}

if(isset($_SESSION['uLocation'])){
	$_SESSION['uLocation'] = $_SESSION['uLocation'];
}else{
	$_SESSION['uLocation'] = $_SESSION['uLocationDefault'];
}
$desc_loc = ($_SESSION['uLocation']==1)? ' - Tlaquiltenango':' - Zacatepec';