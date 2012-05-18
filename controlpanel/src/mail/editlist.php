<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$check = function($condition, $error) use($listID) {
		if(!$condition) die(page(listHeader($listID) . editMailListForm($listID, $error, $_POST)));
	};
	
	$domainID = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), "domainID");
	$localpart = post("localpart");
	
	$check(validLocalPart($localpart), "Invalid mailing list name.");
	$check(!$GLOBALS["database"]->stdExists("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailbox with the chosen name already exists.");
	$check(!$GLOBALS["database"]->stdExists("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart)), "An alias with the chosen name already exists.");
	$check($GLOBALS["database"]->stdGetTry("mailList", array("domainID"=>$domainID, "localpart"=>$localpart), "listID", $listID) == $listID, "A mailing list with the chosen name already exists.");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("mailList", array("listID"=>$listID), array("localpart"=>$localpart));
	
	updateMail(customerID());
	
	redirect("mail/domain.php?id=$domainID");
}

main();

?>