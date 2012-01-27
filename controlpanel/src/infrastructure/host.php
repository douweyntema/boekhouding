<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$hostID = get("id");
	$hostname = $GLOBALS["database"]->stdGet("infrastructureHost", array("hostID"=>$hostID), "hostname");
	$hostnameHtml = htmlentities($hostname);
	
	$content = "<h1>Infrastructure - host $hostnameHtml</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Infrastructure", "url"=>"{$GLOBALS["root"]}infrastructure/"),
		array("name"=>"$hostname", "url"=>"{$GLOBALS["root"]}infrastructure/host.php?id=$hostID")
		));
	
	if(post("refresh") == "all") {
		refreshHostMount($hostID);
		refreshHostWebServer($hostID);
		refreshHostDovecot($hostID);
		refreshHostExim($hostID);
		refreshHostBind($hostID);
	} else if(post("refresh") == "fileSystem") {
		refreshHostMount($hostID);
	} else if(post("refresh") == "webserver") {
		refreshHostWebServer($hostID);
	} else if(post("refresh") == "dovecot") {
		refreshHostDovecot($hostID);
	} else if(post("refresh") == "exim") {
		refreshHostExim($hostID);
	} else if(post("refresh") == "bind") {
		refreshHostBind($hostID);
	}
	
	$content .= hostDetail($hostID);
	$content .= hostFileSystemList($hostID);
	$content .= hostMailSystemList($hostID);
	$content .= hostNameSystemList($hostID);
	$content .= hostRefresh($hostID);
	
	echo page($content);
}

main();

?>