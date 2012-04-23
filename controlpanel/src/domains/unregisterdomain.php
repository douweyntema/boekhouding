<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doDomains($domainID);
	
	if(isSubDomain($domainID) || domainsDomainStatus($domainID) != "activeforever") {
		error404();
	}
	
	$check = function($condition, $error) use($domainID) {
		if(!$condition) die(page(makeHeader("Domain " . domainsFormatDomainName($domainID), domainBreadcrumbs($domainID), crumbs("Unregister domain", "unregisterdomain.php?id=$domainID")) . unregisterDomainForm($domainID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	domainsDisableAutoRenew($domainID);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}domains/");
}

main();

?>