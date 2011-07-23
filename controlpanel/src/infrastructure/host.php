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
		refreshHostMount($hostID);
		refreshHostWebServer($hostID);
		refreshHostDovecot($hostID);
		refreshHostExim($hostID);
	} else if(post("refresh") == "fileSystem") {
		refreshHostMount($hostID);
	} else if(post("refresh") == "webserver") {
		refreshHostWebServer($hostID);
	} else if(post("refresh") == "dovecot") {
		refreshHostDovecot($hostID);
	} else if(post("refresh") == "exim") {
		refreshHostExim($hostID);
	}
	
	$content .= hostDetail($hostID);
	$content .= hostFileSystemList($hostID);
	$content .= hostMailSystemList($hostID);
	$content .= hostRefresh($hostID);
	
	echo page($content);
}

main();

?>