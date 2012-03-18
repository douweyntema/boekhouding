<?php

require_once("common.php");

function main()
{
	doBilling();
	
	$content = "<h1>Billing</h1>\n";
	$content .= billingBreadcrumbs();
	$content .= customerSubscriptionList();
	$content .= invoiceList(3);
	
	echo page($content);
}

main();

?>