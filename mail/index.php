<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	doMail();
	
	$content = "<h1>Email</h1>\n";
	
	$content .= mailDomainsList();
	
	echo page($content);
}

main();

?>