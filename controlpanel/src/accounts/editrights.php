<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccount($userID);
	
	$username = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$check = function($condition, $error) use($userID, $username) {
		if(!$condition) die(page(makeHeader("Accounts - $username", accountBreadcrumbs($userID), crumbs("Edit rights", "editrights.php?id=$userID")) . changeAccountRightsForm($userID, $error, $_POST)));
	};
	
	if(post("rights") == "full") {
		$rights = true;
	} else if(post("rights") == "limited") {
		$rights = array();
		foreach(rights() as $right) {
			if(post("right-" . $right["name"]) !== null) {
				$rights[$right["name"]] = true;
			} else {
				$rights[$right["name"]] = false;
			}
		}
	} else {
		$check(false, "");
	}
	
	$check(!accountsIsMainAccount($userID), "Unable to edit rights on your main account.");
	$check(post("confirm") !== null, null);
	
	if(!$GLOBALS["mysql_management_disabled"]) {
		if($rights === true || (isset($rights["mysql"]) && $rights["mysql"])) {
			mysqlEnableAccount($username);
		} else {
			mysqlDisableAccount($username);
		}
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
	
	updateAccounts(customerID());
	
	redirect("accounts/account.php?id=$userID");
}

main();

?>