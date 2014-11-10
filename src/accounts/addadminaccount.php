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
	$check(!stdExists("adminUser", array("username"=>$username)), "An account with the chosen name already exists.");
	
	$password = checkPassword($check, "password");
	$check(post("confirm") !== null, null);
	
	$userID = stdNew("adminUser", array("username"=>$username, "password"=>hashPassword($password)));
	
	redirect("accounts/adminaccount.php?id=$userID");
}

main();

?>