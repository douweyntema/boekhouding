<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	$aliasID = $_GET["id"];
	doMailAlias($aliasID);
	
	$alias = $GLOBALS["database"]->stdGetTry("mailAlias", array("aliasID"=>$aliasID), array("domainID", "localpart", "targetAddress"), false);
	
	if($alias === false) {
		aliasNotFound($aliasID);
	}
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$alias["domainID"]), "name");
	
	$content = "<h1>Alias {$alias["localpart"]}@$domain</h1>\n";
	
	$content .= editMailAliasForm($aliasID, "", $alias["localpart"], $alias["targetAddress"]);
	$content .= removeMailAliasForm($aliasID, "");
	
	echo page($content);
}

main();

?>