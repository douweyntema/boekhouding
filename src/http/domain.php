<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doHttpDomain($domainID);
	$pathID = domainPath($domainID);
	
	if(isStubDomain($domainID) && !isRootDomain($domainID)) {
		error404();
	}
	
	$content = makeHeader("Web hosting - " . httpDomainName($domainID), domainBreadcrumbs($domainID));
	$content .= domainSummary($domainID);
	$content .= editPathForm($pathID, "STUB");
	$content .= addSubdomainForm($domainID, "STUB");
	$content .= addPathForm($pathID, "STUB");
	$content .= removeDomainForm($domainID);
	echo page($content);
}

main();

?>