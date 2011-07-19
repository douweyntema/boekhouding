<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	
	doMailAddress($addressID);
	
	$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), array("domainID", "localpart"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	$content = "<h1>Mailbox {$mailbox["localpart"]}@$domain</h1>\n";
	
	
	echo page($content);
}

main();

?>