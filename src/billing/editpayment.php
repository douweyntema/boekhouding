<?php

require_once("common.php");

function main()
{
	$paymentID = get("id");
	$customerID = stdGet("billingPayment", array("paymentID"=>$paymentID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($paymentID) {
		if(!$condition) die(page(makeHeader("Edit payment", adminPaymentBreadcrumbs($paymentID), crumbs("Edit payment", "editpayment.php?id=" . $paymentID)) . editPaymentForm($paymentID, $error, $_POST)));
	};
	
	$check(post("amount") !== null, "");
	$check(($amount = parsePrice(post("amount"))) !== null, "Invalid amount");
	$check($amount != 0, "Amount is zero");
	$check(($date = parseDate(post("date"))) !== null, "Invalid date");
	$check(strlen(post("description")) < 255, "Invalid description");
	$check(($bankAccountID = post("bankAccountID")) !== "", "Invalid bank account");
	$check(stdExists("accountingAccount", array("accountID"=>$bankAccountID)), "Invalid bank account");
	
	$check(post("confirm") !== null, null);
	
	billingEditPayment($paymentID, $bankAccountID, $amount, $date, post("description"));
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>