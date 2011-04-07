<?php

require_once("common.php");

function main()
{
	$domainID = $_GET["id"];
	doHttpDomain($domainID);
	
	$content = "<h1>Webhosting - " . domainName($domainID) . "</h1>\n";
	
	$content .= virtualHostList($domainID);
	
	echo page($content);
}

main();

?>