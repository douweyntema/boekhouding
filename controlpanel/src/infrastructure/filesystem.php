<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$fileSystemID = get("id");
	$fileSystemName = $GLOBALS["database"]->stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	
	if(post("refresh") == "all") {
		refreshFileSystemMount($fileSystemID);
		refreshFileSystemWebServer($fileSystemID);
	} else if(post("refresh") == "fileSystem") {
		refreshFileSystemMount($fileSystemID);
	} else if(post("refresh") == "webserver") {
		refreshFileSystemWebServer($fileSystemID);
	}
	
	$content = makeHeader("Infrastructure - filesystem " . htmlentities($fileSystemName), infrastructureBreadcrumbs(), crumbs($fileSystemName, "filesystem.php?id=$fileSystemID"));
	$content .= fileSystemDetail($fileSystemID);
	$content .= fileSystemHostList($fileSystemID);
	$content .= fileSystemCustomersList($fileSystemID);
	$content .= fileSystemRefresh($fileSystemID);
	echo page($content);
}

main();

?>