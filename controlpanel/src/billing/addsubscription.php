<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader("Add subscription", adminCustomerBreadcrumbs($customerID), crumbs("Add subscription", "addsubscription.php?id=" . $customerID)) . addSubscriptionForm($customerID, $error, $_POST)));
	};
	
	$invoiceDelay = post("invoiceDelay") * 3600 * 24;
	
	$check(($price = parsePrice(post("price"))) !== null, "Invalid price");
	$check($price != 0, "Amount is zero");
	if(post("discountPercentage") == "" || post("discountPercentage") == "0") {
		$discountPercentage = null;
	} else {
		$discountPercentage = post("discountPercentage");
		$check(ctype_digit($discountPercentage), "Invalid discount percentage");
		$check($discountPercentage >= 0, "Invalid discount percentage");
		$check($discountPercentage <= 100, "Invalid discount percentage");
	}
	$check(($discountAmount = parsePrice(post("discountAmount"))) !== null, "Invalid discount amount");
	if($discountAmount == 0) {
		$discountAmount = null;
	}
	$check(ctype_digit(post("frequencyMultiplier")), "Invalid frequency multiplier");
	$check((post("frequencyBase") == "YEAR" || post("frequencyBase") == "MONTH" || post("frequencyBase") == "DAY"), "Invalid frequency base");
	$check(($nextPeriodStart = parseDate(post("nextPeriodStart"))) !== null, "Invalid start date");
	$check(is_int($invoiceDelay), "Invalid invoice delay");
	$check(post("confirm") !== null, null);
	
	billingNewSubscription($customerID, post("description"), $price, $discountPercentage, $discountAmount, null, post("frequencyBase"), post("frequencyMultiplier"), $invoiceDelay, $nextPeriodStart);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>