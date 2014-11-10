<?php

$accountsTitle = _("Accounts");
$accountsDescription = _("Account management");
$accountsTarget = "both";

defineRight("accounts", "shell", "Shell access", "FTP and shell access");

function updateAccounts($customerID)
{
	// Update the fileSystem version
	$fileSystemID = stdGet("adminCustomer", array("customerID"=>$customerID), "fileSystemID");
	stdIncrement("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "fileSystemVersion", 1000000000);
	
	// Update all servers
	$hosts = stdList("infrastructureMount", array("fileSystemID"=>$fileSystemID), "hostID");
	updateHosts($hosts, "update-treva-passwd");
}

function accountsIsMainAccount($userID)
{
	$username = stdGet("adminUser", array("userID"=>$userID), "username");
	return stdExists("adminCustomer", array("name"=>$username));
}

function accountsAddAccount($customerID, $username, $password, $rights)
{
	startTransaction();
	$userID = stdNew("adminUser", array("customerID"=>$customerID, "username"=>$username, "password"=>hashPassword($password)));
	if($rights === true) {
		stdNew("adminUserRight", array("userID"=>$userID, "customerRightID"=>null));
	} else {
		foreach($rights as $right=>$value) {
			if($value) {
				$customerRightID = stdGet("adminCustomerRight", array("customerID"=>$customerID, "right"=>$right), "customerRightID");
				stdNew("adminUserRight", array("userID"=>$userID, "customerRightID"=>$customerRightID));
			}
		}
	}
	commitTransaction();
	
	if(!$GLOBALS["mysql_management_disabled"]) {
		if($rights === true) {
			$mysqlEnabled = true;
		} else {
			$mysqlEnabled = isset($rights["mysql"]) && $rights["mysql"];
		}
		$mysqlEnabled = $mysqlEnabled && canAccessCustomerComponent("mysql", $customerID);
		mysqlCreateUser($username, $password, $mysqlEnabled);
	}
	
	updateAccounts($customerID);
	
	return $userID;
}

?>