<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccount($userID);
	
	$username = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$check = function($condition, $error) use($userID, $username) {
		if(!$condition) die(page(makeHeader("Accounts - $username", accountBreadcrumbs($userID), crumbs("Edit password", "editpassword.php?id=$userID")) . changeAccountPasswordForm($userID, $error, $_POST)));
	};
	
	$password = checkPassword($check, "password");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("adminUser", array("userID"=>$userID, "customerID"=>customerID()), array("password"=>hashPassword($password)));
	
	$mysqlRightID = $GLOBALS["database"]->stdGetTry("adminCustomerRight", array("customerID"=>customerID(), "right"=>"mysql"), "customerRightID", false);
	
	mysqlSetPassword($username, $password);
	
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/account.php?id=$userID");
}

main();

?>