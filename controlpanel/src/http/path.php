<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	
	$content = makeHeader("Web hosting - " . pathName($pathID), pathBreadcrumbs($pathID));
	$content .= pathSummary($pathID);
	$content .= editPathForm($pathID, "STUB");
	$content .= addPathForm($pathID, "STUB");
	$content .= removePathForm($pathID);
	echo page($content);
}

main();

?>