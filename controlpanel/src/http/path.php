<?php

require_once("common.php");

function main()
{
	$pathID = get("id");
	doHttpPath($pathID);
	
	$path = stdGet("httpPath", array("pathID"=>$pathID), array("parentPathID", "domainID"));
	if($path["parentPathID"] === null) {
		redirect("http/domain.php?id={$path["domainID"]}");
	}
	
	$content = makeHeader("Web hosting - " . httpPathName($pathID), pathBreadcrumbs($pathID));
	$content .= pathSummary($pathID);
	$content .= editPathForm($pathID, "STUB");
	$content .= addPathForm($pathID, "STUB");
	$content .= removePathForm($pathID);
	echo page($content);
}

main();

?>