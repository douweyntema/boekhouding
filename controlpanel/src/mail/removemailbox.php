<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), array("domainID", "localpart"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	$localpart = $mailbox["localpart"];
	
	$mailHtml = htmlentities($localpart . "@" . $domain);
	
	$content = "<h1>Mailbox $mailHtml</h1>\n";
	
	$content .= domainBreadcrumbs($mailbox["domainID"], array(array("name"=>"Mailbox {$mailbox["localpart"]}@{$domain}", "url"=>"{$GLOBALS["root"]}mail/mailbox.php?id=$addressID")));
	
	checkTrivialAction($content, "{$GLOBALS["root"]}mail/removemailbox.php?id=$addressID", "Remove mailbox", "Are you sure you want to remove this mailbox? This will permanently delete all mail stored in it.", "", "Yes, delete the mail");
	
	$GLOBALS["database"]->stdDel("mailAddress", array("addressID"=>$addressID));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id={$mailbox["domainID"]}");
}

main();

?>