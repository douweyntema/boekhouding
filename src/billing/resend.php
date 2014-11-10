<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	$customerID = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID, $invoiceID) {
		if(!$condition) die(page(makeHeader(_("Resend invoice"), adminCustomerBreadcrumbs($customerID), crumbs(_("Resend invoice"), "resend.php?id=" . $invoiceID)) . operationForm("resend.php?id=$invoiceID", $error, _("Resend invoice"), _("Send"), array(), $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	billingCreateInvoiceResend($invoiceID);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>