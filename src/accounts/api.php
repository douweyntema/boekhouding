<?php

$accountsTitle = _("Accounts");
$accountsDescription = _("Account management");
$accountsTarget = "both";

defineRight("accounts", "shell", "Shell access", "FTP and shell access");

function accountsAddAccount($username, $password)
{
	$userID = stdNew("adminUser", array("username"=>$username, "password"=>hashPassword($password)));
	return $userID;
}

?>