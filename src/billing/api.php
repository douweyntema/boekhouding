<?php

$billingTitle = "Billing";
$billingDescription = "Finances";
$billingTarget = "both";
$billingMenu = "customer";

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

function billingNewSubscription($customerID, $revenueAccountID, $description, $price, $discountPercentage, $discountAmount, $domainTldID, $frequencyBase, $frequencyMultiplier, $invoiceDelay, $startDate)
{
	if($startDate == null) {
		$startDate = time();
	}
	return stdNew("billingSubscription", array("customerID"=>$customerID, "revenueAccountID"=>$revenueAccountID, "domainTldID"=>$domainTldID, "description"=>$description, "price"=>$price, "discountPercentage"=>$discountPercentage, "discountAmount"=>$discountAmount, "frequencyBase"=>$frequencyBase, "frequencyMultiplier"=>$frequencyMultiplier, "invoiceDelay"=>$invoiceDelay, "nextPeriodStart"=>$startDate));
}

function billingEditSubscription($subscriptionID, $revenueAccountID, $description, $price, $discountPercentage, $discountAmount, $frequencyBase, $frequencyMultiplier, $invoiceDelay)
{
	return stdSet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("revenueAccountID"=>$revenueAccountID, "description"=>$description, "price"=>$price, "discountPercentage"=>$discountPercentage, "discountAmount"=>$discountAmount, "frequencyBase"=>$frequencyBase, "frequencyMultiplier"=>$frequencyMultiplier, "invoiceDelay"=>$invoiceDelay));
}

function billingEndSubscription($subscriptionID, $endDate)
{
	stdSet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("endDate"=>$endDate));
}

function billingAddSubscriptionLine($customerID, $revenueAccountID, $description, $price, $discount)
{
	return stdNew("billingSubscriptionLine", array("customerID"=>$customerID, "revenueAccountID"=>$revenueAccountID, "description"=>$description, "price"=>$price, "discount"=>$discount));
}

function billingUpdateSubscriptionLines($customerID)
{
	$status = stdGet("adminCustomer", array("customerID"=>$customerID), "invoiceStatus");
	
	if($status == "DISABLED" || $status == "UNSET") {
		return;
	}
	$now = time();
	startTransaction();
	foreach(stdList("billingSubscription", array("customerID"=>$customerID), array("subscriptionID", "revenueAccountID", "domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
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
			
			stdNew("billingSubscriptionLine", array("customerID"=>$customerID, "revenueAccountID"=>$subscription["revenueAccountID"], "description"=>$subscription["description"], "periodStart"=>$subscription["nextPeriodStart"], "periodEnd"=>$periodEnd, "price"=>$price, "discount"=>$discount));
			
			stdSet("billingSubscription", array("subscriptionID"=>$subscription["subscriptionID"]), array("nextPeriodStart"=>$periodEnd));
			$subscription["nextPeriodStart"] = $periodEnd;
		}
		if($subscription["endDate"] !== null && $subscription["nextPeriodStart"] >= $subscription["endDate"]) {
			if($subscription["domainTldID"] !== null) {
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

function billingUpdateAllSubscriptionLines()
{
	foreach(stdList("adminCustomer", array(), "customerID") as $customerID) {
		billingUpdateSubscriptionLines($customerID);
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
	billingUpdateSubscriptionLines($customerID);
	
	$now = time();
	$invoiceTime = stdGet("adminCustomer", array("customerID"=>$customerID), array("invoiceFrequencyBase", "invoiceFrequencyMultiplier", "nextInvoiceDate"));
	
	if($invoiceTime["nextInvoiceDate"] > $now) {
		return;
	}
	$nextInvoiceTime = $invoiceTime["nextInvoiceDate"];
	while($nextInvoiceTime < $now) {
		$nextInvoiceTime = billingCalculateNextDate($nextInvoiceTime, $invoiceTime["invoiceFrequencyBase"], $invoiceTime["invoiceFrequencyMultiplier"]);
	}
	
	$subscriptionLines = stdList("billingSubscriptionLine", array("customerID"=>$customerID), "subscriptionLineID");
	if(count($subscriptionLines) == 0) {
		return;
	}
	
	stdSet("adminCustomer", array("customerID"=>$customerID), array("nextInvoiceDate"=>$nextInvoiceTime));
	
	billingCreateInvoice($customerID, $subscriptionLines);
}

function billingCreateAllInvoices()
{
	foreach(stdList("adminCustomer", array(), "customerID") as $customerID) {
		billingCreateInvoiceBatch($customerID);
	}
}

function billingCreateInvoice($customerID, $subscriptionLines, $sendEmail = true)
{
	if(count($subscriptionLines) == 0) {
		throw new BillingInvoiceException();
	}
	$now = time();
	$factuurnrDatum = date("Ymd", $now);
	
	startTransaction();
	stdLock("billingInvoice", array());
	$factuurnrCount = query("SELECT invoiceID FROM billingInvoice WHERE invoiceNumber LIKE '" . dbAddSlashes($factuurnrDatum) . "-%'")->numRows();
	$factuurnr = $factuurnrDatum . "-" . ($factuurnrCount + 1);
	
	$customer = stdGet("adminCustomer", array("customerID"=>$customerID), array("accountID", "name"));
	
	$transactionLines = array();
	$invoiceLines = array();
	$total = 0;
	$taxTotal = 0;
	foreach($subscriptionLines as $subscriptionlineID) {
		$line = stdGet("billingSubscriptionLine", array("subscriptionLineID"=>$subscriptionlineID), array("customerID", "revenueAccountID", "description", "periodStart", "periodEnd", "price", "discount"));
		if($line["customerID"] != $customerID) {
			rollbackTransaction();
			throw new BillingInvoiceException();
		}
		$priceWithoutTax = round($line["price"] / (1 + $GLOBALS["taxRate"]));
		$discountWithoutTax = round($line["discount"] / (1 + $GLOBALS["taxRate"]));

		$amount = $line["price"] - $line["discount"];
		$amountWithoutTax = $priceWithoutTax - $discountWithoutTax;
		
		$tax = $amount - $amountWithoutTax;
		$taxTotal += $tax;
		$total += $amount;
		
		$transactionLines[] = array("accountID"=>$line["revenueAccountID"], "amount"=>-1 * $amountWithoutTax);
		
		$invoiceLines[] = array("description"=>$line["description"], "periodStart"=>$line["periodStart"], "periodEnd"=>$line["periodEnd"], "price"=>$priceWithoutTax, "discount"=>$discountWithoutTax, "tax"=>$tax, "taxRate"=>("" . $GLOBALS["taxRate"] * 100));
		
		stdDel("billingSubscriptionLine", array("subscriptionLineID"=>$subscriptionlineID));
	}
	$transactionLines[] = array("accountID"=>$GLOBALS["taxPayableAccountID"], "amount"=>-1 * $taxTotal);
	$transactionLines[] = array("accountID"=>$customer["accountID"], "amount"=>$total);
	
	$transactionID = accountingAddTransaction($now, "Invoice $factuurnr for customer {$customer["name"]}", $transactionLines);
	
	$invoiceID = stdNew("billingInvoice", array("customerID"=>$customerID, "transactionID"=>$transactionID, "invoiceNumber"=>$factuurnr));
	
	foreach($invoiceLines as $invoiceLine) {
		$invoiceLine["invoiceID"] = $invoiceID;
		stdNew("billingInvoiceLine", $invoiceLine);
	}
	
	commitTransaction();
	
	billingCreateInvoiceTex($invoiceID, $sendEmail);
}

function billingCreateInvoiceTex($invoiceID, $sendEmail = true)
{
	$invoice = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), array("customerID", "transactionID", "invoiceNumber"));
	$customer = stdGet("adminCustomer", array("customerID"=>$invoice["customerID"]), array("name", "companyName", "initials", "lastName", "address", "postalCode", "city", "countryCode"));
	
	$texdatum = date("d/m/Y", stdGet("accountingTransaction", array("transactionID"=>$invoice["transactionID"]), "date"));
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
	foreach(stdList("billingInvoiceLine", array("invoiceID"=>$invoiceID), array("description", "periodStart", "periodEnd", "price", "discount", "tax", "taxRate")) as $line) {
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
			$priceFormat = formatPriceRaw($line["price"]);
			$desciptionTex = latexEscapeString($line["description"]);
			$posts .= "\\post{{$desciptionTex}}{{$startdate}}{{$enddate}}{{$priceFormat}}\n";
		}
		if($line["discount"] != 0) {
			$discountDescription = "Korting " . strtolower(substr($line["description"], 0, 1)) . substr($line["description"], 1);
			$discountDescriptionTex = latexEscapeString($discountDescription);
			$discountamountFormat = formatPriceRaw($line["discount"]);
			$discounts .= "\\korting{{$discountDescriptionTex}}{{$discountamountFormat}}\n";
		}
		$btw += $line["tax"];
	}
	$btwTex = formatPriceRaw($btw);
	$brieftype = $creditatie ? "creditatiebrief" : "factuurbrief";
	$tex = <<<TEX
\\documentclass{{$GLOBALS["invoiceLatexDocumentClass"]}}
\\usepackage{{$GLOBALS["invoiceLatexPackage"]}}
\\setDatum[{$texdatum}]

\\begin{document}

\\begin{{$brieftype}}{{$to}}{{$invoiceNumber}}

\\begin{factuur}
{$posts}{$discounts}\\btw{{$btwTex}}
\\end{factuur}

\\end{{$brieftype}}

\\end{document}

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
	$invoice = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), array("customerID", "transactionID", "invoiceNumber", "pdf"));
	if($invoice["pdf"] === null) {
		return;
	}
	$customer = stdGet("adminCustomer", array("customerID"=>$invoice["customerID"]), array("accountID", "name", "companyName", "initials", "lastName", "email"));
	
	$remainingAmount = billingInvoiceRemainingAmount($invoiceID);
	if($remainingAmount > 0) {
		$bedrag = stdGet("accountingTransactionLine", array("transactionID"=>$invoice["transactionID"], "accountID"=>$customer["accountID"]), "amount");
		$bedragText = formatPriceRaw($remainingAmount);
		if($bedrag == $remainingAmount) {
			$betalen = "Wij verzoeken u dit bedrag (€ $bedragText) binnen 30 dagen over te maken op rekeningnummer XXXXXXXX t.n.v. Valkerij Nadicia, onder vermelding van het factuurnummer ({$invoice["invoiceNumber"]}).";
		} else {
			$betalen = "Deze factuur is nog niet volledig betaald. Wij verzoeken u het resterende bedrag (€ $bedragText) binnen 30 dagen over te maken op rekeningnummer XXXXXXXX t.n.v. Valkerij Nadicia, onder vermelding van het factuurnummer ({$invoice["invoiceNumber"]}).";
		}
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
	$mail->setSender("info@nadicia.nl", "Valkerij Nadicia");
	$mail->addBcc("info@nadicia.nl");
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

Valkerij Nadicia

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

function billingAddPayment($customerID, $bankAccountID, $amount, $date, $desciption)
{
	$accountID = stdGet("adminCustomer", array("customerID"=>$customerID), "accountID");
	$lines = array(
		array("accountID"=>$bankAccountID, "amount"=>$amount),
		array("accountID"=>$accountID, "amount"=>$amount * -1),
	);
	
	startTransaction();
	$transactionID = accountingAddTransaction($date, $desciption, $lines);
	stdNew("billingPayment", array("customerID"=>$customerID, "transactionID"=>$transactionID));
	commitTransaction();
}

function billingEditPayment($paymentID, $bankAccountID, $amount, $date, $desciption)
{
	$payment = stdGet("billingPayment", array("paymentID"=>$paymentID), array("customerID", "transactionID"));
	$accountID = stdGet("adminCustomer", array("customerID"=>$payment["customerID"]), "accountID");
	$lines = array(
		array("accountID"=>$bankAccountID, "amount"=>$amount),
		array("accountID"=>$accountID, "amount"=>$amount * -1),
	);
	
	accountingEditTransaction($payment["transactionID"], $date, $desciption, $lines);
}

function billingDeletePayment($paymentID)
{
	$payment = stdGet("billingPayment", array("paymentID"=>$paymentID), array("customerID", "transactionID"));
	
	startTransaction();
	stdDel("billingPayment", array("paymentID"=>$paymentID));
	accountingDeleteTransaction($payment["transactionID"]);
	commitTransaction();
}

function billingBalance($customerID)
{
	$accountID = stdGet("adminCustomer", array("customerID"=>$customerID), "accountID");
	return -stdGet("accountingAccount", array("accountID"=>$accountID), "balance");
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

function billingInvoiceStatusList($customerID)
{
	$accountID = stdGet("adminCustomer", array("customerID"=>$customerID), "accountID");
	$balance = -billingBalance($customerID);
	
	$invoices = array();
	foreach(stdList("billingInvoice", array("customerID"=>$customerID), array("invoiceID", "customerID", "transactionID", "invoiceNumber")) as $invoice) {
		$invoice["date"] = stdGet("accountingTransaction", array("transactionID"=>$invoice["transactionID"]), "date");
		$invoice["amount"] = stdGet("accountingTransactionLine", array("transactionID"=>$invoice["transactionID"], "accountID"=>$accountID), "amount");
		$invoices[] = $invoice;
	}
	usort($invoices, function($a, $b) { return $b["date"] - $a["date"]; });
	for($i = 0; $i < count($invoices); $i++) {
		if($balance <= 0) {
			$invoices[$i]["remainingAmount"] = 0;
		} else if($balance >= $invoices[$i]["amount"]) {
			$invoices[$i]["remainingAmount"] = $invoices[$i]["amount"];
		} else {
			$invoices[$i]["remainingAmount"] = $balance;
		}
		$balance -= $invoices[$i]["amount"];
	}
	
	return $invoices;
}

function billingInvoiceRemainingAmount($invoiceID)
{
	$customerID = stdGet("billingInvoice", array("invoiceID"=>$invoiceID), "customerID");
	foreach(billingInvoiceStatusList($customerID) as $invoice) {
		if($invoice["invoiceID"] == $invoiceID) {
			return $invoice["remainingAmount"];
		}
	}
	return null;
}

function billingCreateStatusWarnings()
{
	$unsetList = stdList("adminCustomer", array("invoiceStatus"=>"UNSET"), "name");
	$previewList = stdList("adminCustomer", array("invoiceStatus"=>"PREVIEW"), "name");
	if(count($unsetList) == 0 && count($previewList) == 0) {
		return;
	}
	
	mailAdmin("There are customers with invalid invoice status!", "The following customers have an invalid invoice status:\n" . (count($unsetList) ? "\nunset:\n" . implode("\n", $unsetList) . "\n" : "") . (count($previewList) ? "\npreview:\n" . implode("\n", $previewList) . "\n" : ""));
}

class BillingInvoiceException extends Exception {}
class BillingCalculateNextDateException extends Exception {}

?>