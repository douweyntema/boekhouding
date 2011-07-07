<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	$content = "<h1>New alias for doman $domain</h1>\n";
	
	$localpart = post("localpart");
	$targetAddress = post("targetAddress");
	
	if(post("confirm") === null) {
		$content .= addMailAliasForm($domainID, null, $localpart, $targetAddress);
		die(page($content));
	}
	
	$aliasID = $GLOBALS["database"]->stdNew("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart, "targetAddress"=>$targetAddress));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/alias.php?id=$aliasID");
}

main();

?>