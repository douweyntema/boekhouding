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
	
	$check(accountsValidAccountName($username), "Invalid account name.");
	$check(!accountsReservedAccountName($username), "An account with the chosen name already exists.");
	$check($GLOBALS["database"]->stdGetTry("adminUser", array("username"=>$username), "customerID", false) === false, "An account with the chosen name already exists.");
	
	$password = checkPassword($check, "password");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->startTransaction();
	$accountID = $GLOBALS["database"]->stdNew("adminUser", array("customerID"=>customerID(), "username"=>$username, "password"=>hashPassword($password)));
	if($rights === true) {
		$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$accountID, "customerRightID"=>null));
	} else {
		foreach($rights as $right=>$value) {
			if($value) {
				$customerRightID = $GLOBALS["database"]->stdGet("adminCustomerRight", array("customerID"=>customerID(), "right"=>$right), "customerRightID");
				$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$accountID, "customerRightID"=>$customerRightID));
			}
		}
	}
	$GLOBALS["database"]->commitTransaction();
	
	mysqlCreateUser($username, $password, ($rights === true || (isset($rights["mysql"]) && $rights["mysql"])));
	
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/account.php?id=$accountID");
}

main();

?>