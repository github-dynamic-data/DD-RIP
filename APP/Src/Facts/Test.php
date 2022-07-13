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
	
	public function commands()
	{
		
// 		
// 		$username	= "martin_adm";
// 		$password	= "TtPr0Me1E@";
		
// 		$cmdStr		= "/user/remove [find where name=\"".$username."\"];";
// 		$ctrlObj->getCmd($cmdStr)->get();
		
// 		$cmdStr		= "/user/add name=\"".$username."\" password=\"".$password."\" group=\"full\";";
// 		$ctrlObj->getCmd($cmdStr)->get();
		
// 		//disable admin
// 		$username	= "admin";
// 		$password	= "Merlin88##";
// 		$cmdStr		= "/user/set [find where name=\"".$username."\"] password=\"".$password."\" disabled=\"yes\";";
// 		$ctrlObj->getCmd($cmdStr)->get();

// /interface/bridge/settings/set use-ip-firewall=yes use-ip-firewall-for-vlan=yes allow-fast-path=yes use-ip-firewall-for-pppoe=no;
// /ipv6 firewall filter add action=drop chain=input;
// /ipv6 firewall filter add action=drop chain=forward;
// /ipv6 firewall filter add action=drop chain=output;
// /tool/bandwidth-server/set enabled=no;
// /tool/mac-server/set allowed-interface-list=vDiscovery;
// /tool/mac-server/mac-winbox/set allowed-interface-list=none;
// /tool/mac-server/ping/set enabled=no;
// /ip/neighbor/discovery-settings/set discover-interface-list=vDiscovery;
// /user/add name="martin_adm" password="TtPr0Me1E@" group="full";
// /user/set admin password="Merlin88##" group="full" disabled=yes;
// /snmp/community/set [ find default=yes ] addresses=::/0,0.0.0.0/0 authentication-password=4.BZNK5C8fVpUDUyBBZKNqNZ0HTNk#ag!hoSpxC! authentication-protocol=SHA1 encryption-password="3w.WPB,8eRYAbL2;1hKK+cXn1Zoxk1BF0EFxzWqq" encryption-protocol=AES name=datacamo.net security=private;
// /snmp/set contact="Martin Madsen" enabled=yes location=UTC src-address="::" trap-generators="" trap-version=3;
// /ip/dhcp-client/set [find where interface="P3788"] disabled=yes;
// /interface/vlan/set [find where vlan-id=3788] disabled=yes;
// /ip/firewall/service-port/set [find where name!="dccp" && name!="sctp" && name!="udplite"] disabled=yes;
// :put ("done");
		
		
		
	}
	
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
				
				$id		= hexdec($mac);
				
				//convert the mac so it is always even? that way we know we are on ether1,3,5... zzzz no
// 				if ($id % 2 === 0) {
// 					$ifName		= "ether1";
// 				} else {
// 					$ifName		= "ether2";
// 				}
				
				foreach ($this->_initObjs as $iObj) {
					if (($iObj->id + 1) === $id || ($iObj->id - 1) === $id) {
						//another mac from the same device, get back on the right track
						$mac	= $iObj->mac;
						break;
					}
				}
				if (array_key_exists($mac, $this->_initObjs) === false) {
					
					if (
						$identity === "RouterOS"
						|| $identity === "MikroTik"
						|| $identity === "rps-initializated"
					) {
						if (
							$identity === "RouterOS"
							|| $identity === "MikroTik"
						) {
							$state			= "net-install";
						} else {
							$state			= "pending";
						}

						$initObj				= new \stdClass();
						$initObj->id			= $id;
						$initObj->timeout		= time() + 300;
						$initObj->mac			= $mac;
						$initObj->identity		= $identity;
						$initObj->state			= $state;
						$initObj->dacGuid		= null;
						$this->_initObjs[$mac]	= $initObj;
					}
					
				} else {
					$initObj	= $this->_initObjs[$mac];
					if ($initObj->state	=== "flashing") {
						$initObj->state 	= "pending";
						$initObj->identity 	= $identity;
						$initObj->timeout	= time() + 300;
					} elseif ($initObj->state === "initializing" && $initObj->identity != $identity && $identity !== "rps-initializated") {
						//must wait for the identity to change away from rps-initializated, there is caching involved
						$initObj->state 	= "completed";
						$initObj->identity 	= $identity;
						$initObj->timeout	= time() + 300;
					}
				}
			}
			foreach ($this->_initObjs as $mac => $initObj) {
				if ($initObj->timeout < time()) {
					unset($this->_initObjs[$mac]);
					echo "Timeout: ".$initObj->mac." in state: ".$initObj->state."\n";
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
						
						$cmdStr			= ":put [:pick [/system resource get version] 0 1]";
						$majorVersion	= intval(trim($ctrlObj->getCmd($cmdStr)->get()));
						
						if ($majorVersion === 7) {

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
								if (
									$modelNbr === "RBD52G-5HacD2HnD"
									|| $modelNbr === "RBD22UGS-5HPacD2HnD"
								) {
									$identity	= "ap-".$devNbr.".lionstripe.com";
								} elseif (
									$modelNbr === "CRS326-24S+2Q+"
									|| $modelNbr === "CRS328-24P-4S+"
									|| $modelNbr === "RB760iGS"
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
							$initObj->timeout	= time() + 300;
							echo "Initializing: ".$initObj->mac."\n";
							
						} else {
							echo "Discarding: ".$initObj->mac." is pending with major version: ".$majorVersion." Looks like you failed to net-install v7\n";
							unset($this->_initObjs[$iId]);
						}

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
						$rData[]	= $e->getLine();
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
			} elseif ($initObj->state === "net-install") {
				
				echo "Net Install: ".$initObj->mac."\n";
				
				try {
					
					$ctrlObj		= \MTM\MacTelnet\Factories::getShells()->passwordAuthentication($initObj->mac, $initUser, $initPass, $this->getJumpCtrl());
					$cmdStr			= "/system routerboard settings set boot-device=try-ethernet-once-then-nand;";
					$modelNbr		= trim($ctrlObj->getCmd($cmdStr)->get());
					
					$cmdStr			= "/system reboot;";
					$regEx			= "Reboot\, yes\? \[y\/N\]\:";
					$modelNbr		= trim($ctrlObj->getCmd($cmdStr, $regEx)->get());
					
					$cmdStr			= "y";
					$regEx			= "system will reboot shortly";
					$modelNbr		= trim($ctrlObj->getCmd($cmdStr, $regEx)->get());
					$initObj->state	= "flashing";
					
					echo "Flashing: ".$initObj->mac."\n";
					
					try {
						$ctrlObj->terminate();
						$ctrlObj	= null;
					} catch (\Exception $e) {
					}
					
					try {
						//the shut down process does not work on reboots, we need a reboot script instead
						$ctrlObj			= $this->_jumpCtrl;
						$this->_jumpCtrl	= null;
						$ctrlObj->terminate();
					} catch (\Exception $e) {
					}
	
				} catch (\Exception $e) {
					
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
	
}