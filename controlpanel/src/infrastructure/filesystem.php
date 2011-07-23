<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$fileSystemID = get("id");
	$fileSystemName = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	$fileSystemNameHtml = htmlentities($fileSystemName);
	
	$content = "<h1>Infrastructure - filesystem $fileSystemNameHtml</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Infrastructure", "url"=>"{$GLOBALS["root"]}infrastructure/"),
		array("name"=>"$fileSystemName", "url"=>"{$GLOBALS["root"]}infrastructure/filesystem.php?id=$fileSystemID")
		));
	
	if(post("refresh") == "all") {
		refreshFileSystemMount($fileSystemID);
		refreshFileSystemWebServer($fileSystemID);
	} else if(post("refresh") == "fileSystem") {
		refreshFileSystemMount($fileSystemID);
	} else if(post("refresh") == "webserver") {
		refreshFileSystemWebServer($fileSystemID);
	}
	
	$content .= fileSystemDetail($fileSystemID);
	$content .= fileSystemHostList($fileSystemID);
	$content .= fileSystemCustomersList($fileSystemID);
	$content .= fileSystemRefresh($fileSystemID);
	
	echo page($content);
}

main();

?>