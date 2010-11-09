<?php

require_once("common.php");
doCustomers(null);

function main()
{
	$content = "<h1>Customers</h1>\n";
	
	$content .= customerList();
	
	echo page($content);
}

main();

?>