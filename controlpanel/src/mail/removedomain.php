<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(domainHeader($domainID) . removeMailDomainForm($domainID, $error, $_POST)));
	};
	
	$check(!$GLOBALS["database"]->stdExists("mailAddress", array("domainID"=>$domainID)), "Unable to remove this domain. There are still mailboxes connected to this domain. Remove them first if you want to remove this domain.");
	$check(!$GLOBALS["database"]->stdExists("mailAlias", array("domainID"=>$domainID)), "Unable to remove this domain. There are still aliases connected to this domain. Remove them first if you want to remove this domain.");
	$check(!$GLOBALS["database"]->stdExists("mailList", array("domainID"=>$domainID)), "Unable to remove this domain. There are still mailing lists connected to this domain. Remove them first if you want to remove this domain.");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdDel("mailDomain", array("domainID"=>$domainID));
	$GLOBALS["database"]->commitTransaction();
	
	updateMail(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}mail/");
}

main();

?>