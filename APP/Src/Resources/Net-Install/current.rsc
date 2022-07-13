
##begin, tested on ROS v6.49.6 and v7.3.1
:log info message=("Starting net-install RPS default configuration script v1");

##init will be complete when the executed script sets the global var: rpsInit = true
##this disables the schedule and stops the script from triggering 
##default config scripts timeout after running for 240 secs, scheduled jobs do not have this limit

:local ethCount 1; ##minimum loaded ethernet interface count
:local result ""; ##general return data
:local counter 0; ##general counter data
:local isDone false; ##general did a job complete

##make sure the ethernet interfaces are loaded before we start
:set counter 0;
:set isDone false;
:log info message=("Waiting for a minimum of: '".$ethCount."' ethernet interfaces to be ready");
:while ($isDone = false) do={
	:set counter [:len [/interface ethernet find]];
	:if ($counter >= $ethCount) do={
		:set isDone true;
		:log info message=("'".$ethCount."' ethernet interfaces ready!");
	} else={
		:delay 1s;
	}
};

##we rely on ssh/mac-telnet and the annoying renew password prompts are a problem
:log info message=("Setting identity");
:set result [/system identity set name="rps-initializated"];

##we create a scheduled task to tackle the init scripts. This way if the script fails
## e.g. bc there is no internet or power is lost, the device will try again until it succeds

:local scrName "rpsInit"; ##script name
:local schName "rpsInit"; ##schedule name

##build the script line by line
:local s [:toarray ""];
:set ($s->([:len $s])) ":global rpsInitRun;";
:set ($s->([:len $s])) ":if (\$rpsInitRun != true) do={";
:set ($s->([:len $s])) ":set rpsInitRun true;";
:set ($s->([:len $s])) ":global rpsInit;";
:set ($s->([:len $s])) ":set rpsInit false;";
:set ($s->([:len $s])) ":local scrJob \"\";";
:set ($s->([:len $s])) ":local scrNull \"initNull.txt\";";
:set ($s->([:len $s])) ":local loadFiles [:toarr \"flash/RPS/primary.rsc,flash/RPS/secondary.rsc\"];";
:set ($s->([:len $s])) ":local res \"\";";
:set ($s->([:len $s])) ":local isDone false;";
:set ($s->([:len $s])) ":local counter 0;";
:set ($s->([:len $s])) ":foreach path in=\$loadFiles do={";
:set ($s->([:len $s])) ":if (\$isDone = false) do={";
:set ($s->([:len $s])) ":if ([:len [/file find where name=\$path]] > 0) do={";
:set ($s->([:len $s])) ":do {";
:set ($s->([:len $s])) ":log info message=(\"Loading Initialization file: '\".\$path.\"'\");";
:set ($s->([:len $s])) ":set counter 300;";
:set ($s->([:len $s])) ":set scrJob [:execute script=(\"/import file-name=\".\$path) file=(\$scrNull)];";
:set ($s->([:len $s])) ":while (\$counter > 0) do={"; ##start while
:set ($s->([:len $s])) ":set res [/system script job get \$scrJob type];";
:set ($s->([:len $s])) ":if ([:typeof \$res] = \"nil\") do={";
:set ($s->([:len $s])) ":set counter 0;";
:set ($s->([:len $s])) "} else={";
:set ($s->([:len $s])) ":set counter (\$counter - 1);";
:set ($s->([:len $s])) ":delay 1s;";
:set ($s->([:len $s])) "}";
:set ($s->([:len $s])) "}"; ##end while
:set ($s->([:len $s])) ":if (\$rpsInit = true) do={";
:set ($s->([:len $s])) ":set isDone true;";
:set ($s->([:len $s])) ":log info (\"RPS init file: '\".\$path.\"' success!\");";
:set ($s->([:len $s])) "} else={";
:set ($s->([:len $s])) ":log warning (\"RPS init file: '\".\$path.\"' failed!\");";
:set ($s->([:len $s])) "}";
:set ($s->([:len $s])) "} on-error={";
:set ($s->([:len $s])) ":log error message=(\"RPS init file: '\".\$path.\"' failed in error!\");";
:set ($s->([:len $s])) "}";
:set ($s->([:len $s])) "} else={";
:set ($s->([:len $s])) ":log warning message=(\"RPS init file: '\".\$path.\"' does not exist!\");";
:set ($s->([:len $s])) "}";
:set ($s->([:len $s])) "}";
:set ($s->([:len $s])) "}";
:set ($s->([:len $s])) ":if (\$rpsInit != true) do={";
:set ($s->([:len $s])) ":log error message=(\"RPS initialization failed!\");";
:set ($s->([:len $s])) "} else={";
:set ($s->([:len $s])) ":log info message=(\"RPS initialization success!\");";
:set ($s->([:len $s])) (":if ([:len [/system scheduler find where name=\"".$schName."\"]] > 0) do={");
:set ($s->([:len $s])) ("/system scheduler set [find where name=\"".$schName."\"] disabled=yes");
:set ($s->([:len $s])) "}";
:set ($s->([:len $s])) "}";
:set ($s->([:len $s])) ":set rpsInitRun false;";
:set ($s->([:len $s])) "} else={";
:set ($s->([:len $s])) ":log info message=(\"RPS initialization still in progress, skipping!\");";
:set ($s->([:len $s])) "}";

:local scr "";
:foreach line in=$s do={
	:set scr ($scr.$line."\r\n");
}
:if ([:len [/system script find where name=$scrName]] < 1) do={
	/system script add name=$scrName source=$scr dont-require-permissions=no policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon;
} else={
	/system script set [find where name=$scrName] source=$scr dont-require-permissions=no policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon;
}
:if ([:len [/system scheduler find where name=$schName]] < 1) do={
	/system scheduler add disabled=no interval=1m name=$schName on-event="/system script run rpsInit;" policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon start-date=jan/01/1970 start-time=00:00:00;
} else={
	/system scheduler set [find where name=$schName] disabled=no interval=1m on-event="/system script run rpsInit;" policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon start-date=jan/01/1970 start-time=00:00:00;
}

##dont trigger, just exit, the default config makes the device hang until done
##/system script run rpsInit;
