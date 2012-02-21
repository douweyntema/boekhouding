<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	$content = "<h1>New mailinglist for doman $domain</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("name"=>"Add mailinglist", "url"=>"{$GLOBALS["root"]}mail/addlist.php?id=$domainID")));
	
	$localpart = post("localpart");
	$members = explode("\n", post("members"));
	
	if(!validLocalPart($localpart)) {
		$content .= addMailListForm($domainID, "Invalid mailinglist address", $localpart, $members);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdGetTry("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart), "addressID", null) !== null) {
		$content .= addMailListForm($domainID, "A mailbox with the same name already exists", $localpart, $members);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdGetTry("mailList", array("domainID"=>$domainID, "localpart"=>$localpart), "listID", null) !== null) {
		$content .= addMailListForm($domainID, "A mailinglist with the same name already exists", $localpart, $members);
		die(page($content));
	}
	
	foreach($members as $member) {
		if(trim($member) == "") {
			continue;
		}
		if(!validEmail($member)) {
			$content .= addMailListForm($domainID, "Invalid member address ($member)", $localpart, $members);
			die(page($content));
		}
	}
	
	if(post("confirm") === null) {
		$content .= addMailListForm($domainID, null, $localpart, $members);
		die(page($content));
	}
	
	$GLOBALS["database"]->startTransaction();
	$listID = $GLOBALS["database"]->stdNew("mailList", array("domainID"=>$domainID, "localpart"=>$localpart));
	foreach($members as $member) {
		if(trim($member) == "") {
			continue;
		}
		if($GLOBALS["database"]->stdExists("mailListMember", array("listID"=>$listID, "targetAddress"=>$member))) {
			continue;
		}
		$GLOBALS["database"]->stdNew("mailListMember", array("listID"=>$listID, "targetAddress"=>$member));
	}
	$GLOBALS["database"]->commitTransaction();
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/list.php?id={$listID}");
}

main();

?>