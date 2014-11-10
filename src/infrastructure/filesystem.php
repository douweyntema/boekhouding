<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$fileSystemID = get("id");
	$fileSystemName = stdGet("infrastructureFileSystem", array("fileSystemID"=>$fileSystemID), "name");
	
	if(post("refreshall") !== null || post("refreshmount") !== null) {
		infrastructureRefreshFileSystemMount($fileSystemID);
	}
	if(post("refreshall") !== null || post("refreshwebserver") !== null) {
		infrastructureRefreshFileSystemWebServer($fileSystemID);
	}
	
	$content = makeHeader("Infrastructure - filesystem " . htmlentities($fileSystemName), infrastructureBreadcrumbs(), crumbs($fileSystemName, "filesystem.php?id=$fileSystemID"));
	$content .= fileSystemDetail($fileSystemID);
	$content .= fileSystemHostList($fileSystemID);
	$content .= fileSystemCustomersList($fileSystemID);
	$content .= fileSystemRefreshForm($fileSystemID);
	echo page($content);
}

main();

?>