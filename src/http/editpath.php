<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	$domainID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	$content = "<h1>Web hosting - " . domainName($domainID) . "</h1>\n";
	
	$content .= pathBreadcrumbs($pathID);
	
	$content .= editPathForm($domainID, $pathID, "");
	
	echo page($content);
}

main();

?>