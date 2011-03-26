<?php

require_once("common.php");
doAccountsAdmin(null);

function main()
{
	$content = "<h1>Admin Accounts</h1>\n";
	
	if(!isset($_POST["accountUsername"])) {
		$content .= addAdminAccountForm("", "", null);
		die(page($content));
	}
	
	$username = $_POST["accountUsername"];
	
	$exists = $GLOBALS["database"]->stdGetTry("adminUser", array("username"=>$username), "customerID", false) !== false;
	if($exists) {
		$content .= addAdminAccountForm("An account with the chosen name already exists.", $username, null);
		die(page($content));
	}
	
	if(!validAccountName($username)) {
		$content .= addAdminAccountForm("Invalid account name.", $username, null);
		die(page($content));
	}
	
	if(!isset($_POST["confirm"])) {
		if($_POST["accountPassword1"] != $_POST["accountPassword2"]) {
			$content .= addAdminAccountForm("The entered passwords do not match.", $username, null);
			die(page($content));
		}
		
		if($_POST["accountPassword1"] == "") {
			$content .= addAdminAccountForm("Passwords must be at least one character long.", $username, null);
			die(page($content));
		}
		
		$content .= addAdminAccountForm(null, $username, $_POST["accountPassword1"]);
		die(page($content));
	}
	
	$password = decryptPassword($_POST["accountEncryptedPassword"]);
	if($password === null) {
		$content .= addAdminAccountForm("Internal error: invalid encrypted password. Please enter password again.", $username, null);
		die(page($content));
	}
	
	$accountID = $GLOBALS["database"]->stdNew("adminUser", array("customerID"=>null, "username"=>$username, "password"=>md5($password)));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/adminaccount.php?id=$accountID");
}

main();

?>