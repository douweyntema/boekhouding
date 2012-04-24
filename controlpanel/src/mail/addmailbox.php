<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(domainHeader($domainID, "Add mailbox", "addmailbox.php?id=$domainID") . addMailboxForm($domainID, $error, $_POST)));
	};
	
	$localpart = post("localpart");
	$quota = post("quota") == "" ? null : post("quota");
	
	$spamboxType = post("spambox");
	if($spamboxType == "none") {
		$spambox = null;
		$spamQuota = null;
	} else if($spamboxType == "folder") {
		$spambox = post("spambox-folder");
		$spamQuota = post("spamquota") == "" ? null : post("spamquota");
	} else {
		$spambox = "";
		$spamQuota = null;
	}
	
	$virusboxType = post("virusbox");
	if($virusboxType == "none") {
		$virusbox = null;
		$virusQuota = null;
	} else if($virusboxType == "folder") {
		$virusbox = post("virusbox-folder");
		$virusQuota = post("virusquota") == "" ? null : post("virusquota");
	} else {
		$virusbox = "";
		$virusQuota = null;
	}
	
	$check(validLocalPart($localpart), "Invalid mailbox name");
	$check(!$GLOBALS["database"]->stdExists("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart)), "Another mailbox with the same name already exists");
	$check(!$GLOBALS["database"]->stdExists("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart)), "An alias with the same name already exists");
	$check(!$GLOBALS["database"]->stdExists("mailList", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailinx list with the same name already exists");
	$check($spamboxType != "folder" || validDirectory($spambox), "Invalid spambox");
	$check($virusboxType != "folder" || validDirectory($virusbox), "Invalid virusbox");
	$check($quota === null || (is_numeric($quota) && 1 <= $quota && $quota <= 100000), "Invalid quota");
	$check($spamQuota === null || (is_numeric($spamQuota) && 1 <= $spamQuota && $spamQuota <= 100000), "Invalid spambox quota");
	$check($virusQuota === null || (is_numeric($virusQuota) && 1 <= $virusQuota && $virusQuota <= 100000), "Invalid virusbox quota");
	if(post("confirm") === null) {
		$check(post("password-1") == post("password-2"), "The entered passwords do not match");
		$check(post("password-1") != "", "Passwords must be at least one character long");
		$check(false, null);
	}
	$password = decryptPassword(post("encrypted-password"));
	$check($password !== null, "Internal error: invalid encrypted password. Please enter password again.");
	
	$addressID = $GLOBALS["database"]->stdNew("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart, "password"=>base64_encode($password), "canUseSmtp"=>1, "canUseImap"=>1, "spambox"=>$spambox, "virusbox"=>$virusbox, "quota"=>$quota, "spamQuota"=>$spamQuota, "virusQuota"=>$virusQuota));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/mailbox.php?id=$addressID");
}

main();

?>