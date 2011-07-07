<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$hostID = get("id");
	$hostName = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "name");
	$hostNameHtml = htmlentities($hostName);
	
	$content = "<h1>Infrastructure - host $hostNameHtml</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Infrastructure", "url"=>"{$GLOBALS["root"]}infrastructure/"),
		array("name"=>"$hostName", "url"=>"{$GLOBALS["root"]}infrastructure/host.php?id=$hostID")
		));
	
	if(post("refresh") == "all") {
		refreshMount($hostID);
		refreshWebServer($hostID);
	} else if(post("refresh") == "filesystem") {
		refreshMount($hostID);
	} else if(post("refresh") == "webserver") {
		refreshWebServer($hostID);
	}
	
	$content .= hostDetail($hostID);
	$content .= hostFilesystemList($hostID);
	$content .= hostRefresh($hostID);
	
	echo page($content);
}

main();

?>