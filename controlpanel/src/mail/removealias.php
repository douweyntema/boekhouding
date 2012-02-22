<?php

require_once("common.php");

function main()
{
	$aliasID = get("id");
	doMailAlias($aliasID);
	
	$domainID = $GLOBALS["database"]->stdGet("mailAlias", array("aliasID"=>$aliasID), "domainID");
	
	$check = function($condition, $error) use($aliasID) {
		if(!$condition) die(page(aliasHeader($aliasID) . removeMailAliasForm($aliasID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdDel("mailAlias", array("aliasID"=>$aliasID));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id=$domainID");
}

main();

?>