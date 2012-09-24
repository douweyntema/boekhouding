<?php

require_once("common.php");

function main()
{
	doBilling();
	
	$content = makeHeader("Billing", customersBillingBreadcrumbs());
	$content .= customerSubscriptionList();
	$content .= customerInvoiceList(customerID());
	
	echo page($content);
}

main();

?>