<?php

require_once("common.php");
doAccountsAdmin(null);

function main()
{
	$userID = $_GET["id"];
	$username = $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID, "customerID"=>null), "username", false);
	
	if($username === false) {
		accountNotFound($userID);
	}
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Admin Accounts - $usernameHtml</h1>\n";
	
	if(!isset($_POST["confirm"])) {
		$content .= removeAdminAccountForm($userID, null);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdDel("adminUser", array("userID"=>$userID, "customerID"=>null));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/");
}

main();

?>