<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccountsAdmin();
	
	$username = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$check = function($condition, $error) use($userID, $username) {
		if(!$condition) die(page(makeHeader("Accounts - $username", accountBreadcrumbs($userID), crumbs("Edit password", "editadminpassword.php?id=$userID")) . changeAdminAccountPasswordForm($userID, $error, $_POST)));
	};
	
	$password = checkPassword($check, "password");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("adminUser", array("userID"=>$userID), array("password"=>hashPassword($password)));
	
	redirect("accounts/adminaccount.php?id=$userID");
}

main();

?>