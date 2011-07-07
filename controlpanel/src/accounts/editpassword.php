<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccountsUser($userID);
	$username = $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID, "customerID"=>customerID()), "username", false);
	
	if($username === false) {
		accountNotFound($userID);
	}
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Accounts - $usernameHtml</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Accounts", "url"=>"{$GLOBALS["root"]}accounts/"),
		array("name"=>$username, "url"=>"{$GLOBALS["root"]}accounts/account.php?id=" . $userID),
		array("name"=>"Change password", "url"=>"{$GLOBALS["root"]}accounts/editpassword.php?id=" . $userID)
		));

	if(post("accountPassword1") === null || post("accountPassword2") === null) {
		$content .= changeAccountPasswordForm($userID);
		die(page($content));
	} else {
		if(post("confirm") === null) {
			if(post("accountPassword1") != post("accountPassword2")) {
				$content .= changeAccountPasswordForm($userID, "The entered passwords do not match.", null);
				die(page($content));
			}
			
			if(post("accountPassword1") == "") {
				$content .= changeAccountPasswordForm($userID, "Passwords must be at least one character long.", null);
				die(page($content));
			}
			
			$content .= changeAccountPasswordForm($userID, null, post("accountPassword1"));
			die(page($content));
		}
		
		$password = decryptPassword(post("accountEncryptedPassword"));
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
}

main();

?>