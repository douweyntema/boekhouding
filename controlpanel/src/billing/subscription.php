<?php

require_once("common.php");

function main()
{
	$subscriptionID = get("id");
	doBillingAdmin();
	
	$content = "<h1>Subscription</h1>\n";
	$content .= billingBreadcrumbs(array(array("name"=>"Subscription", "url"=>"{$GLOBALS["root"]}billing/subscription.php?id=$subscriptionID")));
	
	$content .= subscriptionDetail($subscriptionID);
	$content .= editSubscriptionForm($subscriptionID);
	$content .= removeSubscriptionForm($subscriptionID);
	
	echo page($content);
}

main();

?>