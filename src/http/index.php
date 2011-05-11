<?php

require_once("common.php");

function main()
{
	doHttp();
	
	$content = "<h1>Web hosting</h1>\n";
	
	$content .= breadcrumbs(array(array("url"=>"{$GLOBALS["root"]}http/", "name"=>"Web hosting")));
	
	$content .= domainsList();
	
	$content .= addDomainForm("STUB");
	
	echo page($content);
}

main();

?>