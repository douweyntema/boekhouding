<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	$domainID = $_GET["id"];
	doMailDomain($domainID);
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	$content = "<h1>Email for $domain</h1>\n";
	
	$content .= mailboxList($domainID);
	$content .= mailAliasList($domainID);
	
	echo page($content);
}

main();

?>