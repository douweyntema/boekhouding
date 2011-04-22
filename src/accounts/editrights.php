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
		array("name"=>"Edit rights", "url"=>"{$GLOBALS["root"]}accounts/editrights.php?id=" . $userID)
		));
	
	if(post("rights") === null) {
		$content .= changeAccountRightsForm($userID);
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
	
	if(post("confirm") === null) {
		$content .= changeAccountRightsForm($userID, null, $rights);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdDel("adminUserRight", array("userID"=>$userID));
	if($rights === true) {
		$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$userID, "componentID"=>null));
	} else {
		foreach(customerComponents() as $component) {
			if($rights[$component["componentID"]]) {
				$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$userID, "componentID"=>$component["componentID"]));
			}
		}
	}
	$GLOBALS["database"]->commitTransaction();
	
	// Distribute the accounts database
	updateAccounts(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/account.php?id=$userID");
}

main();

?>