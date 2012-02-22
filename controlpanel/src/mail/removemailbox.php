<?php

require_once("common.php");

function main()
{
	$addressID = get("id");
	doMailAddress($addressID);
	
	$domainID = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), "domainID");
	
	$check = function($condition, $error) use($addressID) {
		if(!$condition) die(page(addressHeader($addressID) . removeMailAddressForm($addressID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdDel("mailAddress", array("addressID"=>$addressID));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id=$domainID");
}

main();

?>