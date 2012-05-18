<?php

$accountsTitle = "Accounts";
$accountsDescription = "Account management";
$accountsTarget = "both";

defineRight("accounts", "shell", "Shell access", "FTP and shell access");

function updateAccounts($customerID)
{
	// Update the fileSystem version
	$fileSystemID = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "fileSystemID");
	$GLOBALS["database"]->stdIncrement("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "fileSystemVersion", 1000000000);
	
	// Update all servers
	$hosts = $GLOBALS["database"]->stdList("infrastructureMount", array("fileSystemID"=>$fileSystemID), "hostID");
	updateHosts($hosts, "update-treva-passwd");
}

function accountsIsMainAccount($userID)
{
	$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username");
	return $GLOBALS["database"]->stdExists("adminCustomer", array("name"=>$username));
}

?>