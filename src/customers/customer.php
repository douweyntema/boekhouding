<?php

$GLOBALS["endImpersonate"] = true;

require_once("common.php");

function main()
{
	$customerID = get("id");
	doCustomer($customerID);
	
	$customerName = stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	
	$content = makeHeader("Customers - $customerName", customerBreadcrumbs($customerID));
	$content .= customerBalance($customerID);
	$content .= editCustomerForm($customerID);
	echo page($content);
}

main();

?>