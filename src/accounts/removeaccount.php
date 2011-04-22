<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccountsUser($userID);
	$username = $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID, "customerID"=>customerID()), "username", false);
	
	if($username === false) {
		accountNotFound($userID);
	}
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Accounts - $usernameHtml</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Accounts", "url"=>"{$GLOBALS["root"]}accounts/"),
		array("name"=>$username, "url"=>"{$GLOBALS["root"]}accounts/account.php?id=" . $userID),
		array("name"=>"Remove account", "url"=>"{$GLOBALS["root"]}accounts/removeaccount.php?id=" . $userID)
		));
	
	if(post("confirm") === null) {
		$content .= removeAccountForm($userID, null);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdDel("adminUserRight", array("userID"=>$userID));
	$GLOBALS["database"]->stdDel("adminUser", array("userID"=>$userID, "customerID"=>customerID()));
	
	// Distribute the accounts database
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/");
}

main();

?>