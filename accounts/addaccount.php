<?php

require_once("common.php");
doAccounts(null);

function main()
{
	$content = "<h1>Accounts</h1>\n";
	
	if(!isset($_POST["accountUsername"])) {
		$content .= addAccountForm("", null, null, null);
		die(page($content));
	}
	
	$username = $_POST["accountUsername"];
	
	if($_POST["rights"] == "full") {
		$rights = true;
	} else {
		$components = customerComponents();
		$rights = array();
		foreach($components as $component) {
			if(isset($_POST["right" . $component["componentID"]])) {
				$rights[$component["componentID"]] = true;
			} else {
				$rights[$component["componentID"]] = false;
			}
		}
	}
	
	$exists = $GLOBALS["database"]->stdGetTry("adminUser", array("username"=>$username), "customerID", false) !== false;
	if($exists) {
		$content .= addAccountForm("An account with the chosen name already exists.", $username, $rights, null);
		die(page($content));
	}
	
	if(!validAccountName($username)) {
		$content .= addAccountForm("Invalid account name.", $username, $rights, null);
		die(page($content));
	}
	
	if(!isset($_POST["confirm"])) {
		if($_POST["accountPassword1"] != $_POST["accountPassword2"]) {
			$content .= addAccountForm("The entered passwords do not match.", $username, $rights, null);
			die(page($content));
		}
		
		if($_POST["accountPassword1"] == "") {
			$content .= addAccountForm("Passwords must be at least one character long.", $username, $rights, null);
			die(page($content));
		}
		
		$content .= addAccountForm(null, $username, $rights, $_POST["accountPassword1"]);
		die(page($content));
	}
	
	$password = decryptPassword($_POST["accountEncryptedPassword"]);
	if($password === null) {
		$content .= addAccountForm("Internal error: invalid encrypted password. Please enter password again.", $username, $rights, null);
		die(page($content));
	}
	
	$accountID = $GLOBALS["database"]->stdNew("adminUser", array("customerID"=>customerID(), "username"=>$username, "password"=>md5($password)));
	if($rights === true) {
		$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$accountID, "componentID"=>0));
	} else {
		foreach(customerComponents() as $component) {
			if($rights[$component["componentID"]]) {
				$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$accountID, "componentID"=>$component["componentID"]));
			}
		}
	}
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/account.php?id=$accountID");
	
}

main();

?>