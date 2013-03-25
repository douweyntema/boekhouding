<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$check = function($condition, $error) use($listID) {
		if(!$condition) die(page(listHeader($listID) . removeMailListMemberForm($listID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	startTransaction();
	foreach(stdList("mailListMember", array("listID"=>$listID), "memberID") as $memberID) {
		if(post("member-$memberID") !== null) {
			stdDel("mailListMember", array("memberID"=>$memberID));
		}
	}
	commitTransaction();
	
	updateMail(customerID());
	
	redirect("mail/list.php?id=$listID");
}

main();

?>