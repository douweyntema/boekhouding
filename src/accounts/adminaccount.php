<?php

require_once("common.php");
doAccountsAdmin($_GET["id"]);

function main()
{
	$userID = $_GET["id"];
	$username = $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID, "customerID"=>null), "username", false);
	
	if($username === false) {
		accountNotFound($userID);
	}
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Admin Accounts - $usernameHtml</h1>\n";
	
	$content .= changeAdminAccountPasswordForm($userID, "", null);
	$content .= removeAdminAccountForm($userID, "");
	
	echo page($content);
}

main();

?>