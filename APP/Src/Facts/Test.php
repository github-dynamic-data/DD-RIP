<?php
//© 2022 Dynamic Data
namespace DD\RIP\Facts;

class Test extends Base
{
	protected $_scanCtrl=null;
	protected $_scanInit=0;
	protected $_scanTtl=600; //max life so we dont end up with enourmous stdOut files
	protected $_jumpInit=0;
	protected $_jumpTtl=600; //max life so we dont end up with enourmous stdOut files
	protected $_jumpCtrl=null;
	protected $_initObjs=array();
	
	public function getScanCtrl()
	{
		if ($this->_scanCtrl !== null) {
			if (($this->_scanInit + $this->_scanTtl) < time()) {
				echo "Replacing Scan Controller\n";
				try {
					$this->_scanCtrl->terminate();
				} catch (\Exception $e) {
				}
				$this->_scanCtrl	= null;
			}
		}
		if ($this->_scanCtrl === null) {
			$this->_scanCtrl	= \MTM\Shells\Factories::getShells()->getBash();
			$this->_scanInit	= time();
		}
		return $this->_scanCtrl;
	}
	public function getJumpCtrl()
	{
		if ($this->_jumpCtrl !== null) {
			if (($this->_jumpInit + $this->_jumpTtl) < time()) {
				echo "Replacing Jump Controller\n";
				try {
					$this->_jumpCtrl->terminate();
				} catch (\Exception $e) {
				}
				$this->_jumpCtrl	= null;
			}
		}
		if ($this->_jumpCtrl === null) {
			$jumpHost			= "10.169.65.1";
			$jumpUser			= "ripService";
			$jumpUser			= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($jumpUser);
			$jumpPass			= "Merlin88";
			$jumpPort			= 1122;
			$this->_jumpCtrl	= \MTM\SSH\Factories::getShells()->passwordAuthentication($jumpHost, $jumpUser, $jumpPass, null, $jumpPort);
			$this->_jumpInit	= time();
		}
		return $this->_jumpCtrl;
	}
	public function getInitObjs()
	{
		$rData		= array();
		$rMacs		= array();

		try {
			
			$cmdStr		= "mactelnet -l -B -t 5;";
			$data		= trim($this->getScanCtrl()->getCmd($cmdStr)->get());
			$lines		= \MTM\Spreadsheet\Factories::getCSV()->getTool()->getAsArray($data);
			array_shift($lines);
			array_shift($lines);
			foreach ($lines as $csv) {
				$rawMac		= trim(strtoupper($csv[0]), "'");
				$mac		= "";
				foreach (explode(":", $rawMac) as $pm) {
					if (strlen($pm) < 2) {
						$pm		= str_repeat("0", (2 - strlen($pm))).$pm;
					}
					$mac	.= $pm;
				}
				$identity		= trim($csv[1], "'");
				if ($identity === "rps-initializated") {
					if (array_key_exists($mac, $this->_initObjs) === false) {
						$initObj				= new \stdClass();
						$initObj->mac			= $mac;
						$initObj->identity		= $identity;
						$initObj->state			= "pending";
						$initObj->dacGuid		= null;
						$this->_initObjs[$mac]	= $initObj;
					}
				} elseif (array_key_exists($mac, $this->_initObjs) === true) {
					$this->_initObjs[$mac]->state		= "completed";
					$this->_initObjs[$mac]->identity	= $identity;
				}
			}
			
			return $this->_initObjs;
	
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
		if (getenv("dd-app.stage") === "prod") {
			$dacHost		= "dac-eu-1.dynamic-data.io";
			$dacPort		= 44280;
		} else {
			$dacHost		= "dac-test-grp.dynamic-data.io";
			$dacPort		= 47480;
		}
		
		$initUser		= "admin";
		$initPass		= "";
		$initUser		= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($initUser);
		$acctObj		= \DD\DACApi\Facts::getAccounts()->getBySession();
		$hashTool		= \MTM\Utilities\Factories::getStrings()->getHashing();
		$devTypeObj		= \DD\DACApi\Facts::getDeviceTypes()->getByLabel("routeros");
		
		$rData			= array();
		$this->getInitObjs();
		foreach ($this->_initObjs as $iId => $initObj) {
			
			if ($initObj->state === "pending") {
				
				echo "Pending: ".$initObj->mac."\n";
				
				try {
					$ctrlObj		= \MTM\MacTelnet\Factories::getShells()->passwordAuthentication($initObj->mac, $initUser, $initPass, $this->getJumpCtrl());
					try {
						
						$cmdStr			= ":put [/system/routerboard/get model];";
						$modelNbr		= trim($ctrlObj->getCmd($cmdStr)->get());
						
						$cmdStr			= ":put [/system/license/get software-id];";
						$license		= trim($ctrlObj->getCmd($cmdStr)->get());
						
						$cmdStr			= ":put [/system/routerboard/get serial-number];";
						$serial			= trim($ctrlObj->getCmd($cmdStr)->get());
						
						$label			= hash("sha256", strtolower($serial.$license));
						$devObj			= $acctObj->getDeviceByLabel($label, false);
						
						if ($devObj === null) {
							$devNbr			= $hashTool->getAsInteger(hash("sha256", $label."qefqwegh23hh5hFytdf"), 99999);
							if ($devNbr < 6000) {
								$devNbr		= $hashTool->getAsIntegerV2(hash("sha256", $label."qefqwegh23hh5hFytdf"), 99999);
								if ($devNbr < 6000) {
									throw new \Exception("Device Nbr too low: ".$devNbr);
								}
							}
							if ($modelNbr === "RBD52G-5HacD2HnD") {
								$identity	= "ap-".$devNbr.".lionstripe.com";
							} elseif (
								$modelNbr === "CRS326-24S+2Q+"
								|| $modelNbr === "CRS328-24P-4S+"
							) {
								$identity	= "as-".$devNbr.".lionstripe.com";
							} elseif (
								$modelNbr === "CCR1036-8G-2S+"
								|| $modelNbr === "CCR2004-16G-2S+"
							) {
								$identity	= "br-".$devNbr.".lionstripe.com";
							} else {
								throw new \Exception("Not handled for model: '".$modelNbr."'");
							}

							$devObj		= $acctObj->addDevice($devTypeObj, $label);
							$devObj->setIdentity($identity);
							$devObj->setDisplayName($identity);
						}
						$initObj->dacGuid		= $devObj->getGuid();
						
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
						
						$cmdStr		= "/ip/dhcp-client/print";
						$data		= trim($ctrlObj->getCmd($cmdStr)->get());
						if ($data == "") {
							$cmdStr		= "/ip/dhcp-client/add interface=ether1 disabled=no add-default-route=yes default-route-distance=250";
							$ctrlObj->getCmd($cmdStr)->get();
							sleep(2);
						}
						
						$cmdStr		= "/tool fetch url=\"https://".$dacHost."/api/v1/Provisioning/Get/RouterOSv7/RpsInitial/".$devObj->getGuid()."/\"";
						$cmdStr		.= " port=".$dacPort." mode=https user=\"".getenv("dd-dac.api.user")."\"";
						$cmdStr		.= " password=\"".getenv("dd-dac.api.pass")."\"";
						$cmdStr		.= " http-method=get output=file as-value dst-path=flash/RPS/primary.rsc";
						$ctrlObj->getCmd($cmdStr)->get();
						
						//setup martin
						$username	= "martin_adm";
						$password	= "TtPr0Me1E@";
						
						$cmdStr		= "/user/remove [find where name=\"".$username."\"];";
						$ctrlObj->getCmd($cmdStr)->get();
						
						$cmdStr		= "/user/add name=\"".$username."\" password=\"".$password."\" group=\"full\";";
						$ctrlObj->getCmd($cmdStr)->get();
						
						//disable admin
						$username	= "admin";
						$password	= "Merlin88##";
						$cmdStr		= "/user/set [find where name=\"".$username."\"] password=\"".$password."\" disabled=\"yes\";";
						$ctrlObj->getCmd($cmdStr)->get();
						
						
						$serviceCmds		= array();
						$serviceCmds[]		= "/ip service set telnet address=127.0.0.1/32 disabled=yes;";
						$serviceCmds[]		= "/ip service set ftp address=127.0.0.1/32 port=1121 disabled=no;";
						$serviceCmds[]		= "/ip service set www address=127.0.0.1/32 disabled=yes;";
						$serviceCmds[]		= "/ip service set ssh port=1122 disabled=no;";
						$serviceCmds[]		= "/ip service set www-ssl address=127.0.0.1/32 disabled=yes;";
						$serviceCmds[]		= "/ip service set api address=127.0.0.1/32 disabled=yes;";
						$serviceCmds[]		= "/ip service set winbox port=14557 disabled=no;";
						$serviceCmds[]		= "/ip service set api-ssl address=127.0.0.1/32 disabled=yes;";
						
						foreach ($serviceCmds as $cmd) {
							$ctrlObj->getCmd($cmd)->get();
						}
						
						
						$initObj->state		= "initializing";	
						echo "Initializing: ".$initObj->mac."\n";
						
						try {
							$ctrlObj->terminate();
						} catch (\Exception $e) {
						}
						
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
					if (isset($jumpCtrl) === true && $jumpCtrl !== null) {
						try {
							$jumpCtrl->terminate();
						} catch (\Exception $e) {
						}
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
			} elseif ($initObj->state === "completed") {
				echo "Completed: ".$initObj->mac." ".$initObj->identity." ".$initObj->dacGuid."\n";
				unset($this->_initObjs[$iId]);
			}
		} 
	}
	public function execute()
	{
		$rData		= array();
		try {
			
			
			///system/reset-configuration skip-backup=yes
			while (true) {
				$this->loopOnce();
			}

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
	public function newDmeDevice()
	{
// 		$dbUsr	= "dmeUsr";
// 		$dbPass	= "TWY7sB6-Gu-DrBvf7VAT2Wh_hx45Zae-Z9WFrHx3";
		
// 		CREATE USER 'dmeUsr' IDENTIFIED BY 'TWY7sB6-Gu-DrBvf7VAT2Wh_hx45Zae-Z9WFrHx3'
// 		GRANT  ALL ON `DC-NET-DME`.* TO 'dmeUsr'
	}
}