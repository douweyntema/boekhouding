<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$nameSystemID = get("id");
	$nameSystemName = $GLOBALS["database"]->stdGet("infrastructureNameSystem", array("nameSystemID"=>$nameSystemID), "name");
	$nameSystemNameHtml = htmlentities($nameSystemName);
	
	$content = "<h1>Infrastructure - namesystem $nameSystemNameHtml</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Infrastructure", "url"=>"{$GLOBALS["root"]}infrastructure/"),
		array("name"=>"$nameSystemName", "url"=>"{$GLOBALS["root"]}infrastructure/namesystem.php?id=$nameSystemID")
		));
	
	if(post("refresh") == "bind" || post("refresh") == "all") {
		refreshNameSystem($nameSystemID);
	}
	
	$content .= nameSystemDetail($nameSystemID);
	$content .= nameSystemHostList($nameSystemID);
	$content .= nameSystemCustomersList($nameSystemID);
	$content .= nameSystemRefresh($nameSystemID);
	
	echo page($content);
}

main();

?>