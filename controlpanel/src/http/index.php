<?php

require_once("common.php");

function main()
{
	doHttp();
	
	$content = makeHeader("Web hosting", httpBreadcrumbs());
	$content .= domainsList();
	$content .= addDomainForm("STUB");
	echo page($content);
}

main();

?>