<?php

require_once("common.php");

function main()
{
	$userID = get("id");
	doAccount($userID);
	
	$username = htmlentities($GLOBALS["database"]->stdGet("adminUser", array("userID"=>$userID), "username"));
	
	$content = makeHeader("Accounts - $username", accountBreadcrumbs($userID));
	$content .= changeAccountPasswordForm($userID);
	if(accountsIsMainAccount($userID)) {
		$content .= operationForm(null, "", "Remove account", null, array(
			array("type"=>"html", "html"=>"This is your main account; it can not be removed.")
			), null);
	} else {
		$content .= changeAccountRightsForm($userID);
		$content .= removeAccountForm($userID);
	}
	echo page($content);
}

main();

?>