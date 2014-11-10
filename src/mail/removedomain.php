<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doMailDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(domainHeader($domainID) . removeMailDomainForm($domainID, $error, $_POST)));
	};
	
	$check(!stdExists("mailAddress", array("domainID"=>$domainID)), "Unable to remove this domain; there are still mailboxes connected to it. Remove them first if you want to remove this domain.");
	$check(!stdExists("mailAlias", array("domainID"=>$domainID)), "Unable to remove this domain; there are still aliases connected to it. Remove them first if you want to remove this domain.");
	$check(!stdExists("mailList", array("domainID"=>$domainID)), "Unable to remove this domain; there are still mailing lists connected to it. Remove them first if you want to remove this domain.");
	$check(post("confirm") !== null, null);
	
	startTransaction();
	stdDel("mailDomain", array("domainID"=>$domainID));
	commitTransaction();
	
	updateMail(customerID());
	
	redirect("mail/");
}

main();

?>