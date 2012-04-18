<?php

require_once("common.php");

function main()
{
	$subscriptionID = get("id");
	$customerID = $GLOBALS["database"]->stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), "customerID");
	doBillingAdmin($customerID);
	
	$content = "<h1>Subscription</h1>\n";
	$content .= billingAdminCustomerBreadcrumbs($customerID, array(array("name"=>"Subscription", "url"=>"{$GLOBALS["root"]}billing/subscription.php?id=$subscriptionID")));
	
	$content .= subscriptionDetail($subscriptionID);
	$content .= editSubscriptionForm($subscriptionID);
	$content .= endSubscriptionForm($subscriptionID);
	
	echo page($content);
}

main();

?>