<?php

require_once("common.php");

function main()
{
	$paymentID = get("id");
	$customerID = stdGet("billingPayment", array("paymentID"=>$paymentID), "customerID");
	doBillingAdmin($customerID);
	
	$content = makeHeader("Payment", adminPaymentBreadcrumbs($paymentID));
	$content .= paymentSummary($paymentID);
	$content .= editPaymentForm($paymentID);
	$content .= deletePaymentForm($paymentID);
	echo page($content);
}

main();

?>