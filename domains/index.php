<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	doDomains(null);
	
	$content = "<h1>Domains</h1>\n";
	
	$content .= addDomainsList();
	
	echo page($content);
}

main();

?>