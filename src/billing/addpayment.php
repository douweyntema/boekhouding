<?php

require_once("common.php");

function main()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader(_("Add payment"), adminCustomerBreadcrumbs($customerID), crumbs(_("Add payment"), "addpayment.php?id=" . $customerID)) . addPaymentForm($customerID, $error, $_POST)));
	};
	
	$check(post("amount") !== null, "");
	$check(($amount = parsePrice(post("amount"))) !== null, _("Invalid amount"));
	$check($amount != 0, "Amount is zero");
	$check(($date = parseDate(post("date"))) !== null, _("Invalid date"));
	$check(strlen(post("description")) < 255, _("Invalid description"));
	$check(($bankAccountID = post("bankAccountID")) !== "", _("Invalid bank account"));
	$check(stdExists("accountingAccount", array("accountID"=>$bankAccountID)), _("Invalid bank account"));
	
	$check(post("confirm") !== null, null);
	
	billingAddPayment($customerID, $bankAccountID, $amount, $date, post("description"));
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>