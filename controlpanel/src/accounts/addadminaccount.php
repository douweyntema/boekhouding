<?php

require_once("common.php");

function main()
{
	doAccountsAdmin();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Add admin account", accountsBreadcrumbs(), crumbs("Add admin account", "addadminaccount.php")) . addAdminAccountForm($error, $_POST)));
	};
	
	$check(post("username") !== null, "");
	
	$username = post("username");
	
	$check(validAccountName($username), "Invalid account name.");
	$check(!$GLOBALS["database"]->stdExists("adminUser", array("username"=>$username)), "An account with the chosen name already exists.");
	
	$password = checkPassword($check, "password");
	$check(post("confirm") !== null, null);
	
	$accountID = $GLOBALS["database"]->stdNew("adminUser", array("customerID"=>null, "username"=>$username, "password"=>hashPassword($password)));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/adminaccount.php?id=$accountID");
}

main();

?>