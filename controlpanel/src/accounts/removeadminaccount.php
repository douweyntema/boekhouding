<?php

require_once("common.php");

function main()
{
	doAccountsAdmin();
	$userID = get("id");
	$username = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$check = function($condition, $error) use($userID, $username) {
		if(!$condition) die(page(makeHeader("Accounts - $username", accountBreadcrumbs($userID), crumbs("Remove admin account", "removeadminaccount.php?id=$userID")) . removeAdminAccountForm($userID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdDel("adminUser", array("userID"=>$userID, "customerID"=>null));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/");
}

main();

?>