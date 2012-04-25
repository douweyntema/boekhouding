<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$hostID = get("id");
	$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	
	if(post("refreshall") !== null || post("refreshmount") !== null) {
		refreshHostMount($hostID);
	}
	if(post("refreshall") !== null || post("refreshwebserver") !== null) {
		refreshHostWebServer($hostID);
	}
	if(post("refreshall") !== null || post("refreshdovecot") !== null) {
		refreshHostDovecot($hostID);
	}
	if(post("refreshall") !== null || post("refreshexim") !== null) {
		refreshHostExim($hostID);
	}
	if(post("refreshall") !== null || post("refreshbind") !== null) {
		refreshHostBind($hostID);
	}
	
	$content = makeHeader("Infrastructure - host " . htmlentities($hostname), infrastructureBreadcrumbs(), crumbs($hostname, "host.php?id=$hostID"));
	$content .= hostDetail($hostID);
	$content .= hostFileSystemList($hostID);
	$content .= hostMailSystemList($hostID);
	$content .= hostNameSystemList($hostID);
	$content .= hostRefreshForm($hostID);
	echo page($content);
}

main();

?>