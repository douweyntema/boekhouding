<?php

require_once("common.php");

function main()
{
	doInfrastructure();
	
	$content = makeHeader("Infrastructure", infrastructureBreadcrumbs());
	$content .= fileSystemList();
	$content .= mailSystemList();
	$content .= nameSystemList();
	$content .= hostList();
	echo page($content);
}

main();

?>