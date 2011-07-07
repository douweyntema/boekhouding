<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$content = "<h1>Infrastructure</h1>\n";
	
	$content .= breadcrumbs(array(
		array("name"=>"Infrastructure", "url"=>"{$GLOBALS["root"]}infrastructure/")
		));
	
	$content .= filesystemList();
	$content .= hostList();
	
	echo page($content);
}

main();

?>