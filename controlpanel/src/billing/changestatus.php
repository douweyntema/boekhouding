<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader("Change invoice status", adminCustomerBreadcrumbs($customerID), crumbs("Change invoice status", "changestatus.php?id=" . $customerID)) . invoiceStatusForm($customerID, $error, $_POST)));
	};
	
	$check(post("invoiceStatus") == "DISABLED" || post("invoiceStatus") == "PREVIEW" || post("invoiceStatus") == "ENABLED", "");
	$check(post("confirm") !== null, null);
	
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("invoiceStatus"=>post("invoiceStatus")));
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>