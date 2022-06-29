<?php
//© 2022 Dynamic Data
$appPath	= realpath("/storage/DD/Apps/RIP/Enable.php");
if ($appPath !== false) {
	require_once $appPath;
	$workObj	= new \DD\RIP\Process\Ctrl();
	$workObj->run();
} else {
	die("DD\RIP missing");
}