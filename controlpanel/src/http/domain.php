<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doHttpDomain($domainID);
	
	if(isStubDomain($domainID)) {
		error404();
	}
	
	$content = "<h1>Web hosting - " . domainName($domainID) . "</h1>\n";
	
	$content .= domainBreadcrumbs($domainID);
	
	$content .= domainSummary($domainID);
	
	$content .= editPathForm($domainID, null, "STUB");
	
	$content .= addSubdomainForm($domainID, "STUB");
	
	$content .= addPathForm(domainPath($domainID), "STUB");
	
	$content .= removeDomainForm($domainID);
	
	echo page($content);
}

main();

?>