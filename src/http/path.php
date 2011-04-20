<?php

require_once("common.php");

function main()
{
	$pathID = $_GET["id"];
	doHttpPath($pathID);
	
	$content = "<h1>Web hosting - " . pathName($pathID) . "</h1>\n";
	
	$content .= pathBreadcrumbs($pathID);
	
	$content .= pathSummary($pathID);
	
	echo page($content);
}

main();

?>