<?php

$accountsTitle = "Accounts";
$accountsDescription = "Account management";
$accountsTarget = "both";

defineRight("accounts", "shell", "Shell access", "FTP and shell access");

define("RESERVED_USERNAMES_FILE", dirname(__FILE__) . "/../../reserved-usernames");

function accountsValidAccountName($username)
{
	if(strlen($username) < 3 || strlen($username) > 30) {
		return false;
	}
	if(preg_match('/^[a-zA-Z_][-a-zA-Z0-9_]*$/', $username) != 1) {
		return false;
	}
	return true;
}

function accountsReservedAccountName($username)
{
	foreach(explode("\n", file_get_contents(RESERVED_USERNAMES_FILE)) as $reserved) {
		$reserved = trim($reserved);
		if($reserved == "" || $reserved[0] == "#") {
			continue;
		}
		if($username == $reserved) {
			return true;
		}
	}
	return false;
}

function accountsIsMainAccount($userID)
{
	$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username");
	return $GLOBALS["database"]->stdExists("adminCustomer", array("name"=>$username));
}

?>