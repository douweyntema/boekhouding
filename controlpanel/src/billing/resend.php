<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	$customerID = $GLOBALS["database"]->stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID, $invoiceID) {
		if(!$condition) die(page(makeHeader("Resend invoice", adminCustomerBreadcrumbs($customerID), crumbs("Resend invoice", "resend.php?id=" . $invoiceID)) . operationForm("resend.php?id=$invoiceID", $error, "Resend invoice", "Send", array(), $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	billingCreateInvoiceResend($invoiceID);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>