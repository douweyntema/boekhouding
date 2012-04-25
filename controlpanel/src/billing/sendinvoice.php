<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader("Send invoice", adminCustomerBreadcrumbs($customerID), crumbs("Send invoice", "sendinvoice.php?id=" . $customerID)) . sendInvoiceForm($customerID, $error, $_POST)));
	};
	
	$invoiceLines = array();
	foreach($GLOBALS["database"]->stdList("billingInvoiceLine", array("customerID"=>$customerID, "invoiceID"=>null), "invoiceLineID") as $invoiceLineID) {
		if(post("invoiceline-" . $invoiceLineID) !== null) {
			$invoiceLines[] = $invoiceLineID;
		}
	}
	$check(count($invoiceLines) > 0, "");
	$check(post("confirm") !== null, null);
	
	billingCreateInvoice($customerID, $invoiceLines);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}billing/customer.php?id=$customerID");
}

main();

?>