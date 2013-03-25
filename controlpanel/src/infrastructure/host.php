<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$hostID = get("id");
	$hostname = stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	
	if(post("refreshall") !== null || post("refreshmount") !== null) {
		infrastructureRefreshHostMount($hostID);
	}
	if(post("refreshall") !== null || post("refreshwebserver") !== null) {
		infrastructureRefreshHostWebServer($hostID);
	}
	if(post("refreshall") !== null || post("refreshdovecot") !== null) {
		infrastructureRefreshHostDovecot($hostID);
	}
	if(post("refreshall") !== null || post("refreshexim") !== null) {
		infrastructureRefreshHostExim($hostID);
	}
	if(post("refreshall") !== null || post("refreshbind") !== null) {
		infrastructureRefreshHostBind($hostID);
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