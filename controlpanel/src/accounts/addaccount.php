<?php

require_once("common.php");

function main()
{
	doAccounts();
	
	$check = function($condition, $error) {
		if(!$condition) die(page(makeHeader("Add account", accountsBreadcrumbs(), crumbs("Add account", "addaccount.php")) . addAccountForm($error, $_POST)));
	};
	
	$username = post("username");
	
	$check($username !== null, "");
	
	if(post("rights") == "full") {
		$rights = true;
	} else if(post("rights") == "limited") {
		$rights = array();
		foreach(rights() as $right) {
			if(post("right-" . $right["name"]) !== null) {
				$rights[$right["name"]] = true;
			} else {
				$rights[$right["name"]] = false;
			}
		}
	} else {
		$check(false, "");
	}
	
	$check(validAccountName($username), "Invalid account name.");
	$check(!reservedAccountName($username), "An account with the chosen name already exists.");
	$check(stdGetTry("adminUser", array("username"=>$username), "customerID", false) === false, "An account with the chosen name already exists.");
	$check(strlen($username) < 16, "The username should be less then 16 characters.");
	
	$password = checkPassword($check, "password");
	$check(post("confirm") !== null, null);
	
	$userID = accountsAddAccount(customerID(), $username, $password, $rights);
	
	redirect("accounts/account.php?id=$userID");
}

main();

?>