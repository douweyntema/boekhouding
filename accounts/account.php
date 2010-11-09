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
	
	if($GLOBALS["database"]->stdGetTry("adminUserRight", array("userID"=>$userID, "componentID"=>0), "userID", false) !== false) {
		$rights = true;
	} else {
		$components = customerComponents();
		$rights = array();
		foreach($components as $component) {
			$rights[$component["componentID"]] = false;
		}
		foreach($GLOBALS["database"]->stdList("adminUserRight", array("userID"=>$userID), "componentID") as $componentID) {
			$rights[$componentID] = true;
		}
	}
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Accounts - $usernameHtml</h1>\n";
	
	$content .= changeAccountPasswordForm($userID, "", null);
	$content .= changeAccountRightsForm($userID, "", $rights);
	$content .= removeAccountForm($userID, "");
	
	echo page($content);
}

main();

?>