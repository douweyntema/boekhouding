<?php

require_once("common.php");

function main()
{
	doMail();
	
	$content = makeHeader("Email", mailBreadcrumbs());
	$content .= mailDomainsList();
	$content .= addMailDomainForm();
	echo page($content);
}

main();

?>