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

function billingCreateInvoice($customerID, $invoiceLines)
{
	$now = time();
	$factuurnrDatum = date("Ymd", $now);
	
	$GLOBALS["database"]->startTransaction();
	// TODO: atomair in de insert doen of locken
	$factuurnrCount = $GLOBALS["database"]->query("SELECT invoiceID FROM billingInvoice WHERE invoiceNumber LIKE '" . $GLOBALS["database"]->addSlashes($factuurnrDatum) . "-%'")->numRows();
	$factuurnr = $factuurnrDatum . "-" . ($factuurnrCount + 1);
	
	$amount = 0;
	foreach($invoiceLines as $lineID) {
		$line = $GLOBALS["database"]->stdGet("billingInvoiceLine", array("invoiceLineID"=>$lineID), array("customerID", "invoiceID", "price", "discount"));
		if($line["customerID"] != $customerID) {
			$GLOBALS["database"]->rollbackTransaction();
			throw new BillingInvoiceException();
		}
		if($line["invoiceID"] !== null) {
			$GLOBALS["database"]->rollbackTransaction();
			throw new BillingInvoiceException();
		}
		$amount += $line["price"];
		$amount -= $line["discount"];
	}
	
	$invoiceID = $GLOBALS["database"]->stdNew("billingInvoice", array("customerID"=>$customerID, "date"=>$now, "remainingAmount"=>$amount, "invoiceNumber"=>$factuurnr));
	
	// TODO: $amount overal bij op en af trekken
	
	foreach($invoiceLines as $lineID) {
		$GLOBALS["database"]->stdSet("billingInvoiceLine", array("invoiceLineID"=>$lineID), array("invoiceID"=>$invoiceID));
	}
	$GLOBALS["database"]->commitTransaction();
	
	billingCreateInvoiceTex($invoiceID);
}

function billingCreateInvoiceTex($invoiceID)
{
	$invoice = $GLOBALS["database"]->stdGet("billingInvoice", array("invoiceID"=>$invoiceID), array("customerID", "date", "invoiceNumber"));
	$customer = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$invoice["customerID"]), array("name", "companyName", "initials", "lastName", "address", "postalCode", "city", "countryCode"));
	
	$texdatum = date("d/m/Y", $invoice["date"]);
	$invoiceNumber = $invoice["invoiceNumber"];
	$to = "";
	if($customer["companyName"] !== null && $customer["companyName"] !== "") {
		$to .= $customer["companyName"] . "\\\\";
		$to .= "t.n.v ";
	}
	$to .= $customer["initials"] . " " . $customer["lastName"] . "\\\\";
	$to .= $customer["address"] . "\\\\";
	$to .= $customer["postalCode"] . "~~" . $customer["city"];
	if($customer["countryCode"] != "NL") {
		$to .= "\\\\" . countryName($customer["countryCode"]);
	}
	$username = $customer["name"];
	
	$posts = "";
	$discounts = "";
	
	foreach($GLOBALS["database"]->stdList("billingInvoiceLine", array("invoiceID"=>$invoiceID), array("description", "periodStart", "periodEnd", "price", "discount")) as $line) {
		if($line["periodStart"] != null && $line["periodEnd"] != null) {
			$startdate = texdate($line["periodStart"]);
			$enddate = texdate($line["periodEnd"]);
		} else {
			$startdate = "";
			$enddate = "";
		}
		$price = formatPriceRaw($line["price"] / 1.19);
		$posts .= "\\post{{$line["description"]}}{{$startdate}}{{$enddate}}{{$price}}\n";
		
		if($line["discount"] != 0) {
			$discountDescription = "Korting " . strtolower(substr($line["description"], 0, 1)) . substr($line["description"], 1);
			$discountamount = formatPriceRaw($line["discount"] / 1.19);
			$discounts .= "\\korting{{$discountDescription}}{{$discountamount}}\n";
		}
	}
	// TODO: BTW uitrekenen en aan de tex toevoegen
	$tex = <<<TEX
\documentclass{trevabrief}
\usepackage{treva-factuur}
\setDatum[{$texdatum}]

\begin{document}

\begin{factuurbrief}{{$to}}{{$invoiceNumber}}
\gebruikersnaam{{$username}}

\begin{factuur}
{$posts}{$discounts}
\end{factuur}

\end{factuurbrief}

\end{document}

TEX;
	$GLOBALS["database"]->stdSet("billingInvoice", array("invoiceID"=>$invoiceID), array("tex"=>$tex));
	
	billingCreateInvoicePdf($invoiceID);
}

function billingCreateInvoicePdf($invoiceID)
{
	$tex = $GLOBALS["database"]->stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "tex");
	$pdf = pdfLatex($tex);
	$GLOBALS["database"]->stdSet("billingInvoice", array("invoiceID"=>$invoiceID), array("pdf"=>$pdf));
}

billingCreateInvoiceTex(2);

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

class BillingInvoiceException extends Exception
{
}

?>