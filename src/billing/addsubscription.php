<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader(_("Add subscription"), adminCustomerBreadcrumbs($customerID), crumbs(_("Add subscription"), "addsubscription.php?id=" . $customerID)) . addSubscriptionForm($customerID, $error, $_POST)));
	};
	
	$invoiceDelay = post("invoiceDelay") * 3600 * 24;
	
	$check(($price = parsePrice(post("price"))) !== null, _("Invalid price"));
	$check($price != 0, _("Amount is zero"));
	if(post("discountPercentage") == "" || post("discountPercentage") == "0") {
		$discountPercentage = null;
	} else {
		$discountPercentage = post("discountPercentage");
		$check(ctype_digit($discountPercentage), _("Invalid discount percentage"));
		$check($discountPercentage >= 0, _("Invalid discount percentage"));
		$check($discountPercentage <= 100, _("Invalid discount percentage"));
	}
	$check(($discountAmount = parsePrice(post("discountAmount"))) !== null, _("Invalid discount amount"));
	if($discountAmount == 0) {
		$discountAmount = null;
	}
	$check(ctype_digit(post("frequencyMultiplier")), _("Invalid frequency multiplier"));
	$check((post("frequencyBase") == "YEAR" || post("frequencyBase") == "MONTH" || post("frequencyBase") == "DAY"), _("Invalid frequency base"));
	$check(($nextPeriodStart = parseDate(post("nextPeriodStart"))) !== null, _("Invalid start date"));
	$check(is_int($invoiceDelay), _("Invalid invoice delay"));
	$check(($revenueAccountID = post("revenueAccountID")) !== "", _("Invalid revenue account"));
	$check(stdExists("accountingAccount", array("accountID"=>$revenueAccountID)), _("Invalid revenue account"));
	$check(post("confirm") !== null, null);
	
	billingNewSubscription($customerID, $revenueAccountID, post("description"), $price, $discountPercentage, $discountAmount, null, post("frequencyBase"), post("frequencyMultiplier"), $invoiceDelay, $nextPeriodStart);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>