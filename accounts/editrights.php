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
	
	$usernameHtml = htmlentities($username);
	
	$content = "<h1>Accounts - $usernameHtml</h1>\n";
	
	if(!isset($_POST["confirm"])) {
		$content .= changeAccountRightsForm($userID, null, $rights);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdDel("adminUserRight", array("userID"=>$userID));
	if($rights === true) {
		$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$userID, "componentID"=>0));
	} else {
		foreach(customerComponents() as $component) {
			if($rights[$component["componentID"]]) {
				$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$userID, "componentID"=>$component["componentID"]));
			}
		}
	}
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}accounts/account.php?id=$userID");
}

main();

?>