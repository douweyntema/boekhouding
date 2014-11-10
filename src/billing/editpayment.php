<?php

require_once("common.php");

function main()
{
	$paymentID = get("id");
	$customerID = stdGet("billingPayment", array("paymentID"=>$paymentID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($paymentID) {
		if(!$condition) die(page(makeHeader(_("Edit payment"), adminPaymentBreadcrumbs($paymentID), crumbs(_("Edit payment"), "editpayment.php?id=" . $paymentID)) . editPaymentForm($paymentID, $error, $_POST)));
	};
	
	$check(post("amount") !== null, "");
	$check(($amount = parsePrice(post("amount"))) !== null, _("Invalid amount"));
	$check($amount != 0, _("Amount is zero"));
	$check(($date = parseDate(post("date"))) !== null, _("Invalid date"));
	$check(strlen(post("description")) < 255, _("Invalid description"));
	$check(($bankAccountID = post("bankAccountID")) !== "", _("Invalid bank account"));
	$check(stdExists("accountingAccount", array("accountID"=>$bankAccountID)), _("Invalid bank account"));
	
	$check(post("confirm") !== null, null);
	
	billingEditPayment($paymentID, $bankAccountID, $amount, $date, post("description"));
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>