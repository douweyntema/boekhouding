<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$filesystemID = get("id");
	$filesystemName = $GLOBALS["database"]->stdGet("infrastructureFilesystem", array("filesystemID"=>$filesystemID), "name");
	$filesystemNameHtml = htmlentities($filesystemName);
	
	$content = "<h1>Infrastructure - filesystem $filesystemNameHtml</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Infrastructure", "url"=>"{$GLOBALS["root"]}infrastructure/"),
		array("name"=>"$filesystemName", "url"=>"{$GLOBALS["root"]}infrastructure/filesystem.php?id=$filesystemID")
		));
	
	if(post("refresh") == "all") {
		refreshFilesystemMount($filesystemID);
		refreshFilesystemWebServer($filesystemID);
	} else if(post("refresh") == "filesystem") {
		refreshFilesystemMount($filesystemID);
	} else if(post("refresh") == "webserver") {
		refreshFilesystemWebServer($filesystemID);
	}
	
	$content .= filesystemDetail($filesystemID);
	$content .= filesystemHostList($filesystemID);
	$content .= filesystemCustomersList($filesystemID);
	$content .= filesystemRefresh($filesystemID);
	
	echo page($content);
}

main();

?>