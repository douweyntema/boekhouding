<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Add subdomain", domainBreadcrumbs($domainID), crumbs("Add subdomain", "addsubdomain.php?id=$domainID")) . addSubdomainForm($domainID, $error, $_POST)));
	};
	
	$check(($domainName = post("name")) !== null, "");
	$check(validDomainPart($domainName), "Invalid domain name.");
	$check(!$GLOBALS["database"]->stdExists("dnsDomain", array("parentDomainID"=>$domainID, "name"=>$domainName)), "The chosen subdomain already exists.");
	$check(post("confirm") !== null, null);
	
	$subdomainID = $GLOBALS["database"]->stdNew("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>$domainID, "name"=>$domainName));
	
	updateDomains(customerID());
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id={$subdomainID}");
}

main();

?>