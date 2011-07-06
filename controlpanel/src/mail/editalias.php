<?php

require_once("common.php");

function main()
{
	$aliasID = get("id");
	doMailAlias($aliasID);
	
	$alias = $GLOBALS["database"]->stdGetTry("mailAlias", array("aliasID"=>$aliasID), array("domainID", "localpart", "targetAddress"), false);
	
	if($alias === false) {
		aliasNotFound($aliasID);
	}
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$alias["domainID"]), "name");
	
	$content = "<h1>Alias {$alias["localpart"]}@$domain</h1>\n";
	
	$localpart = post("localpart");
	$targetAddress = post("targetAddress");
	
	if(post("confirm") === null) {
		$content .= editMailAliasForm($aliasID, null, $localpart, $targetAddress);
		die(page($content));
	}
	
	$GLOBALS["database"]->stdSet("mailAlias", array("aliasID"=>$aliasID), array("localpart"=>$localpart, "targetAddress"=>$targetAddress));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/alias.php?id=$aliasID");
}

main();

?>