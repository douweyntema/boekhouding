<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	$content = "<h1>Domain $domain</h1>\n";
	
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