<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$mailSystemID = get("id");
	$mailSystemName = $GLOBALS["database"]->stdGet("infrastructureMailSystem", array("mailSystemID"=>$mailSystemID), "name");
	$mailSystemNameHtml = htmlentities($mailSystemName);
	
	$content = "<h1>Infrastructure - mailsystem $mailSystemNameHtml</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Infrastructure", "url"=>"{$GLOBALS["root"]}infrastructure/"),
		array("name"=>"$mailSystemName", "url"=>"{$GLOBALS["root"]}infrastructure/mailsystem.php?id=$mailSystemID")
		));
	
	if(post("refresh") == "all") {
		refreshMailSystemDovecot($mailSystemID);
		refreshMailSystemExim($mailSystemID);
	} else if(post("refresh") == "dovecot") {
		refreshMailSystemDovecot($mailSystemID);
	} else if(post("refresh") == "exim") {
		refreshMailSystemExim($mailSystemID);
	}
	
	$content .= mailSystemDetail($mailSystemID);
	$content .= mailSystemHostList($mailSystemID);
	$content .= mailSystemCustomersList($mailSystemID);
	$content .= mailSystemRefresh($mailSystemID);
	
	echo page($content);
}

main();

?>