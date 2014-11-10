<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomains($domainID);
	doDomainsBilling();
	
	if(domainsIsSubDomain($domainID) || domainsDomainStatus($domainID) != "active") {
		error404();
	}
	$autorenew = domainsDomainAutorenew($domainID);
	if($autorenew === null || !$autorenew) {
		error404();
	}
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Domain " . domainsFormatDomainName($domainID), domainBreadcrumbs($domainID), crumbs("Withdraw domain", "withdrawdomain.php?id=$domainID")) . withdrawDomainForm($domainID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	domainsDisableAutoRenew($domainID);
	
	redirect("domains/domain.php?id=$domainID");
}

main();

?>