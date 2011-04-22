<?php

require_once("common.php");

function main()
{
	$domainID = get("id")
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$content = "<h1>Email for $domain</h1>\n";
	
	$content .= mailboxList($domainID);
	$content .= mailAliasList($domainID);
	$content .= addMailAliasForm($domainID, "", null, null);

	echo page($content);
}

main();

?>