<?php
//© 2022 Dynamic Data
$appPath	= realpath("/storage/DD/Apps/RIP/Enable.php");
if ($appPath !== false) {
	require_once $appPath;
	$testObj	= new \DD\RIP\Facts\Test();
	$testObj->execute();
} else {
	die("DD\RIP missing");
}