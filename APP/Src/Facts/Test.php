<?php
//© 2022 Dynamic Data
namespace DD\RIP\Facts;

class Test extends Base
{
	public function getJumpCtrl()
	{
		$jumpHost	= "10.169.65.1";
		$jumpUser	= "ripService";
		$jumpUser	= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($jumpUser);
		$jumpPass	= "Merlin88";
		$jumpPort	= 1122;
		return \MTM\SSH\Factories::getShells()->passwordAuthentication($jumpHost, $jumpUser, $jumpPass, null, $jumpPort);
	}
	public function getInitMacs()
	{
		$rData		= array();
		$rMacs		= array();
		$ctrlObj	= \MTM\Shells\Factories::getShells()->getBash();
		try {
			
			$cmdStr		= "mactelnet -l -B -t 10;";
			$data		= trim($ctrlObj->getCmd($cmdStr)->get());
			$lines		= \MTM\Spreadsheet\Factories::getCSV()->getTool()->getAsArray($data);
			array_shift($lines);
			array_shift($lines);
			foreach ($lines as $lId => $csv) {
				if ($csv[1] != "'rps-initializated'") {
					unset($lines[$lId]);
				}
			}
			foreach ($lines as $csv) {
				$mac	= "";
				foreach (explode(":", $csv[0]) as $pm) {
					if (strlen($pm) < 2) {
						$pm		= str_repeat("0", (2 - strlen($pm))).$pm;
					}
					$mac	.= $pm;
				}
				$rMacs[]	= strtoupper($mac);
			}
			$ctrlObj->terminate();
	
		} catch (\Exception $e) {
			try {
				$ctrlObj->terminate();
			} catch (\Exception $e) {
			}
			$rData[]	= "Exception";
			$rData[]	= $e->getMessage();
			$rData[]	= $e->getCode();
			$rData[]	= $e->getTraceAsString();
			echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
			//var_dump($_SERVER);
			echo "\n 2222 \n";
			//print_r($_GET);
			echo "\n 3333 \n";
			print_r($rData);
			echo "\n ".time()."</pre></code> \n ";
			die("end");
		}
		return $rMacs;
	}
	
	public function loopOnce()
	{
		$initUser		= "admin";
		$initPass		= "";
		$initUser		= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($initUser);
		$acctObj		= \DD\DACApi\Facts::getAccounts()->getBySession();
		$hashTool		= \MTM\Utilities\Factories::getStrings()->getHashing();
		$devTypeObj		= \DD\DACApi\Facts::getDeviceTypes()->getByLabel("routeros");
		
		$rData			= array();
		$initMacs		= $this->getInitMacs();
		if (count($initMacs) > 0) {
			echo "We have a hit\n";
		}
		foreach ($initMacs as $initMac) {
			$jumpCtrl		= $this->getJumpCtrl();
			try {
				$ctrlObj		= \MTM\MacTelnet\Factories::getShells()->passwordAuthentication($initMac, $initUser, $initPass, $jumpCtrl);
				try {
					
					$cmdStr			= ":put [/system/routerboard/get model];";
					$modelNbr		= trim($ctrlObj->getCmd($cmdStr)->get());
					
					echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
					//var_dump($_SERVER);
					echo "\n 2222 \n";
					//print_r($_GET);
					echo "\n 3333 \n";
					print_r($modelNbr);
					echo "\n ".time()."</pre></code> \n ";
					die("end");
					$cmdStr			= ":put [/system/license/get software-id];";
					$license		= trim($ctrlObj->getCmd($cmdStr)->get());

					$devNbr			= $hashTool->getAsInteger(hash("sha256", $license."qefqwegh23hh5hFytdf"), 99999);
					if ($devNbr < 20000) {
						$devNbr		= $hashTool->getAsIntegerV2(hash("sha256", $license."qefqwegh23hh5hFytdf"), 99999);
						if ($devNbr < 20000) {
							throw new \Exception("Device Nbr too low");
						}
					}
					if ($modelNbr === "RBD52G-5HacD2HnD") {
						$identity	= "ap-".$devNbr.".lionstripe.com";
					} else {
						throw new \Exception("Not handled for model: '".$modelNbr."'");
					}
					
					$devObj		= $acctObj->getDeviceByLabel($identity, false);
					if ($devObj === null) {
						$devObj		= $acctObj->addDevice($devTypeObj, $identity);
					}
					
					$clFact		= \DD\DACApi\Facts::getOvpnClusters();
					$clObjs		= array();
					$clObjs[]	= $clFact->getByLabel("de-eu-1");
					$clObjs[]	= $clFact->getByLabel("de-eu-2");
					foreach ($clObjs as $clObj) {
						$lnkObj	= $devObj->getOvpnLinkByCluster($clObj, false);
						if ($lnkObj === null) {
							$lnkObj	= $devObj->addOvpnLink($clObj);
						}
					}
					
					$cmdStr		= "/tool fetch url=\"https://dac-test-grp.dynamic-data.io/api/v1/Provisioning/Get/RouterOSv7/RpsInitial/".$devObj->getGuid()."/\"";
					$cmdStr		.= " port=47480 mode=https user=\"".getenv("dd-dac.api.user")."\"";
					$cmdStr		.= " password=\"".getenv("dd-dac.api.pass")."\"";
					$cmdStr		.= " http-method=get output=file as-value dst-path=flash/RPS/primary.rsc";
					$ctrlObj->getCmd($cmdStr)->get();
					
				} catch (\Exception $e) {
					try {
						$ctrlObj->terminate();
					} catch (\Exception $e) {
					}
					$rData[]	= "Exception";
					$rData[]	= $e->getMessage();
					$rData[]	= $e->getCode();
					$rData[]	= $e->getTraceAsString();
					echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
					//var_dump($_SERVER);
					echo "\n 2222 \n";
					//print_r($_GET);
					echo "\n 3333 \n";
					print_r($rData);
					echo "\n ".time()."</pre></code> \n ";
					die("end");
				}
				
				
			} catch (\Exception $e) {
				try {
					$jumpCtrl->terminate();
				} catch (\Exception $e) {
				}
				$rData[]	= "Exception";
				$rData[]	= $e->getMessage();
				$rData[]	= $e->getCode();
				$rData[]	= $e->getTraceAsString();
				echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
				//var_dump($_SERVER);
				echo "\n 2222 \n";
				//print_r($_GET);
				echo "\n 3333 \n";
				print_r($rData);
				echo "\n ".time()."</pre></code> \n ";
				die("end");
			}
			echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
			//var_dump($_SERVER);
			echo "\n 2222 \n";
			//print_r($_GET);
			echo "\n 3333 \n";
			print_r($initMac);
			echo "\n ".time()."</pre></code> \n ";
			die("end");
		} 
	}
	public function execute()
	{
		$rData		= array();
		try {
			
			
			///system/reset-configuration skip-backup=yes
// 			while (true) {
// 				$this->loopOnce();
// 			}
			$jumpHost		= "10.169.65.1";
			$jumpUser		= "ripService";
			$jumpUser		= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($jumpUser);
			$jumpPass		= "Merlin88";
			$jumpPort		= 1122;
			$jumpCtrl		= \MTM\SSH\Factories::getShells()->passwordAuthentication($jumpHost, $jumpUser, $jumpPass, null, $jumpPort);
			
			$initUser		= "admin";
			$initPass		= "";
			$initUser		= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($initUser);
			$ctrlObj		= \MTM\MacTelnet\Factories::getShells()->passwordAuthentication("DC2C6EE45A12", $initUser, $initPass, $jumpCtrl);

			$cmdStr			= ":put [/system/routerboard/get model];";
			$modelNbr		= trim($ctrlObj->getCmd($cmdStr)->get());
			echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
			//var_dump($_SERVER);
			echo "\n 2222 \n";
			print_r($modelNbr);
			echo "\n 3333 \n";
// 			print_r($devObj);
			echo "\n ".time()."</pre></code> \n ";
			die("end");
			
			
			
	// 		$toolObj	= \MTM\Mikrotik\Factories::getTools()->getNetInstall();
			
	// 		$ip		= "10.169.65.254";
	// 		$mac	= "ee:00:00:00:00:18";
	// 		$toolObj->setTxConfig($ip, $mac);
			
	// 		$devObjs	= $toolObj->getDeviceList();
	// 		print_r($devObjs);
	
			
	
			
	// 		$username	= "martin_adm";
	// 		$password	= "TtPr0Me1E@";
			
	// 		$cmdStr		= "/user/remove [find where name=\"".$username."\"];";
	// 		$data		= trim($ctrlObj->getCmd($cmdStr)->get());
			
	// 		$cmdStr		= "/user/add name=\"".$username."\" password=\"".$password."\" group=\"full\";";
	// 		$data		= $ctrlObj->getCmd($cmdStr)->get();
	
			
			

	// 		$rData[]	= $identity;
	// 		$dacGuid	= "8E5ECB0A-B39E-68B1-450E-EEE820667841";
			
	// 		$clGuids	= array("174711A0-BC6F-BB18-F62D-3F390D036F70", "4AA2B430-1250-D8D5-B88A-6810A5A4A2F6");
	// 		foreach ($clGuids as $clGuid) {
				
	// 		}
			
			
	
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
			
		} catch (\Exception $e) {
			$rData[]	= "Exception";
			$rData[]	= $e->getMessage();
			$rData[]	= $e->getCode();
			$rData[]	= $e->getTraceAsString();
		}
		echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
		//var_dump($_SERVER);
		echo "\n 2222 \n";
		//print_r($_GET);
		echo "\n 3333 \n";
		print_r($rData);
		echo "\n ".time()."</pre></code> \n ";
		die("end");
	}
}