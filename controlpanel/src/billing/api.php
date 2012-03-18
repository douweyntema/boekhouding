<?php

$billingTitle = "Billing";
$billingDescription = "Billing";
$billingTarget = "customer";

function billingDomainPrice($tldID)
{
	return $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "price");
}

function billingNewSubscription($customerID, $description, $price, $discountPercentage, $discountAmount, $domainTldID, $frequencyBase, $frequencyMultiplier, $invoiceDelay, $startDate)
{
	if($startDate == null) {
		$startDate = time();
	}
	return $GLOBALS["database"]->stdNew("billingSubscription", array("customerID"=>$customerID, "domainTldID"=>$domainTldID, "description"=>$description, "price"=>$price, "discountPercentage"=>$discountPercentage, "discountAmount"=>$discountAmount, "frequencyBase"=>$frequencyBase, "frequencyMultiplier"=>$frequencyMultiplier, "invoiceDelay"=>$invoiceDelay, "nextPeriodStart"=>$startDate));
}

function billingEndSubscription($subscriptionID, $endDate)
{
	$GLOBALS["database"]->stdSet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("endDate"=>$endDate));
}

function billingUpdateInvoiceLines($customerID)
{
	$now = time();
	$GLOBALS["database"]->startTransaction();
	foreach($GLOBALS["database"]->stdList("billingSubscription", array("customerID"=>$customerID), array("subscriptionID", "domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
		while($subscription["nextPeriodStart"] < $now + $subscription["invoiceDelay"]) {
			$nextPeriodStart = new DateTime("@" . $subscription["nextPeriodStart"]);
			if($subscription["frequencyBase"] == "DAY") {
				$intervalKey = "D";
			} else if($subscription["frequencyBase"] == "MONTH") {
				$intervalKey = "M";
			} else if($subscription["frequencyBase"] == "YEAR") {
				$intervalKey = "Y";
			} else {
				mailAdmin("Controlpanel database broken!", "Unknown frequencyBase for subscription " . $subscription["subscriptionID"]);
				continue 2;
			}
			$interval = new DateInterval("P" . $subscription["frequencyMultiplier"] . $intervalKey);
			$periodEnd = $nextPeriodStart->add($interval)->getTimestamp();
			
			if($subscription["price"] === null) {
				if($subscription["domainTldID"] === null) {
					mailAdmin("Controlpanel database broken!", "Both price and domainTldID are NULL for subscription " . $subscription["subscriptionID"]);
					continue 2;
				} else {
					$price = billingDomainPrice($subscription["domainTldID"]);
				}
			} else {
				$price = $subscription["price"];
			}
			
			$discount = ($price * $subscription["discountPercentage"]) / 100 + $subscription["discountAmount"];
			
			$deleteSubscription = false;
			if($subscription["endDate"] !== null && $periodEnd > $subscription["endDate"]) {
				$fraction = ($subscription["endDate"] - $subscription["nextPeriodStart"]) / ($periodEnd - $subscription["nextPeriodStart"]);
				$price *= $fraction;
				$discount *= $fraction;
				$deleteSubscription = true;
				$periodEnd = $subscription["endDate"];
			}
			
			$isDomain = $subscription["domainTldID"] !== null;
			
			$GLOBALS["database"]->stdNew("billingInvoiceLine", array("customerID"=>$customerID, "description"=>$subscription["description"], "periodStart"=>$subscription["nextPeriodStart"], "periodEnd"=>$periodEnd, "price"=>$price, "discount"=>$discount, "domain"=>$isDomain));
			
			if($deleteSubscription) {
				$GLOBALS["database"]->stdDel("billingSubscription", array("subscriptionID"=>$subscription["subscriptionID"]));
				continue 2;
			} else {
				$GLOBALS["database"]->stdSet("billingSubscription", array("subscriptionID"=>$subscription["subscriptionID"]), array("nextPeriodStart"=>$periodEnd));
				$subscription["nextPeriodStart"] = $periodEnd;
			}
		}
	}
	$GLOBALS["database"]->commitTransaction();
}

function billingUpdateAllInvoiceLines()
{
	foreach($GLOBALS["database"]->stdList("adminCustomer", array(), "customerID") as $customerID) {
		billingUpdateInvoiceLines($customerID);
	}
}

function billingCustomerBalance($customerID)
{
	return $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
}

?>