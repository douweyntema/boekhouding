<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader(_("Add invoice line"), adminCustomerBreadcrumbs($customerID), crumbs(_("Add invoice line"), "addinvoiceline.php?id=" . $customerID)) . addInvoiceLineForm($customerID, $error, $_POST)));
	};
	
	$check(post("price") !== null, "");
	$check(($price = parsePrice(post("price"))) !== null, _("Invalid price"));
	$check($price != 0, _("Amount is zero"));
	$check(($discount = parsePrice(post("discount"))) !== null, _("Invalid discount"));
	$check(($revenueAccountID = post("revenueAccountID")) !== "", _("Invalid revenue account"));
	$check(stdExists("accountingAccount", array("accountID"=>$revenueAccountID)), _("Invalid revenue account"));
	$check(post("confirm") !== null, null);
	
	billingAddSubscriptionLine($customerID, $revenueAccountID, post("description"), $price, $discount);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>