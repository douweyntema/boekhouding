<?php

require_once("common.php");

function main()
{
	$domainID = get("id");
	doHttpDomain($domainID);
	$pathID = domainPath($domainID);
	
	if(isStubDomain($domainID)) {
		error404();
	}
	
	$content = makeHeader("Web hosting - " . domainName($domainID), domainBreadcrumbs($domainID));
	$content .= domainSummary($domainID);
	$content .= editPathForm($pathID, "STUB");
	$content .= addSubdomainForm($domainID, "STUB");
	$content .= addPathForm($pathID, "STUB");
	$content .= removeDomainForm($domainID);
	echo page($content);
}

main();

?>