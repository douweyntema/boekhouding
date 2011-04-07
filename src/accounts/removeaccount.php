<?php

require_once("common.php");
doAccounts($_GET["id"]);

function main()
{
	$userID = $_GET["id"];
	$username = $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID, "customerID"=>customerID()), "username", false);
	
	if($username === false) {
		accountNotFound($userID);
	}
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Accounts - $usernameHtml</h1>\n";
	
	if(!isset($_POST["confirm"])) {
		$content .= removeAccountForm($userID, null);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdDel("adminUser", array("userID"=>$userID, "customerID"=>customerID()));
	
	// Distribute the accounts database
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/");
}

main();

?>