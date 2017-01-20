<?php

require_once("common.php");

function sendMain()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader(_("Send invoice"), adminCustomerBreadcrumbs($customerID), crumbs(_("Send invoice"), "sendinvoice.php?id=" . $customerID)) . sendInvoiceForm($customerID, $error, $_POST)));
	};
	
	$subscriptionLines = array();
	foreach(stdList("billingSubscriptionLine", array("customerID"=>$customerID), "subscriptionLineID") as $subscriptionLineID) {
		if(post("subscriptionline-" . $subscriptionLineID) !== null) {
			$subscriptionLines[] = $subscriptionLineID;
		}
	}
	$check(count($subscriptionLines) > 0, "");
	$check(($date = parseDate(post("date"))) !== null, _("Invalid date."));
	$check(post("confirm") !== null, null);
	
	billingCreateInvoice($customerID, $subscriptionLines, post("sendmail") !== null, $date);
	
	redirect("billing/customer.php?id=$customerID");
}

function deleteMain()
{
	$customerID = get("id");
	doBillingAdmin($customerID);
	
	$check = function($condition, $error) use($customerID) {
		if(!$condition) die(page(makeHeader(_("Send invoice"), adminCustomerBreadcrumbs($customerID), crumbs(_("Send invoice"), "sendinvoice.php?id=" . $customerID)) . sendInvoiceForm($customerID, $error, $_POST)));
	};
	
	$subscriptionLines = array();
	foreach(stdList("billingSubscriptionLine", array("customerID"=>$customerID), "subscriptionLineID") as $subscriptionLineID) {
		if(post("subscriptionline-" . $subscriptionLineID) !== null) {
			$subscriptionLines[] = $subscriptionLineID;
		}
	}
	$check(count($subscriptionLines) > 0, "");
	$check(post("confirm") !== null, null);
	
	foreach($subscriptionLines as $subscriptionLineID) {
		stdDel("billingSubscriptionLine", array("subscriptionLineID"=>$subscriptionLineID));
	}
	
	redirect("billing/customer.php?id=$customerID");
}

if(post("delete") !== null) {
	deleteMain();
} else {
	sendMain();
}

?>