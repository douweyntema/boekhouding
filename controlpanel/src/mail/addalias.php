<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$domain = $GLOBALS["database"]->stdGet("mailDomain", array("domainID"=>$domainID), "name");
	
	$content = "<h1>New alias for doman $domain</h1>\n";
	
	$content .= domainBreadcrumbs($domainID, array(array("name"=>"Add alias", "url"=>"{$GLOBALS["root"]}mail/addalias.php?id=$domainID")));
	
	$localpart = post("localpart");
	$targetAddress = post("targetAddress");
	
	if(!validLocalPart($localpart)) {
		$content .= addMailAliasForm($domainID, "Invalid alias", $localpart, $targetAddress);
		die(page($content));
	}
	
	if($GLOBALS["database"]->stdGetTry("mailAddress", array("domainID"=>$domainID, "localpart"=>$localpart), "addressID", null) !== null) {
		$content .= addMailAliasForm($domainID, "A mailbox with the same name already exists", $localpart, $targetAddress);
		die(page($content));
	}
	
	if(!validEmail($targetAddress)) {
		$content .= addMailAliasForm($domainID, "Invalid target address", $localpart, $targetAddress);
		die(page($content));
	}
	
	if(post("confirm") === null) {
		$content .= addMailAliasForm($domainID, null, $localpart, $targetAddress);
		die(page($content));
	}
	
	$aliasID = $GLOBALS["database"]->stdNew("mailAlias", array("domainID"=>$domainID, "localpart"=>$localpart, "targetAddress"=>$targetAddress));
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/domain.php?id={$domainID}");
}

main();

?>