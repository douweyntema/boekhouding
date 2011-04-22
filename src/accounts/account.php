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
		array("name"=>$username, "url"=>"{$GLOBALS["root"]}accounts/account.php?id=" . $userID)
		));
	
	$content .= changeAccountPasswordForm($userID);
	$content .= changeAccountRightsForm($userID);
	$content .= removeAccountForm($userID, "");
	
	echo page($content);
}

main();

?>