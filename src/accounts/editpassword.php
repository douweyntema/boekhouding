<?php

require_once("common.php");
doAccounts($_GET["id"]);

function main()
{
	$userID = $_GET["id"];
	$username = $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID, "customerID"=>customerID()), "username", false);
	
	if($username === false) {
		accountNotFound($userID);
	}
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Accounts - $usernameHtml</h1>\n";
	
	if(!isset($_POST["confirm"])) {
		if($_POST["accountPassword1"] != $_POST["accountPassword2"]) {
			$content .= changeAccountPasswordForm($userID, "The entered passwords do not match.", null);
			die(page($content));
		}
		
		if($_POST["accountPassword1"] == "") {
			$content .= changeAccountPasswordForm($userID, "Passwords must be at least one character long.", null);
			die(page($content));
		}
		
		$content .= changeAccountPasswordForm($userID, null, $_POST["accountPassword1"]);
		die(page($content));
	}
	
	$password = decryptPassword($_POST["accountEncryptedPassword"]);
	if($password === null) {
		$content .= changeAccountPasswordForm($userID, "Internal error: invalid encrypted password. Please enter password again.", null);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdSet("adminUser", array("userID"=>$userID, "customerID"=>customerID()), array("password"=>hashPassword($password)));
	
	// Distribute the accounts database
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/account.php?id=$userID");
}

main();

?>