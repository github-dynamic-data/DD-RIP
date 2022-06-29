<?php
//© 2022 Dynamic Data
namespace DD\RIP;

class Facts
{
	//USE: $factObj		= \DD\RIP\Facts::__METHOD__();
	
	protected static $_s=array();
	
	public static function getAccounts()
	{
		if (array_key_exists(__FUNCTION__, self::$_s) === false) {
			self::$_s[__FUNCTION__]	=	new \DD\RIP\Facts\Accounts();
		}
		return self::$_s[__FUNCTION__];
	}
}