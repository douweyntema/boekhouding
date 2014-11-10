<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(domainHeader($domainID, "Add list", "addlist.php?id=$domainID") . addMailListForm($domainID, $error, $_POST)));
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
	
	$check(validLocalPart($localpart), "Invalid mailing list name.");
	$check(!stdExists("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailbox with the chosen name already exists.");
	$check(!stdExists("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart)), "An alias with the chosen name already exists.");
	$check(!stdExists("mailList", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailing list with the chosen name already exists.");
	$messages = array();
	foreach($realMembers as $member) {
		if(!validEmail($member)) {
			$memberHtml = htmlentities($member);
			$messages[] = "Invalid member address <em>$memberHtml</em>.";
		}
	}
	$check(count($messages) == 0, implode("<br />", $messages));
	$check(post("confirm") !== null, null);
	
	startTransaction();
	$listID = stdNew("mailList", array("domainID"=>$domainID, "localpart"=>$localpart));
	foreach($realMembers as $member) {
		if(stdExists("mailListMember", array("listID"=>$listID, "targetAddress"=>$member))) {
			continue;
		}
		stdNew("mailListMember", array("listID"=>$listID, "targetAddress"=>$member));
	}
	commitTransaction();
	
	updateMail(customerID());
	
	redirect("mail/list.php?id=$listID");
}

main();

?>