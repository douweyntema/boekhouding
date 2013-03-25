<?php

$billingTitle = "Billing";
$billingDescription = "Finances";
$billingTarget = "customer";

function billingDomainPrice($tldID)
{
	return stdGet("infrastructureDomainTld", array("domainTldID"=>$tldID), "price");
}

function billingBasePrice($subscription)
{
	if($subscription["price"] === null) {
		return $baseprice = billingDomainPrice($subscription["domainTldID"]);
	} else {
		return $baseprice = $subscription["price"];
	}
}

function billingNewSubscription($customerID, $description, $price, $discountPercentage, $discountAmount, $domainTldID, $frequencyBase, $frequencyMultiplier, $invoiceDelay, $startDate)
{
	if($startDate == null) {
		$startDate = time();
	}
	return stdNew("billingSubscription", array("customerID"=>$customerID, "domainTldID"=>$domainTldID, "description"=>$description, "price"=>$price, "discountPercentage"=>$discountPercentage, "discountAmount"=>$discountAmount, "frequencyBase"=>$frequencyBase, "frequencyMultiplier"=>$frequencyMultiplier, "invoiceDelay"=>$invoiceDelay, "nextPeriodStart"=>$startDate));
}

function billingEditSubscription($subscriptionID, $description, $price, $discountPercentage, $discountAmount, $frequencyBase, $frequencyMultiplier, $invoiceDelay)
{
	return stdSet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("description"=>$description, "price"=>$price, "discountPercentage"=>$discountPercentage, "discountAmount"=>$discountAmount, "frequencyBase"=>$frequencyBase, "frequencyMultiplier"=>$frequencyMultiplier, "invoiceDelay"=>$invoiceDelay));
}

function billingEndSubscription($subscriptionID, $endDate)
{
	stdSet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("endDate"=>$endDate));
}

function billingAddInvoiceLine($customerID, $description, $price, $discount)
{
	return stdNew("billingInvoiceLine", array("customerID"=>$customerID, "description"=>$description, "price"=>$price, "discount"=>$discount, "domain"=>0));
}

function billingUpdateInvoiceLines($customerID)
{
	if(stdGet("adminCustomer", array("customerID"=>$customerID), "invoiceStatus") == "DISABLED") {
		return;
	}
	$now = time();
	startTransaction();
	foreach(stdList("billingSubscription", array("customerID"=>$customerID), array("subscriptionID", "domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
		$isDomain = $subscription["domainTldID"] !== null;
		
		while($subscription["nextPeriodStart"] < $now + $subscription["invoiceDelay"] &&
			!($subscription["endDate"] !== null && $subscription["nextPeriodStart"] >= $subscription["endDate"]))
		{
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
			
			if($subscription["endDate"] !== null && $periodEnd > $subscription["endDate"]) {
				$fraction = ($subscription["endDate"] - $subscription["nextPeriodStart"]) / ($periodEnd - $subscription["nextPeriodStart"]);
				$price *= $fraction;
				$discount *= $fraction;
				$periodEnd = $subscription["endDate"];
			}
			
			stdNew("billingInvoiceLine", array("customerID"=>$customerID, "description"=>$subscription["description"], "periodStart"=>$subscription["nextPeriodStart"], "periodEnd"=>$periodEnd, "price"=>$price, "discount"=>$discount, "domain"=>$isDomain));
			
			stdSet("billingSubscription", array("subscriptionID"=>$subscription["subscriptionID"]), array("nextPeriodStart"=>$periodEnd));
			$subscription["nextPeriodStart"] = $periodEnd;
		}
		if($subscription["endDate"] !== null && $subscription["nextPeriodStart"] >= $subscription["endDate"]) {
			if($isDomain) {
				$domainID = stdGet("dnsDomain", array("subscriptionID"=>$subscription["subscriptionID"]), "domainID");
				if(domainsDomainStatus($domainID) == "expired" || domainsDomainStatus($domainID) == "quarantaine") {
					domainsRemoveDomain($domainID);
				} else {
					continue;
				}
			}
			stdDel("billingSubscription", array("subscriptionID"=>$subscription["subscriptionID"]));
		}
	}
	commitTransaction();
}

function billingUpdateAllInvoiceLines()
{
	foreach(stdList("adminCustomer", array(), "customerID") as $customerID) {
		billingUpdateInvoiceLines($customerID);
	}
}

function billingCreateInvoiceBatch($customerID)
{
	if(!isset($GLOBALS["controlpanelEnableCustomerEmail"]) || !$GLOBALS["controlpanelEnableCustomerEmail"]) {
		return;
	}
	if(stdGet("adminCustomer", array("customerID"=>$customerID), "invoiceStatus") !== 'ENABLED') {
		return;
	}
	billingUpdateInvoiceLines($customerID);
	
	$now = time();
	$invoiceTime = stdGet("adminCustomer", array("customerID"=>$customerID), array("invoiceFrequencyBase", "invoiceFrequencyMultiplier", "nextInvoiceDate"));
	
	if($invoiceTime["nextInvoiceDate"] > $now) {
		return;
	}
	$nextInvoiceTime = $invoiceTime["nextInvoiceDate"];
	while($nextInvoiceTime < $now) {
		$nextInvoiceTime = billingCalculateNextDate($nextInvoiceTime, $invoiceTime["invoiceFrequencyBase"], $invoiceTime["invoiceFrequencyMultiplier"]);
	}
	
	$invoiceLines = stdList("billingInvoiceLine", array("customerID"=>$customerID, "invoiceID"=>null), "invoiceLineID");
	if(count($invoiceLines) == 0) {
		return;
	}
	
	stdSet("adminCustomer", array("customerID"=>$customerID), array("nextInvoiceDate"=>$nextInvoiceTime));
	
	billingCreateInvoice($customerID, $invoiceLines);
}

function billingCreateAllInvoices()
{
	foreach(stdList("adminCustomer", array(), "customerID") as $customerID) {
		billingCreateInvoiceBatch($customerID);
	}
}

function billingCreateInvoice($customerID, $invoiceLines, $sendEmail = true)
{
	if(count($invoiceLines) == 0) {
		throw new BillingInvoiceException();
	}
	$now = time();
	$factuurnrDatum = date("Ymd", $now);
	
	startTransaction();
	stdLock("billingInvoice", array());
	$factuurnrCount = query("SELECT invoiceID FROM billingInvoice WHERE invoiceNumber LIKE '" . dbAddSlashes($factuurnrDatum) . "-%'")->numRows();
	$factuurnr = $factuurnrDatum . "-" . ($factuurnrCount + 1);
	
	$amount = 0;
	foreach($invoiceLines as $lineID) {
		$line = stdGet("billingInvoiceLine", array("invoiceLineID"=>$lineID), array("customerID", "invoiceID", "price", "discount"));
		if($line["customerID"] != $customerID) {
			rollbackTransaction();
			throw new BillingInvoiceException();
		}
		if($line["invoiceID"] !== null) {
			rollbackTransaction();
			throw new BillingInvoiceException();
		}
		$amount += $line["price"];
		$amount -= $line["discount"];
	}
	
	$invoiceID = stdNew("billingInvoice", array("customerID"=>$customerID, "date"=>$now, "remainingAmount"=>$amount, "invoiceNumber"=>$factuurnr));
	
	foreach($invoiceLines as $lineID) {
		stdSet("billingInvoiceLine", array("invoiceLineID"=>$lineID), array("invoiceID"=>$invoiceID));
	}
	commitTransaction();
	
	billingDistributeFunds($customerID);
	
	billingCreateInvoiceTex($invoiceID, $sendEmail);
}

function billingCreateInvoiceTex($invoiceID, $sendEmail = true)
{
	$invoice = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), array("customerID", "date", "invoiceNumber"));
	$customer = stdGet("adminCustomer", array("customerID"=>$invoice["customerID"]), array("name", "companyName", "initials", "lastName", "address", "postalCode", "city", "countryCode"));
	
	$texdatum = date("d/m/Y", $invoice["date"]);
	$invoiceNumber = $invoice["invoiceNumber"];
	$to = "";
	if($customer["companyName"] !== null && $customer["companyName"] !== "") {
		$to .= latexEscapeString($customer["companyName"]) . "\\\\";
		if($customer["initials"] . $customer["lastName"] !== "") {
			$to .= "t.n.v ";
			$to .= latexEscapeString($customer["initials"]) . " " . latexEscapeString($customer["lastName"]) . "\\\\";
		}
	} else {
		$to .= latexEscapeString($customer["initials"]) . " " . latexEscapeString($customer["lastName"]) . "\\\\";
	}
	$to .= latexEscapeString($customer["address"]) . "\\\\";
	$to .= latexEscapeString($customer["postalCode"]) . "~~" . latexEscapeString($customer["city"]);
	if($customer["countryCode"] != "NL") {
		$to .= "\\\\" . latexEscapeString(countryName($customer["countryCode"]));
	}
	$username = $customer["name"];
	$usernameTex = latexEscapeString($username);
	
	$posts = "";
	$discounts = "";
	$btw = 0;
	$creditatie = true;
	foreach(stdList("billingInvoiceLine", array("invoiceID"=>$invoiceID), array("description", "periodStart", "periodEnd", "price", "discount")) as $line) {
		if($line["periodStart"] != null && $line["periodEnd"] != null) {
			$startdate = texdate($line["periodStart"]);
			$enddate = texdate($line["periodEnd"] - 86400);
		} else {
			$startdate = "";
			$enddate = "";
		}
		if($line["price"] > 0) {
			$creditatie = false;
		}
		if($line["price"] != 0) {
			$price = (int)($line["price"] / 1.21);
			$btw += $line["price"] - $price;
			$priceFormat = formatPriceRaw($price);
			$desciptionTex = latexEscapeString($line["description"]);
			$posts .= "\\post{{$desciptionTex}}{{$startdate}}{{$enddate}}{{$priceFormat}}\n";
		}
		if($line["discount"] != 0) {
			$discountDescription = "Korting " . strtolower(substr($line["description"], 0, 1)) . substr($line["description"], 1);
			$discountDescriptionTex = latexEscapeString($discountDescription);
			$discountAmount = (int)($line["discount"] / 1.21);
			$btw -= $line["discount"] - $discountAmount;
			$discountamountFormat = formatPriceRaw($discountAmount);
			$discounts .= "\\korting{{$discountDescriptionTex}}{{$discountamountFormat}}\n";
		}
	}
	$btw = formatPriceRaw($btw);
	$brieftype = $creditatie ? "creditatiebrief" : "factuurbrief";
	$tex = <<<TEX
\documentclass{trevabrief}
\usepackage{treva-factuur}
\setDatum[{$texdatum}]

\begin{document}

\begin{{$brieftype}}{{$to}}{{$invoiceNumber}}
\gebruikersnaam{{$usernameTex}}

\begin{factuur}
{$posts}{$discounts}\btw{{$btw}}
\end{factuur}

\end{{$brieftype}}

\end{document}

TEX;
	stdSet("billingInvoice", array("invoiceID"=>$invoiceID), array("tex"=>$tex));
	
	billingCreateInvoicePdf($invoiceID, $sendEmail);
}

function billingCreateInvoicePdf($invoiceID, $sendEmail = true)
{
	$tex = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "tex");
	$pdf = pdfLatex($tex);
	stdSet("billingInvoice", array("invoiceID"=>$invoiceID), array("pdf"=>$pdf));
	if($pdf === null) {
		mailAdmin("Invoice pdf generation failed", "Failed to generate a pdf for invoiceID $invoiceID with tex:\n\n$tex");
		return;
	}
	if($sendEmail) {
		billingCreateInvoiceEmail($invoiceID);
	}
}

function billingCreateInvoiceEmail($invoiceID, $reminder=false)
{
	if(!isset($GLOBALS["controlpanelEnableCustomerEmail"]) || !$GLOBALS["controlpanelEnableCustomerEmail"]) {
		return;
	}
	$invoice = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), array("customerID", "remainingAmount", "invoiceNumber", "pdf"));
	if($invoice["pdf"] === null) {
		return;
	}
	$customer = stdGet("adminCustomer", array("customerID"=>$invoice["customerID"]), array("name", "companyName", "initials", "lastName", "email"));
	
	if($invoice["remainingAmount"] > 0) {
		$bedrag = formatPriceRaw($invoice["remainingAmount"]);
		$betalen = "Wij verzoeken u dit bedrag (€ $bedrag) binnen 30 dagen over te maken op rekeningnummer 3962370 t.n.v. Treva Technologies, onder vermelding van het factuurnummer ({$invoice["invoiceNumber"]}) en uw accountnaam ({$customer["name"]}).";
	} else {
		$betalen = "Deze factuur is reeds verrekend met eerdere betalingen.";
	}
	
	$mail = new mimemail();
	$mail->setCharset("utf-8");
	if($customer["companyName"] == null || $customer["companyName"] == "") {
		$mail->addReceiver($customer["email"], $customer["initials"] . " " . $customer["lastName"]);
	} else {
		$mail->addReceiver($customer["email"], $customer["companyName"]);
	}
	$mail->setSender("treva@treva.nl", "Treva Technologies");
	$mail->addBcc("klantfacturen@treva.nl");
	if($reminder) {
		$mail->setSubject("Herrinnering: factuur {$invoice["invoiceNumber"]}");
	} else {
		$mail->setSubject("Factuur {$invoice["invoiceNumber"]}");
	}
	$mail->addAttachment("factuur-{$invoice["invoiceNumber"]}.pdf", $invoice["pdf"], "application/pdf");
	
	if($reminder) {
		$inleiding = "Uit onze administratie blijkt dat deze rekening met factuurnummer {$invoice["invoiceNumber"]} nog niet is betaald. Wellicht is het aan uw aandacht ontsnapt. Mocht u reeds betaald hebben, dan kunt u deze herinnering als niet verzonden beschouwen.";
	} else {
		$inleiding = "In de bijlage van dit e-mail bericht vindt u de factuur met factuurnummer {$invoice["invoiceNumber"]} voor de afgenomen diensten/producten.";
	}
	
	$mail->setTextMessage(<<<TEXT
Geachte {$customer["initials"]} {$customer["lastName"]},

{$inleiding}

{$betalen}

Met vriendelijke groet,

Treva Technologies

TEXT
);
	
	$mail->send();
}

function billingCreateInvoiceResend($invoiceID)
{
	$invoice = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), array("tex", "pdf"));
	
	if($invoice["tex"] === null) {
		billingCreateInvoiceTex($invoiceID);
	} else if($invoice["pdf"] === null) {
		billingCreateInvoicePdf($invoiceID);
	} else {
		billingCreateInvoiceEmail($invoiceID);
	}
}

function billingCustomerBalance($customerID)
{
	return stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
}

function billingAddPayment($customerID, $amount, $date, $desciption)
{
	startTransaction();
	stdLock("adminCustomer", array("customerID"=>$customerID));
	stdNew("billingPayment", array("customerID"=>$customerID, "amount"=>$amount, "date"=>$date, "description"=>$desciption));
	$balance = stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
	stdSet("adminCustomer", array("customerID"=>$customerID), array("balance"=>$balance + $amount));
	commitTransaction();
	
	billingDistributeFunds($customerID);
}

function billingDistributeFunds($customerID)
{
	startTransaction();
	stdLock("adminCustomer", array("customerID"=>$customerID));
	stdLock("billingInvoice", array("customerID"=>$customerID));
	
	$balance = stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
	
	foreach(query("SELECT invoiceID, remainingAmount FROM billingInvoice WHERE customerID='" . dbAddSlashes($customerID) . "' AND remainingAmount > 0 ORDER BY date")->fetchMap("invoiceID", "remainingAmount") as $invoiceID=>$remainingAmount) {
		$change = min($balance, $remainingAmount);
		if($change > 0) {
			$remainingAmount -= $change;
			$balance -= $change;
			stdSet("billingInvoice", array("invoiceID"=>$invoiceID), array("remainingAmount"=>$remainingAmount));
		}
	}
	stdSet("adminCustomer", array("customerID"=>$customerID), array("balance"=>$balance));
	
	commitTransaction();
}

function billingBalance($customerID)
{
	billingDistributeFunds($customerID);
	$balance = stdGet("adminCustomer", array("customerID"=>$customerID), "balance");
	if($balance > 0) {
		return $balance;
	}
	$result = query("SELECT SUM(remainingAmount) AS sum FROM billingInvoice WHERE customerID='" . dbAddSlashes($customerID) . "'")->fetchArray();
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