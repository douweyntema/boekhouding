<?php

require_once("common.php");

function main()
{
	$invoiceID = get("id");
	$customerID = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID, $invoiceID) {
		if(!$condition) die(page(makeHeader(_("Send reminder"), adminCustomerBreadcrumbs($customerID), crumbs(_("Send reminder"), "reminder.php?id=" . $invoiceID)) . operationForm("reminder.php?id=$invoiceID", $error, _("Send reminder"), _("Send"), array(), $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	billingCreateInvoiceEmail($invoiceID, true);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>