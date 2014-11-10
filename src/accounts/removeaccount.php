<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccount($userID);
	$username = htmlentities(stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$check = function($condition, $error) use($userID, $username) {
		if(!$condition) die(page(makeHeader("Accounts - $username", accountBreadcrumbs($userID), crumbs("Remove account", "removeaccount.php?id=$userID")) . removeAccountForm($userID, $error, $_POST)));
	};
	
	$check(!accountsIsMainAccount($userID), "Unable to remove your main account.");
	$check(!stdExists("httpPath", array("hostedUserID"=>$userID)), "There are still websites configured for this account. Remove or reconfigure these sites before removing this account.");
	$check(post("confirm") !== null, null);
	
	if(!$GLOBALS["mysql_management_disabled"]) {
		mysqlRemoveAccount($username);
	}
	
	startTransaction();
	stdDel("adminUserRight", array("userID"=>$userID));
	stdDel("adminUser", array("userID"=>$userID, "customerID"=>customerID()));
	commitTransaction();
	
	updateAccounts(customerID());
	
	redirect("accounts/");
}

main();

?>