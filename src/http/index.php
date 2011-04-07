<?php

require_once("common.php");

function main()
{
	doHttp();
	
	$content = "<h1>Webhosting</h1>\n";
	
	$content .= domainsList();
	
	echo page($content);
}

main();

?>