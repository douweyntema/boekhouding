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
		$rights = array();
		foreach(rights() as $right) {
			if(post("right-" . $right["name"]) !== null) {
				$rights[$right["name"]] = true;
			} else {
				$rights[$right["name"]] = false;
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
	
	// Distribute the accounts database
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/account.php?id=$accountID");
}

main();

?>