<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$check = function($condition, $error) use($listID) {
		if(!$condition) die(page(listHeader($listID) . addMailListMemberForm($listID, $error, $_POST)));
	};
	
	$members = explode(" ", str_replace(array(" ", "\n", "\t", ",", ";", "<", ">"), " ", post("members")));
	$realMembers = array();
	foreach($members as $member) {
		$member = trim($member);
		if(strpos($member, "@") === false) {
			continue;
		}
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
	
	$_POST["members"] = implode("\n", $realMembers);
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
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