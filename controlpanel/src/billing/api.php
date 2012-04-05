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

function billingEditSubscription($subscriptionID, $description, $price, $discountPercentage, $discountAmount, $frequencyBase, $frequencyMultiplier, $invoiceDelay)
{
	if($startDate == null) {
		$startDate = time();
	}
	return $GLOBALS["database"]->stdSet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("description"=>$description, "price"=>$price, "discountPercentage"=>$discountPercentage, "discountAmount"=>$discountAmount, "frequencyBase"=>$frequencyBase, "frequencyMultiplier"=>$frequencyMultiplier, "invoiceDelay"=>$invoiceDelay));
}

function billingEndSubscription($subscriptionID, $endDate)
{
	$GLOBALS["database"]->stdSet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("endDate"=>$endDate));
}

function billingAddInvoiceLine($customerID, $description, $price, $discount)
{
	return $GLOBALS["database"]->stdNew("billingInvoiceLine", array("customerID"=>$customerID, "description"=>$description, "price"=>$price, "discount"=>$discount, "domain"=>0));
}

function billingUpdateInvoiceLines($customerID)
{
	$now = time();
	$GLOBALS["database"]->startTransaction();
	foreach($GLOBALS["database"]->stdList("billingSubscription", array("customerID"=>$customerID), array("subscriptionID", "domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
		while($subscription["nextPeriodStart"] < $now + $subscription["invoiceDelay"]) {
			$day = date("j", $subscription["nextPeriodStart"]);
			$month = date("n", $subscription["nextPeriodStart"]);
			$year = date("Y", $subscription["nextPeriodStart"]);
			if($subscription["frequencyBase"] == "DAY") {
				$day += $subscription["frequencyMultiplier"];
			} else if($subscription["frequencyBase"] == "MONTH") {
				$month += $subscription["frequencyMultiplier"];
			} else if($subscription["frequencyBase"] == "YEAR") {
				$year += $subscription["frequencyMultiplier"];
			} else {
				mailAdmin("Controlpanel database broken!", "Unknown frequencyBase for subscription " . $subscription["subscriptionID"]);
				continue 2;
			}
			$periodEnd = mktime(
				date("H", $subscription["nextPeriodStart"]),
				date("i", $subscription["nextPeriodStart"]),
				date("s", $subscription["nextPeriodStart"]),
				$month, $day, $year);
			
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

function billingAddPayment($customerID, $amount, $date, $desciption)
{
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdLock("adminCustomer", array("customerID"=>$customerID));
	$GLOBALS["database"]->stdLock("billingInvoice", array("customerID"=>$customerID));
	
	$GLOBALS["database"]->stdNew("billingPayment", array("customerID"=>$customerID, "amount"=>$amount, "date"=>$date, "description"=>$desciption));
	
	$balance = $amount + $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
	
	foreach($GLOBALS["database"]->query("SELECT invoiceID, remainingAmount FROM billingInvoice WHERE customerID='" . $GLOBALS["database"]->addSlashes($customerID) . "' AND remainingAmount > 0 ORDER BY date")->fetchMap("invoiceID", "remainingAmount") as $invoiceID=>$remainingAmount) {
		$change = min($balance, $remainingAmount);
		if($change > 0) {
			$remainingAmount -= $change;
			$balance -= $change;
			$GLOBALS["database"]->stdSet("billingInvoice", array("invoiceID"=>$invoiceID), array("remainingAmount"=>$remainingAmount));
		}
	}
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("balance"=>$balance));
	
	$GLOBALS["database"]->commitTransaction();
}

?>