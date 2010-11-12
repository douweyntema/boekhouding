<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	$aliasID = $_GET["id"];
	doMailAlias(null);
	$alias = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), array("domainID", "localpart"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$alias["domainID"]), "name");
	$content = "<h1>Alias {$alias["localpart"]}@$domain</h1>\n";
	
	$content .= mailboxList($aliasID);
	$content .= mailAliasList($domainID);
	
	echo page($content);
}

main();

?>