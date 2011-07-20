<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$content = "<h1>Domain $domain</h1>\n";
	
	if(count($GLOBALS["database"]->stdList("mailAddress", array("domainID"=>$domainID), "addressID")) > 0) {
		$content .= removeMailDomainForm($domainID, "Unable to remove this domain. There are still mailboxes connected to this domain. Remove them first if you want to remove this domain.");
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= removeMailDomainForm($domainID, null);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdDel("mailAlias", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("mailAddress", array("domainID"=>$domainID));
	$GLOBALS["database"]->stdDel("mailDomain", array("domainID"=>$domainID));
	$GLOBALS["database"]->commitTransaction();
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/");
}

main();

?>