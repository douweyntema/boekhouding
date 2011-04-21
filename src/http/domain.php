<?php

require_once("common.php");

function main()
{
	$domainID = $_GET["id"];
	doHttpDomain($domainID);
	
	$content = "<h1>Web hosting - " . domainName($domainID) . "</h1>\n";
	
	$content .= domainBreadcrumbs($domainID);
	
	$content .= domainSummary($domainID);
	
	$content .= addSubdomainForm($domainID, "STUB");
	
	echo page($content);
}

main();

?>