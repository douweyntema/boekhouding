<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$domainID = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), "domainID");
	
	$check = function($condition, $error) use($addressID) {
		if(!$condition) die(page(mailboxHeader($addressID) . editMailboxForm($addressID, $error, $_POST)));
	};
	
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
	
	$check($spamboxType != "folder" || validDirectory($spambox), "Invalid spam folder.");
	$check($virusboxType != "folder" || validDirectory($virusbox), "Invalid malware folder.");
	$check($quota === null || (is_numeric($quota) && 1 <= $quota && $quota <= 100000), "Invalid maximum size.");
	$check($spamQuota === null || (is_numeric($spamQuota) && 1 <= $spamQuota && $spamQuota <= 100000), "Invalid spam folder size.");
	$check($virusQuota === null || (is_numeric($virusQuota) && 1 <= $virusQuota && $virusQuota <= 100000), "Invalid malware folder size.");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("mailAddress", array("addressID"=>$addressID), array("spambox"=>$spambox, "virusbox"=>$virusbox, "quota"=>$quota, "spamQuota"=>$spamQuota, "virusQuota"=>$virusQuota));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/mailbox.php?id=$addressID");
}

main();

?>