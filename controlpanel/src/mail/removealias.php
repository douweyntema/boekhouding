<?php

require_once("common.php");

function main()
{
	$aliasID = get("id");
	doMailAlias($aliasID);
	
	$check = function($condition, $error) use($aliasID) {
		if(!$condition) die(page(aliasHeader($aliasID) . removeMailAliasForm($aliasID, $error, $_POST)));
	};
	
	$domainID = stdGet("mailAlias", array("aliasID"=>$aliasID), "domainID");
	$check(post("confirm") !== null, null);
	
	stdDel("mailAlias", array("aliasID"=>$aliasID));
	
	updateMail(customerID());
	
	redirect("mail/domain.php?id=$domainID");
}

main();

?>