<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(addHeader("Add list", "addlist.php", $domainID) . addMailListForm($domainID, $error, $_POST)));
	};
	
	$localpart = post("localpart");
	$members = explode("\n", post("members"));
	$realMembers = array();
	foreach($members as $member) {
		$member = trim($member);
		if($member != "") {
			$realMembers[] = $member;
		}
	}
	
	$check(validLocalPart($localpart), "Invalid mailinglist address");
	$check(!$GLOBALS["database"]->stdExists("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailbox with the same name already exists");
	$check(!$GLOBALS["database"]->stdExists("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart)), "An alias with the same name already exists");
	$check(!$GLOBALS["database"]->stdExists("mailList", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailinglist with the same name already exists");
	foreach($realMembers as $member) {
		$check(validEmail($member), "Invalid member address ($member)");
	}
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->startTransaction();
	$listID = $GLOBALS["database"]->stdNew("mailList", array("domainID"=>$domainID, "localpart"=>$localpart));
	foreach($realMembers as $member) {
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