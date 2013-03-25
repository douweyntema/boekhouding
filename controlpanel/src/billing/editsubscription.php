<?php

require_once("common.php");

function main()
{
	$subscriptionID = get("id");
	$customerID = stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID, $subscriptionID) {
		if(!$condition) die(page(makeHeader("Edit subscription", adminSubscriptionBreadcrumbs($subscriptionID), crumbs("Edit subscription", "editsubscription.php?id=" . $subscriptionID)) . editSubscriptionForm($subscriptionID, $error, $_POST)));
	};
	
	$invoiceDelay = post("invoiceDelay") * 3600 * 24;
	
	if(post("priceType") == "domain" && stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), "domainTldID") !== null) {
		$price = null;
	} else {
		$check(($price = parsePrice(post("price"))) !== null, "Invalid price");
		$check($price != 0, "Amount is zero");
	}
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
	$check(is_int($invoiceDelay), "Invalid invoice delay");
	$check(post("confirm") !== null, null);
	
	billingEditSubscription($subscriptionID, post("description"), $price, $discountPercentage, $discountAmount, post("frequencyBase"), post("frequencyMultiplier"), $invoiceDelay);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>