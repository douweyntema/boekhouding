<?php

require_once("common.php");
doAccountsAdmin($_GET["id"]);

function main()
{
	$userID = $_GET["id"];
	$username = $GLOBALS["database"]->stdGetTry("adminUser", array("userID"=>$userID, "customerID"=>null), "username", false);
	
	if($username === false) {
		accountNotFound($userID);
	}
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Admin Accounts - $usernameHtml</h1>\n";
	$content .= breadcrumbs(array(
		array("name"=>"Admin Accounts", "url"=>"{$GLOBALS["root"]}accounts/"),
		array("name"=>$username, "url"=>"{$GLOBALS["root"]}accounts/adminaccount.php?id=" . $userID),
		array("name"=>"Change password", "url"=>"{$GLOBALS["root"]}accounts/editadminpassword.php?id=" . $userID)
		));
	
	if(!isset($_POST["accountPassword1"]) || !isset($_POST["accountPassword2"])) {
		$content .= changeAdminAccountPasswordForm($userID);
		die(page($content));
	} else {
		if(!isset($_POST["confirm"])) {
			if($_POST["accountPassword1"] != $_POST["accountPassword2"]) {
				$content .= changeAdminAccountPasswordForm($userID, "The entered passwords do not match.", null);
				die(page($content));
			}
			
			if($_POST["accountPassword1"] == "") {
				$content .= changeAdminAccountPasswordForm($userID, "Passwords must be at least one character long.", null);
				die(page($content));
			}
			
			$content .= changeAdminAccountPasswordForm($userID, null, $_POST["accountPassword1"]);
			die(page($content));
		}
		
		$password = decryptPassword($_POST["accountEncryptedPassword"]);
		if($password === null) {
			$content .= changeAdminAccountPasswordForm($userID, "Internal error: invalid encrypted password. Please enter password again.", null);
			die(page($content));
		}
		
		$GLOBALS["database"]->stdSet("adminUser", array("userID"=>$userID), array("password"=>hashPassword($password)));
		
		header("HTTP/1.1 303 See Other");
		header("Location: {$GLOBALS["root"]}accounts/adminaccount.php?id=$userID");
	}
}

main();

?>