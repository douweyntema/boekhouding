<?php

require_once("common.php");

function main()
{
	$subscriptionID = get("id");
	$customerID = stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID, $subscriptionID) {
		if(!$condition) die(page(makeHeader("End subscription", adminSubscriptionBreadcrumbs($subscriptionID), crumbs("End subscription", "endsubscription.php?id=" . $subscriptionID)) . endSubscriptionForm($subscriptionID, $error, $_POST)));
	};
	
	$check(($endDate = parseDate(post("endDate"))) !== null, "Invalid end date");
	$check(post("confirm") !== null, null);
	
	billingEndSubscription($subscriptionID, $endDate);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>