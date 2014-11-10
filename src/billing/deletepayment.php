<?php

require_once("common.php");

function main()
{
	$paymentID = get("id");
	$customerID = stdGet("billingPayment", array("paymentID"=>$paymentID), "customerID");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($paymentID) {
		if(!$condition) die(page(makeHeader(_("Delete payment"), adminPaymentBreadcrumbs($paymentID), crumbs(_("Delete payment"), "deletepayment.php?id=" . $paymentID)) . deletePaymentForm($paymentID, $error, $_POST)));
	};
	
	$check(post("confirm") !== null, null);
	
	billingDeletePayment($paymentID);
	
	redirect("billing/customer.php?id=$customerID");
}

main();

?>