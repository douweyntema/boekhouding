<?php

require_once("common.php");

function main()
{
	doHttp();
	
	$content = "<h1>Web hosting</h1>\n";
	
	$content .= breadcrumbs(array(array("url"=>"", "name"=>"Web hosting")));
	
	$content .= domainsList();
	
	echo page($content);
}

main();

?>