<?php

require_once("common.php");

function main()
{
	doAccounts();
	
	$content = "<h1>Accounts</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Accounts", "url"=>"{$GLOBALS["root"]}accounts/"),
		array("name"=>"Add account", "url"=>"{$GLOBALS["root"]}accounts/addaccount.php")
		));
	
	$username = post("accountUsername");
	
	if($username === null) {
		$content .= addAccountForm();
		die(page($content));
	}
	
	if(post("rights") == "full") {
		$rights = true;
	} else {
		$components = customerComponents();
		$rights = array();
		foreach($components as $component) {
			if(post("right" . $component["componentID"]) !== null) {
				$rights[$component["componentID"]] = true;
			} else {
				$rights[$component["componentID"]] = false;
			}
		}
	}
	
	if(!validAccountName($username)) {
		$content .= addAccountForm("Invalid account name.", $username, $rights, null);
		die(page($content));
	}
	
	$exists = $GLOBALS["database"]->stdGetTry("adminUser", array("username"=>$username), "customerID", false) !== false;
	if($exists || reservedAccountName($username)) {
		$content .= addAccountForm("An account with the chosen name already exists.", $username, $rights, null);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		if(post("accountPassword1") != post("accountPassword2")) {
			$content .= addAccountForm("The entered passwords do not match.", $username, $rights, null);
			die(page($content));
		}
		
		if(post("accountPassword1") == "") {
			$content .= addAccountForm("Passwords must be at least one character long.", $username, $rights, null);
			die(page($content));
		}
		
		$content .= addAccountForm(null, $username, $rights, post("accountPassword1"));
		die(page($content));
	}
	
	$password = decryptPassword(post("accountEncryptedPassword"));
	if($password === null) {
		$content .= addAccountForm("Internal error: invalid encrypted password. Please enter password again.", $username, $rights, null);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	$accountID = $GLOBALS["database"]->stdNew("adminUser", array("customerID"=>customerID(), "username"=>$username, "password"=>hashPassword($password)));
	if($rights === true) {
		$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$accountID, "componentID"=>null));
	} else {
		foreach(customerComponents() as $component) {
			if($rights[$component["componentID"]]) {
				$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$accountID, "componentID"=>$component["componentID"]));
			}
		}
	}
	$GLOBALS["database"]->commitTransaction();
	
	// Distribute the accounts database
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/account.php?id=$accountID");
}

main();

?>