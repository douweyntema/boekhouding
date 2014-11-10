<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$nameSystemID = get("id");
	$nameSystemName = stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "name");
	
	if(post("refreshall") !== null || post("refreshbind") !== null) {
		infrastructureRefreshNameSystem($nameSystemID);
	}
	
	$content = makeHeader("Infrastructure - namesystem " . htmlentities($nameSystemName), infrastructureBreadcrumbs(), crumbs($nameSystemName, "namesystem.php?id=$nameSystemID"));
	$content .= nameSystemDetail($nameSystemID);
	$content .= nameSystemHostList($nameSystemID);
	$content .= nameSystemCustomersList($nameSystemID);
	$content .= nameSystemRefreshForm($nameSystemID);
	echo page($content);
}

main();

?>