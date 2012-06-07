<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$content = domainHeader($domainID);
	$content .= editCatchAllFrom($domainID, "STUB");
	$content .= mailboxList($domainID);
	$content .= mailAliasList($domainID);
	$content .= mailListList($domainID);
	$content .= addMailboxForm($domainID);
	$content .= addMailAliasForm($domainID);
	$content .= addMailListForm($domainID);
	$content .= removeMailDomainForm($domainID);
	echo page($content);
}

main();

?>