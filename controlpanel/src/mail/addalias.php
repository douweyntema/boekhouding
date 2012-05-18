<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(domainHeader($domainID, "Add alias", "addalias.php?id=$domainID") . addMailAliasForm($domainID, $error, $_POST)));
	};
	
	$localpart = post("localpart");
	$targetAddress = post("targetAddress");
	
	if(strpos($targetAddress, "@") === false) {
		$targetAddress .= "@" . domainName($domainID);
		$_POST["targetAddress"] = $targetAddress;
	}
	
	$check(validLocalPart($localpart), "Invalid alias name.");
	$check(!$GLOBALS["database"]->stdExists("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailbox with the chosen name already exists.");
	$check(!$GLOBALS["database"]->stdExists("mailList", array("domainID"=>$domainID, "localpart"=>$localpart)), "A mailing list with the chosen name already exists.");
	$check(validEmail($targetAddress), "Invalid target address.");
	$check(post("confirm") !== null, null);
	
	$aliasID = $GLOBALS["database"]->stdNew("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart, "targetAddress"=>$targetAddress));
	
	updateMail(customerID());
	
	redirect("domain.php?id=$domainID");
}

main();

?>