<?php

$GLOBALS["loginAllowed"] = true;
$GLOBALS["endImpersonate"] = true;

require_once("common.php");

function main()
{
	doCustomers();
	
	$content = makeHeader("Customers", customersBreadcrumbs());
	$content .= customerList();
	$content .= addCustomerForm();
	echo page($content);
}

main();

?>