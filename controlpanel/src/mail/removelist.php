<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$domainID = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), "domainID");
	
	$check = function($condition, $error) use($listID) {
		if(!$condition) die(page(listHeader($listID) . removeMailListForm($listID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdDel("mailListMember", array("listID"=>$listID));
	$GLOBALS["database"]->stdDel("mailList", array("listID"=>$listID));
	$GLOBALS["database"]->commitTransaction();
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id={$domainID}");
}

main();

?>