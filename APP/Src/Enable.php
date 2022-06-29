<?php
//© 2022 Dynamic Data
if (defined("DD_RIP_BASE_PATH") === false) {
	define("DD_RIP_BASE_PATH", __DIR__ . DIRECTORY_SEPARATOR);
	spl_autoload_register(function($className)
	{
		if (class_exists($className) === false) {
			$cPath		= array_values(array_filter(explode("\\", $className)));
			if (array_shift($cPath) == "DD" && array_shift($cPath) == "RIP") {
				$filePath	= DD_RIP_BASE_PATH . implode(DIRECTORY_SEPARATOR, $cPath) . ".php";
				if (is_readable($filePath) === true) {
					require_once $filePath;
				}
			}
		}
	});
	
	$envPath	= realpath(DD_RIP_BASE_PATH . "env.txt");
	if (is_string($envPath) === true && is_readable($envPath) === true) {
		$lines	= array_map("trim", explode("\n", file_get_contents($envPath)));
		foreach ($lines as $line) {
			if (($eqPos = strpos($line, "=")) !== false && strpos($line, "#") !== 0) {
				$name	= substr($line, 0, $eqPos);
				if (getenv($name) === false) {
					putenv($line);
				}
			}
		}
		
	} else {
		throw new \Exception("Environment file path is invalid: ".$envPath);
	}
	
	$appsBase	= realpath(DD_RIP_BASE_PATH . "..") ."/";
	require_once $appsBase."CC/Enable.php";
// 	require_once $appsBase."CSC/Enable.php";
	
	$apisBase	= realpath(DD_RIP_BASE_PATH . "../../Apis") ."/";
	require_once $apisBase."DACApi/Enable.php";
// 	require_once $apisBase."CMSApi/Enable.php";
// 	require_once $apisBase."CASApi/Enable.php";
	
	$mtmBase	= realpath(DD_RIP_BASE_PATH . "../../../MTM") ."/";
	require_once $mtmBase."mtm-utilities/Enable.php";
	require_once $mtmBase."mtm-fs/Enable.php";
	require_once $mtmBase."mtm-shells/Enable.php";
	require_once $mtmBase."mtm-network/Enable.php";
	require_once $mtmBase."mtm-ssh/Enable.php";
	require_once $mtmBase."mtm-mactelnet/Enable.php";
// 	require_once $mtmBase."mtm-mikrotik/Enable.php";
// 	require_once $mtmBase."mtm-redis/Enable.php";
// 	require_once $mtmBase."mtm-database/Enable.php";
// 	require_once $mtmBase."mtm-async/Enable.php";
	require_once $mtmBase."mtm-certs/Enable.php";
	require_once $mtmBase."mtm-encrypt/Enable.php";
	require_once $mtmBase."mtm-spreadsheet/Enable.php";
	require_once $mtmBase."mtm-ws-socket/Enable.php";
}