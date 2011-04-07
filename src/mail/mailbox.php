<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	$mailboxID = $_GET["id"];
	doMailbox($mailboxID);
	$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$mailboxID), array("domainID", "localpart"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	$content = "<h1>Mailbox {$mailbox["localpart"]}@$domain</h1>\n";
	
	
	echo page($content);
}

main();

?>