<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	$domainID = get("id");
	doDomain($domainID);
	
	$content = "<h1>Domain " . domainName($domainID) . "</h1>\n";
	
	$content .= domainDetail($domainID);
	
	echo page($content);
}

main();

?>