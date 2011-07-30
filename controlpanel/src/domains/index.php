<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	doDomains();
	
	$content = "<h1>Domains</h1>\n";
	
	$content .= domainsList();
	
	echo page($content);
}

main();

?>