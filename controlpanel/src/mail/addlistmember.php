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
	
	$GLOBALS["database"]->startTransaction();
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