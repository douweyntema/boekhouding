<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccount($userID);
	
	$username = htmlentities(stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$check = function($condition, $error) use($userID, $username) {
		if(!$condition) die(page(makeHeader("Accounts - $username", accountBreadcrumbs($userID), crumbs("Edit password", "editpassword.php?id=$userID")) . changeAccountPasswordForm($userID, $error, $_POST)));
	};
	
	$password = checkPassword($check, "password");
	$check(post("confirm") !== null, null);
	
	stdSet("adminUser", array("userID"=>$userID, "customerID"=>customerID()), array("password"=>hashPassword($password)));
	
	$mysqlRightID = stdGetTry("adminCustomerRight", array("customerID"=>customerID(), "right"=>"mysql"), "customerRightID", false);
	
	if(!$GLOBALS["mysql_management_disabled"]) {
		mysqlSetPassword($username, $password);
	}
	
	updateAccounts(customerID());
	
	redirect("accounts/account.php?id=$userID");
}

main();

?>