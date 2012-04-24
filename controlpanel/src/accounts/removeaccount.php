<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccount($userID);
	$username = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$check = function($condition, $error) use($userID, $username) {
		if(!$condition) die(page(makeHeader("Accounts - $username", accountBreadcrumbs($userID), crumbs("Remove account", "removeaccount.php?id=$userID")) . removeAccountForm($userID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	mysqlRemoveAccount($username);
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdDel("adminUserRight", array("userID"=>$userID));
	$GLOBALS["database"]->stdDel("adminUser", array("userID"=>$userID, "customerID"=>customerID()));
	$GLOBALS["database"]->commitTransaction();
	
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/");
}

main();

?>