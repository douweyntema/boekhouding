<?php

require_once("common.php");

function main()
{
	$listID = get("id");
	doMailList($listID);
	
	$domainID = $GLOBALS["database"]->stdGet("mailList", array("listID"=>$listID), "domainID");
	
	$check = function($condition, $error) use($listID) {
		if(!$condition) die(page(listHeader($listID) . editMailListForm($listID, $error, $_POST)));
	};
	
	$localpart = post("localpart");
	
	$check(validLocalPart($localpart), "Invalid mailinglist address");
	$check(!$GLOBALS["database"]->stdExists("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailbox with the same name already exists");
	$check(!$GLOBALS["database"]->stdExists("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart)), "An alias with the same name already exists");
	$check($GLOBALS["database"]->stdGetTry("mailList", array("domainID"=>$domainID, "localpart"=>$localpart), "listID", $listID) == $listID, "A mailinglist with the same name already exists");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("mailList", array("listID"=>$listID), array("localpart"=>$localpart));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id={$domainID}");
}

main();

?>