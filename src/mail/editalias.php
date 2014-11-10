<?php

require_once("common.php");

function main()
{
	$aliasID = get("id");
	doMailAlias($aliasID);
	
	$check = function($condition, $error) use($aliasID) {
		if(!$condition) die(page(aliasHeader($aliasID) . editMailAliasForm($aliasID, $error, $_POST)));
	};
	
	$domainID = stdGet("mailAlias", array("aliasID"=>$aliasID), "domainID");
	$targetAddress = post("targetAddress");
	
	$check(validEmail($targetAddress), "Invalid target address.");
	$check(post("confirm") !== null, null);
	
	stdSet("mailAlias", array("aliasID"=>$aliasID), array("targetAddress"=>$targetAddress));
	
	updateMail(customerID());
	
	redirect("mail/domain.php?id=$domainID");
}

main();

?>