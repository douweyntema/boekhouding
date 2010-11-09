<?php

require_once(dirname(__FILE__) . "/common.php");

function main()
{
	doMail(null);
	
	$content = "<h1>Email</h1>\n";
	
// 	$content .= addDomainsList();
	
	echo page($content);
}

main();

?>