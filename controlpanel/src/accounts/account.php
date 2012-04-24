<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccount($userID);
	
	$username = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$content = makeHeader("Accounts - $username", accountBreadcrumbs($userID));
	$content .= changeAccountPasswordForm($userID);
	$content .= changeAccountRightsForm($userID);
	$content .= removeAccountForm($userID);
	echo page($content);
}

main();

?>