<?php

require_once("common.php");

function main()
{
	$subscriptionID = get("id");
	$customerID = stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), "customerID");
	doBillingAdmin($customerID);
	
	$content = makeHeader("Subscription", adminSubscriptionBreadcrumbs($subscriptionID));
	$content .= subscriptionDetail($subscriptionID);
	$content .= editSubscriptionForm($subscriptionID);
	$content .= endSubscriptionForm($subscriptionID);
	echo page($content);
}

main();

?>