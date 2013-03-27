<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	billingUpdateSubscriptionLines($customerID);
	
	$content = makeHeader("Billing", adminCustomerBreadcrumbs($customerID));
	$content .= invoiceStatusForm($customerID);
	$content .= subscriptionList($customerID);
	$content .= invoiceList($customerID);
	$content .= paymentList($customerID);
	$content .= addPaymentForm($customerID);
	$content .= addSubscriptionForm($customerID);
	$content .= addInvoiceLineForm($customerID);
	$content .= sendInvoiceForm($customerID);
	
	echo page($content);
}

main();

?>