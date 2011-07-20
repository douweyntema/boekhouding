<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), array("domainID", "localpart"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	
	$content = "<h1>Mailbox {$mailbox["localpart"]}@$domain</h1>\n";
	
	$content .= domainBreadcrumbs($mailbox["domainID"], array(array("name"=>"Mailbox {$mailbox["localpart"]}@{$domain}", "url"=>"{$GLOBALS["root"]}mail/mailbox.php?id=$addressID")));
	
	
	echo page($content);
}

main();

?>