<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$mailSystemID = get("id");
	$mailSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	
	if(post("refresh") == "all") {
		refreshMailSystemDovecot($mailSystemID);
		refreshMailSystemExim($mailSystemID);
	} else if(post("refresh") == "dovecot") {
		refreshMailSystemDovecot($mailSystemID);
	} else if(post("refresh") == "exim") {
		refreshMailSystemExim($mailSystemID);
	}
	
	$content = makeHeader("Infrastructure - mailsystem " . htmlentities($mailSystemName), infrastructureBreadcrumbs(), crumbs($mailSystemName, "mailsystem.php?id=$mailSystemID"));
	$content .= mailSystemDetail($mailSystemID);
	$content .= mailSystemHostList($mailSystemID);
	$content .= mailSystemCustomersList($mailSystemID);
	$content .= mailSystemRefresh($mailSystemID);
	echo page($content);
}

main();

?>