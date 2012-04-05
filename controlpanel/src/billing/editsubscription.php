<?php

require_once("common.php");

function main()
{
	$subscriptionID = get("id");
	$customerID = $GLOBALS["database"]->stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID, $subscriptionID) {
		if(!$condition) die(page(addHeader($customerID, "Edit subscription", "editsubscription.php?id=" . $subscriptionID) . editSubscriptionForm($subscriptionID, $error, $_POST)));
	};
	
	$invoiceDelay = post("invoiceDelay") * 3600 * 24;
	
	$check(($price = parsePrice(post("price"))) !== null, "Invalid price");
	$check($price != 0, "Amount is zero");
	$check(ctype_digit(post("discountPercentage")), "Invalid discount percentage");
	$check(post("discountPercentage") >= 0, "Invalid discount percentage");
	$check(post("discountPercentage") <= 100, "Invalid discount percentage");
	$check(($discountAmount = parsePrice(post("discountAmount"))) !== null, "Invalid discount amount");
	$check(ctype_digit(post("frequencyMultiplier")), "Invalid frequency multiplier");
	$check((post("frequencyBase") == "YEAR" || post("frequencyBase") == "MONTH" || post("frequencyBase") == "DAY"), "Invalid frequency base");
	$check(is_int($invoiceDelay), "Invalid invoice delay");
	$check(post("confirm") !== null, null);
	
	billingEditSubscription($subscriptionID, post("description"), $price, post("discountPercentage"), $discountAmount, post("frequencyBase"), post("frequencyMultiplier"), $invoiceDelay);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}billing/customer.php?id=$customerID");
}

main();

?>