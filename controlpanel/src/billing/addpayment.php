<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin();
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(addHeader($customerID, "Add payment", "addpayment.php?id=" . $customerID) . addPaymentForm($customerID, $error, $_POST)));
	};
	
	$check(($amount = parsePrice(post("amount"))) !== null, "Invalid amount");
	$check($amount != 0, "Amount is zero");
	$check(($date = parseDate(post("date"))) !== null, "Invalid date");
	$check(strlen(post("description")) < 255, "Invalid description");
	$check(post("confirm") !== null, null);
	
	billingAddPayment($customerID, $amount, $date, post("description"));
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}billing/customer.php?id=$customerID");
}

main();

?>