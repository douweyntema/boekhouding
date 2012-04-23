<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomains($domainID);
	
	if(!isSubDomain($domainID)) {
		error404();
	}
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Domain " . domainsFormatDomainName($domainID), domainBreadcrumbs($domainID), crumbs("Delete subdomain", "deletedomain.php?id=$domainID")) . deleteDomainForm($domainID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	$parentDomainID = $GLOBALS["database"]->stdGet("dnsDomain", array("domainID"=>$domainID), "parentDomainID");
	
	$GLOBALS["database"]->startTransaction();
	removeDomain($domainID);
	$GLOBALS["database"]->commitTransaction();
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id=$parentDomainID");
}

main();

?>