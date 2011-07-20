<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$content = "<h1>Domain $domain</h1>\n";
	
	$content .= domainBreadcrumbs($domainID);
	
	$content .= mailboxList($domainID);
	$content .= mailAliasList($domainID);
	$content .= addMailAliasForm($domainID, "", null, null);
	$content .= addMailboxForm($domainID, "", null, null, 1000, 100, 100, null, null);
	$content .= removeMailDomainForm($domainID, "");

	echo page($content);
}

main();

?>