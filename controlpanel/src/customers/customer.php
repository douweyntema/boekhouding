<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doCustomer($customerID);
	
	$customerName = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	
	$content = makeHeader("Customers - $customerName", customerBreadcrumbs($customerID));
	$content .= customerLogin($customerID);
	$content .= customerBalance($customerID);
	$content .= editCustomerForm($customerID);
	$content .= editCustomerRightsForm($customerID);
	echo page($content);
}

main();

?>