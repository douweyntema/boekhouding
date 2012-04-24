<?php

require_once("common.php");

function main()
{
	$aliasID = get("id");
	doMailAlias($aliasID);
	
	$check = function($condition, $error) use($aliasID) {
		if(!$condition) die(page(aliasHeader($aliasID) . editMailAliasForm($aliasID, $error, $_POST)));
	};
	
	$domainID = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), "domainID");
	$targetAddress = post("targetAddress");
	
	$check(validEmail($targetAddress), "Invalid target address");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("mailAlias", array("aliasID"=>$aliasID), array("targetAddress"=>$targetAddress));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id=$domainID");
}

main();

?>