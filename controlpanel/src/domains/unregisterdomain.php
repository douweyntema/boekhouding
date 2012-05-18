<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomains($domainID);
	doDomainsBilling();
	
	if(domainsIsSubDomain($domainID) || domainsDomainStatus($domainID) != "activeforever") {
		error404();
	}
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Domain " . domainsFormatDomainName($domainID), domainBreadcrumbs($domainID), crumbs("Unregister domain", "unregisterdomain.php?id=$domainID")) . unregisterDomainForm($domainID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	domainsDisableAutoRenew($domainID);
	
	redirect("domains/");
}

main();

?>