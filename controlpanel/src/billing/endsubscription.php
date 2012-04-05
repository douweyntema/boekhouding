<?php

require_once("common.php");

function main()
{
	$subscriptionID = get("id");
	$customerID = $GLOBALS["database"]->stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID, $subscriptionID) {
		if(!$condition) die(page(addHeader($customerID, "End subscription", "endsubscription.php?id=" . $subscriptionID) . endSubscriptionForm($subscriptionID, $error, $_POST)));
	};
	
	$check(($endDate = parseDate(post("endDate"))) !== null, "Invalid end date");
	$check(post("confirm") !== null, null);
	
	billingEndSubscription($subscriptionID, $endDate);
	
	header("HTTP/1.1 303 See Other");
	header("Location: {$GLOBALS["root"]}billing/customer.php?id=$customerID");
}

main();

?>