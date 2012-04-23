<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomains($domainID);
	
	if(isSubDomain($domainID) || domainsDomainStatus($domainID) != "active") {
		error404();
	}
	$autorenew = domainsDomainAutorenew($domainID);
	if($autorenew === null || $autorenew) {
		error404();
	}
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Domain " . domainsFormatDomainName($domainID), domainBreadcrumbs($domainID), crumbs("Restore domain", "restoredomain.php?id=$domainID")) . restoreDomainForm($domainID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	domainsEnableAutoRenew($domainID);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id=$domainID");
}

main();

?>