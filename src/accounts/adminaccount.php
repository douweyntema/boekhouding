<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccountsAdmin();
	
	$username = htmlentities(stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$content = makeHeader("Accounts - $username", accountBreadcrumbs($userID));
	$content .= changeAdminAccountPasswordForm($userID);
	$content .= removeAdminAccountForm($userID, "");
	echo page($content);
}

main();

?>