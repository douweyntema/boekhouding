<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	
	$domainName = domainName($domainID);
	$title = isSubDomain($domainID) ? "Subdomain" : "Domain";
	$content = "<h1>$title " . $domainName . "</h1>\n";
	
	$content .= domainBreadcrumbs($domainID);
	$content .= domainDetail($domainID);
	$content .= subDomainsList($domainID);
// 	$content .= editHostsForm($domainID, "STUB");
	$content .= editAddressTypeForm($domainID, "STUB");
	$content .= editMailTypeForm($domainID, "STUB");
	$content .= domainRemoval($domainID);
	$content .= addSubdomainForm($domainID);
	
	echo page($content);
}

main();

?>