<?php

require_once("common.php");

function main()
{
	doDomains();
	
	$content = makeHeader("Domains", domainsBreadcrumbs());
	$content .= domainsList();
	if(canAccessComponent("billing")) {
		$content .= addDomainForm();
	}
	echo page($content);
}

main();

?>