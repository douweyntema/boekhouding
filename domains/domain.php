<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	$domainID = $_GET["id"];
	doDomains($domainID);
	
	$content = "<h1>Domain " . domainName($domainID) . "</h1>\n";
	
	$content .= addDomainDetails($domainID);
	
	echo page($content);
}

main();

?>