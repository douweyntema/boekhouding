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
	$content .= mailListList($domainID);
	$content .= addMailboxForm($domainID, "", null, null, 1000, 100, 100, null, null);
	$content .= addMailAliasForm($domainID, "", null, null);
	$content .= addMailListForm($domainID, "", null, null);
	$content .= trivialActionForm("{$GLOBALS["root"]}mail/removedomain.php?id=$domainID", "", "Remove domain");

	echo page($content);
}

main();

?>