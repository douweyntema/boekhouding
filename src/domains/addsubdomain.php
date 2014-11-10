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
	$check(!stdExists("dnsDomain", array("parentDomainID"=>$domainID, "name"=>$domainName)), "The chosen subdomain already exists.");
	$check(post("confirm") !== null, null);
	
	$subdomainID = stdNew("dnsDomain", array("customerID"=>customerID(), "parentDomainID"=>$domainID, "name"=>$domainName));
	
	updateDomains(customerID());
	
	redirect("domains/domain.php?id=$subdomainID");
}

main();

?>