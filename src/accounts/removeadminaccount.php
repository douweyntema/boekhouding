<?php

require_once("common.php");

function main()
{
	doAccountsAdmin();
	$userID = get("id");
	$username = htmlentities(stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$check = function($condition, $error) use($userID, $username) {
		if(!$condition) die(page(makeHeader(sprintf(_("Accounts - %s"), $username), accountBreadcrumbs($userID), crumbs(_("Remove admin account"), "removeadminaccount.php?id=$userID")) . removeAdminAccountForm($userID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	stdDel("adminUser", array("userID"=>$userID));
	
	redirect("accounts/");
}

main();

?>