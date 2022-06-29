<?php
//© 2022 Dynamic Data
namespace DD\RIP\Facts;

class Test extends Base
{
	public function execute()
	{
		
	
		
		$rData		= array();
// 		$toolObj	= \MTM\Mikrotik\Factories::getTools()->getNetInstall();
		
// 		$ip		= "10.169.65.254";
// 		$mac	= "ee:00:00:00:00:18";
// 		$toolObj->setTxConfig($ip, $mac);
		
// 		$devObjs	= $toolObj->getDeviceList();
// 		print_r($devObjs);

		$host			= "10.169.65.1";
		$username		= "ripService";
		$username		= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($username);
		$password		= "Merlin88";
		$port			= 1122;
		$ctrlObj		= \MTM\SSH\Factories::getShells()->passwordAuthentication($host, $username, $password, null, $port);

		$secMacAddr		= "DC:2C:6E:E4:5A:12";
		$secUsername	= "admin";
		$secPassword	= "";
		$secUsername	= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($secUsername);
		$ctrlObj		= \MTM\MacTelnet\Factories::getShells()->passwordAuthentication($secMacAddr, $secUsername, $secPassword, $ctrlObj);

		
// 		$username	= "martin_adm";
// 		$password	= "TtPr0Me1E@";
		
// 		$cmdStr		= "/user/remove [find where name=\"".$username."\"];";
// 		$data		= trim($ctrlObj->getCmd($cmdStr)->get());
		
// 		$cmdStr		= "/user/add name=\"".$username."\" password=\"".$password."\" group=\"full\";";
// 		$data		= $ctrlObj->getCmd($cmdStr)->get();

		
		
		$cmdStr			= ":put [/system/routerboard/get model];";
		$modelNbr		= trim($ctrlObj->getCmd($cmdStr)->get());
		$rData[]		= $modelNbr;
		
		
		$cmdStr			= ":put [/system/license/get software-id];";
		$license		= trim($ctrlObj->getCmd($cmdStr)->get());
		$rData[]		= $license;
		
		if ($modelNbr === "RBD52G-5HacD2HnD") {
			$hashTool	= \MTM\Utilities\Factories::getStrings()->getHashing();
			$devNbr		= $hashTool->getAsInteger(hash("sha256", $license."qefqwegh23hh5hFytdf"), 99999);
			if ($devNbr < 20000) {
				$devNbr		= $hashTool->getAsIntegerV2(hash("sha256", $license."qefqwegh23hh5hFytdf"), 99999);
				if ($devNbr < 20000) {
					throw new \Exception("Device Nbr too low");
				}
			}
			
			$identity	= "ap-".$devNbr.".lionstripe.com";
			
		} else {
			throw new \Exception("Not handled for model: '".$modelNbr."'");
		}
		$rData[]		= $identity;
		
		$cmdStr			= "/tool fetch url=\"https://dac-test-grp.dynamic-data.io/api/v1/Provisioning/Get/RouterOSv7/RpsInitial/8E5ECB0A-B39E-68B1-450E-EEE820667841/\" port=47480 mode=https user=\"".getenv("dd-dac.api.user")."\" password=\"".getenv("dd-dac.api.pass")."\" http-method=get output=file as-value dst-path=flash/RPS/primary.rsc";
		$iData			= trim($ctrlObj->getCmd($cmdStr)->get());
		

		$rData[]		= $iData;
		
		
		
// 		$cmdStr		= "/ip/dhcp-client/print";
// 		$data		= trim($ctrlObj->getCmd($cmdStr)->get());
// 		if ($data == "") {
// 			$cmdStr		= "/ip/dhcp-client/add interface=ether1 disabled=no add-default-route=yes default-route-distance=250";
// 			$ctrlObj->getCmd($cmdStr)->get();
		
// 			$cmdStr		= "/ip/dhcp-client/print";
// 			$data		= trim($ctrlObj->getCmd($cmdStr)->get());
// 		}
		
		
		// 		
		
// 		$rawData	= $cmdObj->getData();
// 		$rData[]	= $pData;
		
// 		$cmdStr		= "quit";
// 		$regEx		= "Welcome back!";
// 		$rData[]	= $ctrlObj->getCmd($cmdStr, $regEx)->get();
	
		
		echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
// 		var_dump($data);
		echo "\n 2222 \n";
// 		print_r($rawData);
		echo "\n 3333 \n";
		print_r($rData);
		echo "\n ".time()."</pre></code> \n ";
		die("end");
		
// 		echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
// 		//var_dump($_SERVER);
// 		echo "\n 2222 \n";
// 		//print_r($_GET);
// 		echo "\n 3333 \n";
// // 		print_r($rData);
// 		echo "\n ".time()."</pre></code> \n ";
// 		die("end");
	}
}