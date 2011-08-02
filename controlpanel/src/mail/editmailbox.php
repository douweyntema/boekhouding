<?php

require_once("common.php");

function main()
{
	$mailboxID = get("id");
	doMailAddress($mailboxID);
	
	$mailbox = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$mailboxID), array("domainID", "localpart", "spambox", "virusbox", "quota", "spamQuota", "virusQuota"));
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$mailbox["domainID"]), "name");
	$localpart = $mailbox["localpart"];
	
	$mailHtml = htmlentities($localpart . "@" . $domain);
	
	$content = "<h1>Mailbox $mailHtml</h1>\n";
	
	$content .= domainBreadcrumbs($mailbox["domainID"], array(array("name"=>"Mailbox {$mailbox["localpart"]}@{$domain}", "url"=>"{$GLOBALS["root"]}mail/mailbox.php?id=$mailboxID")));
	
	$quota = post("quota");
	$spamboxType = post("spambox");
	if($spamboxType == "none") {
		$spambox = null;
		$spamQuota = null;
		$checkspambox = false;
	} else if($spamboxType == "inbox") {
		$spambox = "";
		$spamQuota = null;
		$checkspambox = false;
	} else {
		$spambox = post("spambox-folder");
		$spamQuota = post("spamquota");
		$checkspambox = true;
	}
	
	$virusboxType = post("virusbox");
	if($virusboxType == "none") {
		$virusbox = null;
		$virusQuota = null;
		$checkvirusbox = false;
	} else if($virusboxType == "inbox") {
		$virusbox = "";
		$virusQuota = null;
		$checkvirusbox = false;
	} else {
		$virusbox = post("virusbox-folder");
		$virusQuota = post("virusquota");
		$checkvirusbox = true;
	}
	
	if($checkspambox && !validDirectory($spambox)) {
		$content .= editMailboxForm($mailboxID, "Invalid spambox", $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($checkvirusbox && !validDirectory($virusbox)) {
		$content .= editMailboxForm($mailboxID, "Invalid virusbox", $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if(!(is_numeric($quota) && 1 < $quota && $quota < 100000)) {
		$content .= editMailboxForm($mailboxID, "Invalid quota", $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($checkspambox && !($spamQuota == "" || (is_numeric($spamQuota) && 1 < $spamQuota && $spamQuota < 100000))) {
		$content .= editMailboxForm($mailboxID, "Invalid spambox quota", $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($checkvirusbox && !($virusQuota == "" || (is_numeric($virusQuota) && 1 < $virusQuota && $virusQuota < 100000))) {
		$content .= editMailboxForm($mailboxID, "Invalid virusbox quota", $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= editMailboxForm($mailboxID, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($spamQuota === "") {
		$spamQuota = null;
	}
	if($virusQuota === "") {
		$virusQuota = null;
	}
	
	$GLOBALS["database"]->stdSet("mailAddress", array("addressID"=>$mailboxID), array("spambox"=>$spambox, "virusbox"=>$virusbox, "quota"=>$quota, "spamQuota"=>$spamQuota, "virusQuota"=>$virusQuota));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/mailbox.php?id=$mailboxID");
}

main();

?>