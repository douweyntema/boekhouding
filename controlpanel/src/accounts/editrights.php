<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccount($userID);
	
	$username = htmlentities(stdGet("adminUser", array("userID"=>$userID), "username"));
	
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
	
	startTransaction();
	if($rights === true) {
		stdDel("adminUserRight", array("userID"=>$userID));
		stdNew("adminUserRight", array("userID"=>$userID, "customerRightID"=>null));
	} else {
		stdDel("adminUserRight", array("userID"=>$userID));
		foreach(stdList("adminCustomerRight", array("customerID"=>customerID()), array("customerRightID", "right")) as $right) {
			if($rights[$right["right"]]) { // right...
				stdNew("adminUserRight", array("userID"=>$userID, "customerRightID"=>$right["customerRightID"]));
			}
		}
	}
	commitTransaction();
	
	updateAccounts(customerID());
	
	redirect("accounts/account.php?id=$userID");
}

main();

?>