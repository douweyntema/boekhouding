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

function accountsAddAccount($customerID, $username, $password, $rights)
{
	$GLOBALS["database"]->startTransaction();
	$userID = $GLOBALS["database"]->stdNew("adminUser", array("customerID"=>$customerID, "username"=>$username, "password"=>hashPassword($password)));
	if($rights === true) {
		$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$userID, "customerRightID"=>null));
	} else {
		foreach($rights as $right=>$value) {
			if($value) {
				$customerRightID = $GLOBALS["database"]->stdGet("adminCustomerRight", array("customerID"=>$customerID, "right"=>$right), "customerRightID");
				$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$userID, "customerRightID"=>$customerRightID));
			}
		}
	}
	$GLOBALS["database"]->commitTransaction();
	
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