<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomains($domainID);
	doDomainsBilling();
	
	if(isSubDomain($domainID) || domainsDomainStatus($domainID) != "active") {
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
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/domain.php?id=$domainID");
}

main();

?>