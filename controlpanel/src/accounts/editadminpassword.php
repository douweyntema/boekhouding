<?php

require_once("common.php");

function main()
{
	doAccountsAdmin();
	
	$userID = get("id");
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
	
	$password = checkPassword($content, "{$GLOBALS["root"]}accounts/editadminpassword.php?id=" . $userID);
	
	$GLOBALS["database"]->stdSet("adminUser", array("userID"=>$userID), array("password"=>hashPassword($password)));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/adminaccount.php?id=$userID");
}

main();

?>