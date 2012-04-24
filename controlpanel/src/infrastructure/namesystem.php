<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$nameSystemID = get("id");
	$nameSystemName = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "name");
	
	if(post("refresh") == "bind" || post("refresh") == "all") {
		refreshNameSystem($nameSystemID);
	}
	
	$content = makeHeader("Infrastructure - namesystem " . htmlentities($nameSystemName), infrastructureBreadcrumbs(), crumbs($nameSystemName, "namesystem.php?id=$nameSystemID"));
	$content .= nameSystemDetail($nameSystemID);
	$content .= nameSystemHostList($nameSystemID);
	$content .= nameSystemCustomersList($nameSystemID);
	$content .= nameSystemRefresh($nameSystemID);
	echo page($content);
}

main();

?>