<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	
	$content = makeHeader((isSubDomain($domainID) ? "Subdomain " : "Domain ") . domainsFormatDomainName($domainID), domainBreadcrumbs($domainID));
	$content .= domainDetail($domainID);
	$content .= subDomainsList($domainID);
	$content .= editAddressForm($domainID, "STUB");
	$content .= editMailForm($domainID, "STUB");
	if(isSubDomain($domainID)) {
		$content .= deleteDomainForm($domainID);
	} else if(!canAccessComponent("billing")) {
		// All further operations forbidden
	} else if(($status = domainsDomainStatus($domainID)) == "activeforever") {
		$content .= unregisterDomainForm($domainID);
	} else if($status == "active" && ($autorenew = domainsDomainAutorenew($domainID)) === null) {
		// Unknown status
	} else if($status == "active" && $autorenew) {
		$content .= withdrawDomainForm($domainID);
	} else if($status == "active") {
		$content .= restoreDomainForm($domainID);
	}
	$content .= addSubdomainForm($domainID);
	echo page($content);
}

main();

?>