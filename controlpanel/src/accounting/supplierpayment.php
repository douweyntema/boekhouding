<?php

require_once("common.php");

function main()
{
	$paymentID = get("id");
	
	doAccountingPayment($paymentID);
	
	$transactionID = stdGet("suppliersPayment", array("paymentID"=>$paymentID), "transactionID");
	$date = stdGet("accountingTransaction", array("transactionID"=>$transactionID), "date");
	$content = makeHeader("Payment on " . date("d-m-Y", $date), suppliersPaymentBreadcrumbs($paymentID));
	
	$content .= supplierPaymentSummary($paymentID);
	
	$content .= editSupplierPaymentForm($paymentID);
	$content .= deleteSupplierPaymentForm($paymentID);
	
	echo page($content);
}

main();

?>