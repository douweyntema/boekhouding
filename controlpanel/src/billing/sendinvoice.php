<?php

require_once("common.php");

function sendMain()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader("Send invoice", adminCustomerBreadcrumbs($customerID), crumbs("Send invoice", "sendinvoice.php?id=" . $customerID)) . sendInvoiceForm($customerID, $error, $_POST)));
	};
	
	$invoiceLines = array();
	foreach(stdList("billingInvoiceLine", array("customerID"=>$customerID, "invoiceID"=>null), "invoiceLineID") as $invoiceLineID) {
		if(post("invoiceline-" . $invoiceLineID) !== null) {
			$invoiceLines[] = $invoiceLineID;
		}
	}
	$check(count($invoiceLines) > 0, "");
	$check(post("confirm") !== null, null);
	
	billingCreateInvoice($customerID, $invoiceLines, post("sendmail") !== null);
	
	redirect("billing/customer.php?id=$customerID");
}

function deleteMain()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader("Send invoice", adminCustomerBreadcrumbs($customerID), crumbs("Send invoice", "sendinvoice.php?id=" . $customerID)) . sendInvoiceForm($customerID, $error, $_POST)));
	};
	
	$invoiceLines = array();
	foreach(stdList("billingInvoiceLine", array("customerID"=>$customerID, "invoiceID"=>null), "invoiceLineID") as $invoiceLineID) {
		if(post("invoiceline-" . $invoiceLineID) !== null) {
			$invoiceLines[] = $invoiceLineID;
		}
	}
	$check(count($invoiceLines) > 0, "");
	$check(post("confirm") !== null, null);
	
	foreach($invoiceLines as $invoiceLineID) {
		stdDel("billingInvoiceLine", array("invoiceLineID"=>$invoiceLineID));
	}
	
	redirect("billing/customer.php?id=$customerID");
}

if(post("delete") !== null) {
	deleteMain();
} else {
	sendMain();
}

?>