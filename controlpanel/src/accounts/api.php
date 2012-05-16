<?php

$accountsTitle = "Accounts";
$accountsDescription = "Account management";
$accountsTarget = "both";

defineRight("accounts", "shell", "Shell access", "FTP and shell access");

function accountsIsMainAccount($userID)
{
	$username = $GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username");
	return $GLOBALS["database"]->stdExists("adminCustomer", array("name"=>$username));
}

?>