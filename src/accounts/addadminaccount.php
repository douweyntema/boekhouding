<?php

require_once("common.php");

function main()
{
	doAdminAccounts();
	
	$content = "<h1>Admin Accounts</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Admin Accounts", "url"=>"{$GLOBALS["root"]}accounts/"),
		array("name"=>"Add admin account", "url"=>"{$GLOBALS["root"]}accounts/addadminaccount.php")
		));
	
	if(post("accountUsername") === null) {
		$content .= addAdminAccountForm("", "", null);
		die(page($content));
	}
	
	$username = post("accountUsername");
	
	$exists = $GLOBALS["database"]->stdGetTry("adminUser", array("username"=>$username), "customerID", false) !== false;
	if($exists) {
		$content .= addAdminAccountForm("An account with the chosen name already exists.", $username, null);
		die(page($content));
	}
	
	if(!validAccountName($username)) {
		$content .= addAdminAccountForm("Invalid account name.", $username, null);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		if(post("accountPassword1") != post("accountPassword2")) {
			$content .= addAdminAccountForm("The entered passwords do not match.", $username, null);
			die(page($content));
		}
		
		if(post("accountPassword1") == "") {
			$content .= addAdminAccountForm("Passwords must be at least one character long.", $username, null);
			die(page($content));
		}
		
		$content .= addAdminAccountForm(null, $username, post("accountPassword1"));
		die(page($content));
	}
	
	$password = decryptPassword(post("accountEncryptedPassword"));
	if($password === null) {
		$content .= addAdminAccountForm("Internal error: invalid encrypted password. Please enter password again.", $username, null);
		die(page($content));
	}
	
	$accountID = $GLOBALS["database"]->stdNew("adminUser", array("customerID"=>null, "username"=>$username, "password"=>hashPassword($password)));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/adminaccount.php?id=$accountID");
}

main();

?>