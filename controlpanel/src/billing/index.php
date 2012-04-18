<?php

require_once("common.php");

function main()
{
	doBilling();
	
	$content = "<h1>Billing</h1>\n";
	$content .= billingCustomerBreadcrumbs();
	$content .= customerSubscriptionList();
	$content .= invoiceList(customerID());
	
	echo page($content);
}

main();

?>