<?php

require_once("common.php");

function main()
{
	doMail();
	
	$content = "<h1>Email</h1>\n";
	$content .= mailBreadcrumbs();
	$content .= mailDomainsList();
	$content .= addMailDomainForm();
	
	echo page($content);
}

main();

?>