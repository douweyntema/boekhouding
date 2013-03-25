<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$mailSystemID = get("id");
	$mailSystemName = stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	
	if(post("refreshall") !== null || post("refreshdovecot") !== null) {
		infrastructureRefreshMailSystemDovecot($mailSystemID);
	}
	if(post("refreshall") !== null || post("refreshexim") !== null) {
		infrastructureRefreshMailSystemExim($mailSystemID);
	}
	
	$content = makeHeader("Infrastructure - mailsystem " . htmlentities($mailSystemName), infrastructureBreadcrumbs(), crumbs($mailSystemName, "mailsystem.php?id=$mailSystemID"));
	$content .= mailSystemDetail($mailSystemID);
	$content .= mailSystemHostList($mailSystemID);
	$content .= mailSystemCustomersList($mailSystemID);
	$content .= mailSystemRefreshForm($mailSystemID);
	echo page($content);
}

main();

?>