<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	$content = "<h1>New mailbox for doman $domain</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("name"=>"Add mailbox", "url"=>"{$GLOBALS["root"]}mail/addmailbox.php?id=$domainID")));
	
	$localpart = post("localpart");
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
	
	if(!validLocalpart($localpart)) {
		$content .= addMailboxForm($domainID, "Invalid mailbox name", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdGetTry("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart), "addressID", null) !== null) {
		$content .= addMailboxForm($domainID, "Another mailbox with the same name already exists", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdGetTry("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart), "aliasID", null) !== null) {
		$content .= addMailboxForm($domainID, "An alias with the same name already exists", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($checkspambox && !validDirectory($spambox)) {
		$content .= addMailboxForm($domainID, "Invalid spambox", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($checkvirusbox && !validDirectory($virusbox)) {
		$content .= addMailboxForm($domainID, "Invalid virusbox", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if(!(is_numeric($quota) && 1 < $quota && $quota < 100000)) {
		$content .= addMailboxForm($domainID, "Invalid quota", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($checkspambox && !(is_numeric($spamQuota) && 1 < $spamQuota && $spamQuota < 100000)) {
		$content .= addMailboxForm($domainID, "Invalid spambox quota", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if($checkvirusbox && !(is_numeric($virusQuota) && 1 < $virusQuota && $virusQuota < 100000)) {
		$content .= addMailboxForm($domainID, "Invalid virusbox quota", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		if(post("password1") != post("password2")) {
			$content .= addMailboxForm($domainID, "The entered passwords do not match", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
			die(page($content));
		}
		
		if(post("password1") == "") {
			$content .= addMailboxForm($domainID, "Passwords must be at least one character long", $localpart, null, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
			die(page($content));
		}
		
		$content .= addMailboxForm($domainID, null, $localpart, post("password1"), $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	$password = decryptPassword(post("encryptedPassword"));
	if($password === null) {
		$content .= addMailboxForm($domainID, "Internal error: invalid encrypted password. Please enter password again.", $localpart, $password, $quota, $spamQuota, $virusQuota, $spambox, $virusbox);
		die(page($content));
	}
	
	$mailboxID = $GLOBALS["database"]->stdNew("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart, "password"=>base64_encode($password), "canUseSmtp"=>1, "canUseImap"=>1, "spambox"=>$spambox, "virusbox"=>$virusbox, "quota"=>$quota, "spamQuota"=>$spamQuota, "virusQuota"=>$virusQuota));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/mailbox.php?id=$mailboxID");
}

main();

?>