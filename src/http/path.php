<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	$domainID = $GLOBALS["database"]->stdGet("httpPath", array("pathID"=>$pathID), "domainID");
	
	$content = "<h1>Web hosting - " . pathName($pathID) . "</h1>\n";
	
	$content .= pathBreadcrumbs($pathID);
	
	$content .= pathSummary($pathID);
	
	$content .= editPathForm($domainID, $pathID, "STUB");
	
	$content .= addPathForm($pathID, "STUB");
	
	$content .= removePathForm($pathID);
	
	echo page($content);
}

main();

?>