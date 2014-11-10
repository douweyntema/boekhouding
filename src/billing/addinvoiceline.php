<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader("Add invoice line", adminCustomerBreadcrumbs($customerID), crumbs("Add invoice line", "addinvoiceline.php?id=" . $customerID)) . addInvoiceLineForm($customerID, $error, $_POST)));
	};
	
	$check(post("price") !== null, "");
	$check(($price = parsePrice(post("price"))) !== null, "Invalid price");
	$check($price != 0, "Amount is zero");
	$check(($discount = parsePrice(post("discount"))) !== null, "Invalid discount");
	$check(($revenueAccountID = post("revenueAccountID")) !== "", "Invalid revenue account");
	$check(stdExists("accountingAccount", array("accountID"=>$revenueAccountID)), "Invalid revenue account");
	$check(post("confirm") !== null, null);
	
	billingAddSubscriptionLine($customerID, $revenueAccountID, post("description"), $price, $discount);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>