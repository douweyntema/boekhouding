<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(addHeader("Add alias", "addalias.php", $domainID) . addMailAliasForm($domainID, $error, $_POST)));
	};
	
	$localpart = post("localpart");
	$targetAddress = post("targetAddress");
	
	if(strpos($targetAddress, "@") === false) {
		$targetAddress .= "@" . $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
		$_POST["targetAddress"] = $targetAddress;
	}
	
	$check(validLocalPart($localpart), "Invalid alias");
	$check(!$GLOBALS["database"]->stdExists("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailbox with the same name already exists");
	$check(!$GLOBALS["database"]->stdExists("mailList", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailing list with the same name already exists");
	$check(validEmail($targetAddress), "Invalid target address");
	$check(post("confirm") !== null, null);
	
	$aliasID = $GLOBALS["database"]->stdNew("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart, "targetAddress"=>$targetAddress));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id={$domainID}");
}

main();

?>