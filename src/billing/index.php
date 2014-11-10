<?php

require_once("common.php");

function main()
{
	doBilling();
	
	$content = makeHeader(_("Billing"), customersBillingBreadcrumbs());
	$content .= customerSubscriptionList();
	$content .= customerInvoiceList(customerID());
	
	echo page($content);
}

main();

?>