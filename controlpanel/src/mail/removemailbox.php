<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$check = function($condition, $error) use($addressID) {
		if(!$condition) die(page(mailboxHeader($addressID) . removeMailboxForm($addressID, $error, $_POST)));
	};
	
	$domainID = $GLOBALS["database"]->stdGet("mailAddress", array("addressID"=>$addressID), "domainID");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdDel("mailAddress", array("addressID"=>$addressID));
	
	updateMail(customerID());
	
	redirect("mail/domain.php?id=$domainID");
}

main();

?>