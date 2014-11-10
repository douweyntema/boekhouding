<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$check = function($condition, $error) use($listID) {
		if(!$condition) die(page(listHeader($listID) . editMailListMemberForm($listID, $error, $_POST)));
	};
	
	$check(post("members") !== null, "");
	
	$members = explode("\n", post("members"));
	$realMembers = array();
	foreach($members as $member) {
		$member = trim($member);
		if($member != "") {
			$realMembers[] = $member;
		}
	}
	
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
	stdDel("mailListMember", array("listID"=>$listID));
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