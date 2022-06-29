<?php
//© 2022 Dynamic Data
namespace DD\RIP\Facts;

class Test extends Base
{
	public function execute()
	{
		
		$raw	= "[admin@rps-initializated] > /system/resource/print
                   uptime: 22h57m31s
                  version: 7.3.1 (stable)
               build-time: Jun/09/2022 08:58:15
         factory-software: 6.45.9
              free-memory: 74.6MiB
             total-memory: 128.0MiB
                      cpu: ARM
                cpu-count: 4
            cpu-frequency: 448MHz
                 cpu-load: 0%
           free-hdd-space: 2048.0KiB
          total-hdd-space: 15.2MiB
  write-sect-since-reboot: 732
         write-sect-total: 1635
               bad-blocks: 0%
        architecture-name: arm
               board-name: hAP ac^2
                 platform: MikroTik
[admin@rps-initializated] >
";
		
		$cmd		= "/system/resource/print";
		
// 		$data		= $raw;
// 		$strCmd		= $cmd;
// 		if ($strCmd !== null && trim($strCmd) != "") {
			
// 			$lines		= explode("\n", $data);
// 			$found		= false;
			
// 			echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
// 			//var_dump($_SERVER);
// 			echo "\n 2222 \n";
// 			//print_r($_GET);
// 			echo "\n 3333 \n";
// 			print_r($lines);
// 			echo "\n ".time()."</pre></code> \n ";
// 			die("end");
			
// 			foreach ($lines as $lKey => $line) {
				
// 				$line	= trim($line);
// 				if ($line != "") {
					
// 					//after each 1000 chars the commands encur a terminal break
// 					$line	= str_replace("\r\n", "", $line);
// 					//is the line part of the command?
// 					$cmdPos		= strpos($strCmd, $line);
// 					//is the command only part of the line
// 					//e.g. there is more data than just the command
// 					$linePos    = strpos($line, $strCmd);
					
// 					if ($cmdPos !== false || $linePos !== false) {
// 						//this line holds part or all of the command
// 						$found		= true;
// 						if ($cmdPos !== false) {
// 							$strCmd		= substr($strCmd, ($cmdPos + strlen($line)));
// 							if (strlen(trim($strCmd)) < 1) {
// 								//we found all of the command, nothing but whitespace left
// 								$lines		= array_slice($lines, ($lKey + 1));
// 								break;
// 							}
							
// 						} elseif ($linePos !== false) {
							
// 							//this line holds what remains of the command, the rest is data
// 							//the rest of the line is data, if it was only whitespace, it would have been caught above
// 							$lines[$lKey]	= substr($line, ($linePos + strlen($strCmd)));
// 							$lines			= array_slice($lines, $lKey);
// 							break;
// 						}
						
// 					} elseif ($found === true) {
// 						//we had part of the command but lost it before a match could be made
// 						break;
// 					}
// 				}
// 			}
// 			$data	= implode("\n", $lines);
// 		}
// 		echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
// 		var_dump(chr(47));
// 		echo "\n 2222 \n";
// 		//print_r($_GET);
// 		echo "\n 3333 \n";
// // 		print_r($_POST);
// 		echo "\n ".time()."</pre></code> \n ";
// 		die("end");
		
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
		$secPassword	= "merlin";
		$secUsername	= \MTM\SSH\Factories::getShells()->getRouterOsTool()->getFormattedUsername($secUsername);
		$ctrlObj		= \MTM\MacTelnet\Factories::getShells()->passwordAuthentication($secMacAddr, $secUsername, $secPassword, $ctrlObj);

		$cmdStr		= "/ip/dhcp-client/print";
		$data		= trim($ctrlObj->getCmd($cmdStr)->get());
		
		if ($data == "") {
			$cmdStr		= "/ip/dhcp-client/add interface=ether1 disabled=no add-default-route=yes default-route-distance=250";
			$ctrlObj->getCmd($cmdStr)->get();
		
			$cmdStr		= "/ip/dhcp-client/print";
			$data		= trim($ctrlObj->getCmd($cmdStr)->get());
		}
		
// 		$rawData	= $cmdObj->getData();
// 		$rData[]	= $pData;
		
// 		$cmdStr		= "quit";
// 		$regEx		= "Welcome back!";
// 		$rData[]	= $ctrlObj->getCmd($cmdStr, $regEx)->get();
	
		
		echo "\n <code><pre> \nClass:  ".__CLASS__." \nMethod:  ".__FUNCTION__. "  \n";
		var_dump($data);
		echo "\n 2222 \n";
// 		print_r($rawData);
		echo "\n 3333 \n";
		print_r($data);
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