<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$check = function($condition, $error) use($listID) {
		if(!$condition) die(page(listHeader($listID) . removeMailListForm($listID, $error, $_POST)));
	};
	
	$domainID = stdGet("mailList", array("listID"=>$listID), "domainID");
	$check(post("confirm") !== null, null);
	
	startTransaction();
	stdDel("mailListMember", array("listID"=>$listID));
	stdDel("mailList", array("listID"=>$listID));
	commitTransaction();
	
	updateMail(customerID());
	
	redirect("mail/domain.php?id=$domainID");
}

main();

?>