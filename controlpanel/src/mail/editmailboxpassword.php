<?php

require_once("common.php");

function main()
{
	$mailboxID = get("id");
	doMailAddress($mailboxID);
	
	$mailbox = $GLOBALS["database"]->stdGetTry("mailAddress", array("addressID"=>$mailboxID), array("domainID", "localpart"), false);
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	$localpart = $mailbox["localpart"];
	
	$mailHtml = htmlentities($localpart . "@" . $domain);
	
	$content = "<h1>Mailbox $mailHtml</h1>\n";
	$content .= domainBreadcrumbs($mailbox["domainID"], array(array("name"=>"Mailbox {$mailbox["localpart"]}@{$domain}", "url"=>"{$GLOBALS["root"]}mail/mailbox.php?id=$mailboxID")));
	
	$password = checkPassword($content, "{$GLOBALS["root"]}mail/editmailboxpassword.php?id=" . $mailboxID);
	
	$GLOBALS["database"]->stdSet("mailAddress", array("addressID"=>$mailboxID), array("password"=>$password));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/mailbox.php?id=$mailboxID");
}

main();

?>