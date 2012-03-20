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
		$rights = array();
		foreach(rights() as $right) {
			if(post("right-" . $right["name"]) !== null) {
				$rights[$right["name"]] = true;
			} else {
				$rights[$right["name"]] = false;
			}
		}
	}
	
	if(post("confirm") === null) {
		$content .= changeAccountRightsForm($userID, null, $rights);
		die(page($content));
	}
	
	if($rights === true || (isset($rights["mysql"]) && $rights["mysql"])) {
		mysqlEnableAccount($username);
	} else {
		mysqlDisableAccount($username);
	}
	
	$GLOBALS["database"]->startTransaction();
	if($rights === true) {
		$GLOBALS["database"]->stdDel("adminUserRight", array("userID"=>$userID));
		$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$userID, "customerRightID"=>null));
	} else {
		$GLOBALS["database"]->stdDel("adminUserRight", array("userID"=>$userID));
		foreach($GLOBALS["database"]->stdList("adminCustomerRight", array("customerID"=>customerID()), array("customerRightID", "right")) as $right) {
			if($rights[$right["right"]]) { // right...
				$GLOBALS["database"]->stdNew("adminUserRight", array("userID"=>$userID, "customerRightID"=>$right["customerRightID"]));
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