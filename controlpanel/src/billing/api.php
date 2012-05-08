<?php

$billingTitle = "Billing";
$billingDescription = "Finances";
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
	if($GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "invoicesEnabled") === 0) {
		return;
	}
	$now = time();
	$GLOBALS["database"]->startTransaction();
	foreach($GLOBALS["database"]->stdList("billingSubscription", array("customerID"=>$customerID), array("subscriptionID", "domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
		while($subscription["nextPeriodStart"] < $now + $subscription["invoiceDelay"]) {
			$periodEnd = billingCalculateNextDate($subscription["nextPeriodStart"], $subscription["frequencyBase"], $subscription["frequencyMultiplier"]);
			
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

function billingCreateInvoiceBatch($customerID)
{
	if(!isset($GLOBALS["controlpanelEnableCustomerEmail"]) || !$GLOBALS["controlpanelEnableCustomerEmail"]) {
		return;
	}
	if($GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "invoicesEnabled") === 0) {
		return;
	}
	billingUpdateInvoiceLines($customerID);
	
	$now = time();
	$invoiceTime = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), array("invoiceFrequencyBase", "invoiceFrequencyMultiplier", "nextInvoiceDate"));
	
	if($invoiceTime["nextInvoiceDate"] > $now) {
		return;
	}
	$nextInvoiceTime = $invoiceTime["nextInvoiceDate"];
	while($nextInvoiceTime < $now) {
		$nextInvoiceTime = billingCalculateNextDate($nextInvoiceTime, $invoiceTime["invoiceFrequencyBase"], $invoiceTime["invoiceFrequencyMultiplier"]);
	}
	
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("nextInvoiceDate"=>$nextInvoiceTime));
	
	$invoiceLines = $GLOBALS["database"]->stdList("billingInvoiceLine", array("customerID"=>$customerID, "invoiceID"=>null), "invoiceLineID");
	if(count($invoiceLines) == 0) {
		return;
	}
	billingCreateInvoice($customerID, $invoiceLines);
}

function billingCreateAllInvoices()
{
	foreach($GLOBALS["database"]->stdList("adminCustomer", array(), "customerID") as $customerID) {
		billingCreateInvoiceBatch($customerID);
	}
}

function billingCreateInvoice($customerID, $invoiceLines)
{
	if(count($invoiceLines) == 0) {
		throw new BillingInvoiceException();
	}
	$now = time();
	$factuurnrDatum = date("Ymd", $now);
	
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdLock("billingInvoice", array());
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
	
	foreach($invoiceLines as $lineID) {
		$GLOBALS["database"]->stdSet("billingInvoiceLine", array("invoiceLineID"=>$lineID), array("invoiceID"=>$invoiceID));
	}
	$GLOBALS["database"]->commitTransaction();
	
	billingDistributeFunds($customerID);
	
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
		if($customer["initials"] . $customer["lastName"] !== "") {
			$to .= "t.n.v ";
			$to .= $customer["initials"] . " " . $customer["lastName"] . "\\\\";
		}
	} else {
		$to .= $customer["initials"] . " " . $customer["lastName"] . "\\\\";
	}
	$to .= $customer["address"] . "\\\\";
	$to .= $customer["postalCode"] . "~~" . $customer["city"];
	if($customer["countryCode"] != "NL") {
		$to .= "\\\\" . countryName($customer["countryCode"]);
	}
	$username = $customer["name"];
	
	$posts = "";
	$discounts = "";
	$btw = 0;
	foreach($GLOBALS["database"]->stdList("billingInvoiceLine", array("invoiceID"=>$invoiceID), array("description", "periodStart", "periodEnd", "price", "discount")) as $line) {
		if($line["periodStart"] != null && $line["periodEnd"] != null) {
			$startdate = texdate($line["periodStart"]);
			$enddate = texdate($line["periodEnd"] - 86400);
		} else {
			$startdate = "";
			$enddate = "";
		}
		if($line["price"] != 0) {
			$price = (int)($line["price"] / 1.19);
			$btw += $line["price"] - $price;
			$priceFormat = formatPriceRaw($price);
			$posts .= "\\post{{$line["description"]}}{{$startdate}}{{$enddate}}{{$priceFormat}}\n";
		}
		if($line["discount"] != 0) {
			$discountDescription = "Korting " . strtolower(substr($line["description"], 0, 1)) . substr($line["description"], 1);
			$discountAmount = (int)($line["discount"] / 1.19);
			$btw -= $line["discount"] - $discountAmount;
			$discountamountFormat = formatPriceRaw($discountAmount);
			$discounts .= "\\korting{{$discountDescription}}{{$discountamountFormat}}\n";
		}
	}
	$btw = formatPriceRaw($btw);
	$tex = <<<TEX
\documentclass{trevabrief}
\usepackage{treva-factuur}
\setDatum[{$texdatum}]

\begin{document}

\begin{factuurbrief}{{$to}}{{$invoiceNumber}}
\gebruikersnaam{{$username}}

\begin{factuur}
{$posts}{$discounts}\btw{{$btw}}
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
	billingCreateInvoiceEmail($invoiceID);
}

function billingCreateInvoiceEmail($invoiceID)
{
	$invoice = $GLOBALS["database"]->stdGet("billingInvoice", array("invoiceID"=>$invoiceID), array("customerID", "remainingAmount", "invoiceNumber", "pdf"));
	$customer = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$invoice["customerID"]), array("name", "companyName", "initials", "lastName", "email"));
	
	if($invoice["remainingAmount"] > 0) {
		$bedrag = formatPriceRaw($invoice["remainingAmount"]);
		$betalen = "Wij verzoeken u dit bedrag ($bedrag) binnen 30 dagen over te maken op rekeningnummer 3962370 t.n.v. Treva Technologies, onder vermelding van het factuurnummer ({$invoice["invoiceNumber"]}) en uw accountnaam ({$customer["name"]}).";
	} else {
		$betalen = "Deze factuur is reeds verrekend met eerdere betalingen.";
	}
	
	$mail = new mimemail();
	if($customer["companyName"] == null || $customer["companyName"] == "") {
		$mail->addReceiver($customer["email"], $customer["initials"] . " " . $customer["lastName"]);
	} else {
		$mail->addReceiver($customer["email"], $customer["companyName"]);
	}
	$mail->setSender("treva@treva.nl", "Treva Technologies");
	$mail->addBcc("klantfacturen@treva.nl");
	$mail->setSubject("Factuur {$invoice["invoiceNumber"]}");
	$mail->addAttachment("factuur-{$invoice["invoiceNumber"]}.pdf", $invoice["pdf"], "application/pdf");
	
	$mail->setTextMessage(<<<TEXT
Geachte {$customer["initials"]} {$customer["lastName"]},

In de bijlage van dit e-mail bericht vindt u de factuur met factuurnummer {$invoice["invoiceNumber"]} voor de afgenomen diensten/producten.

{$betalen}

Met vriendelijke groet,

Treva Technologies

TEXT
);
	
	$mail->send();
}

function billingCustomerBalance($customerID)
{
	return $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
}

function billingAddPayment($customerID, $amount, $date, $desciption)
{
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdLock("adminCustomer", array("customerID"=>$customerID));
	$GLOBALS["database"]->stdNew("billingPayment", array("customerID"=>$customerID, "amount"=>$amount, "date"=>$date, "description"=>$desciption));
	$balance = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
	$GLOBALS["database"]->stdSet("adminCustomer", array("customerID"=>$customerID), array("balance"=>$balance + $amount));
	$GLOBALS["database"]->commitTransaction();
	
	billingDistributeFunds($customerID);
}

function billingDistributeFunds($customerID)
{
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdLock("adminCustomer", array("customerID"=>$customerID));
	$GLOBALS["database"]->stdLock("billingInvoice", array("customerID"=>$customerID));
	
	$balance = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
	
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

function billingBalance($customerID)
{
	billingDistributeFunds($customerID);
	$balance = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
	if($balance > 0) {
		return $balance;
	}
	$result = $GLOBALS["database"]->query("SELECT SUM(remainingAmount) AS sum FROM billingInvoice WHERE customerID='" . $GLOBALS["database"]->addSlashes($customerID) . "'")->fetchArray();
	return -$result["sum"];
}

function billingCalculateNextDate($oldDate, $frequencyBase, $frequencyMultiplier)
{
	$day = date("j", $oldDate);
	$month = date("n", $oldDate);
	$year = date("Y", $oldDate);
	if($frequencyBase == "DAY") {
		$day += $frequencyMultiplier;
	} else if($frequencyBase == "MONTH") {
		$month += $frequencyMultiplier;
	} else if($frequencyBase == "YEAR") {
		$year += $frequencyMultiplier;
	} else {
		throw new BillingCalculateNextDateException();
		return;
	}
	$nextInvoiceTime = mktime(
		date("H", $oldDate),
		date("i", $oldDate),
		date("s", $oldDate),
		$month, $day, $year);
	return $nextInvoiceTime;
}

class BillingInvoiceException extends Exception {}
class BillingCalculateNextDateException extends Exception {}

?>