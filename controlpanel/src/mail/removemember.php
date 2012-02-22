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
	
	$GLOBALS["database"]->startTransaction();
	foreach($GLOBALS["database"]->stdList("mailListMember", array("listID"=>$listID), "memberID") as $memberID) {
		if(post("member-$memberID") !== null) {
			$GLOBALS["database"]->stdDel("mailListMember", array("memberID"=>$memberID));
		}
	}
	$GLOBALS["database"]->commitTransaction();
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/list.php?id=$listID");
}

main();

?>