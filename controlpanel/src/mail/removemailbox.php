<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$domainID = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), "domainID");
	
	$check = function($condition, $error) use($addressID) {
		if(!$condition) die(page(mailboxHeader($addressID) . removeMailboxForm($addressID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdDel("mailAddress", array("addressID"=>$addressID));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id=$domainID");
}

main();

?>