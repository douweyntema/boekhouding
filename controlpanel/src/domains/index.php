<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	doDomains();
	
	$content = "<h1>Domains</h1>\n";
	
	$content .= domainsList();
	$content .= addDomainForm();
	
	echo page($content);
}

main();

?>