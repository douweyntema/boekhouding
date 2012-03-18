<?php

require_once(dirname(__FILE__) . "/../common.php");

function doBilling()
{
	useComponent("billing");
	$GLOBALS["menuComponent"] = "billing";
}

function doCustomer($customerID)
{
	doBilling();
	useCustomer($customerID);
}

function doSubscription($subscriptionID)
{
	doBilling();
	doCustomer($GLOBALS["database"]->stdGetTry("billingSubscription", array("subscriptionID"=>$subscriptionID), "customerID", false));
}

function doInvoice($invoiceID)
{
	doBilling();
	doCustomer($GLOBALS["database"]->stdGetTry("billingInvoice", array("invoiceID"=>$invoiceID), "customerID", false));
}

function billingBreadcrumbs($postfix = array())
{
	return breadcrumbs(array_merge(array(array("name"=>"Billing", "url"=>"{$GLOBALS["root"]}billing/")), $postfix));
}

function billingAdminCustomerBreadcrumbs($customerID, $postfix = array())
{
	$name = $GLOBALS["database"]->stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	return breadcrumbs(array_merge(array(
		array("name"=>"Customers", "url"=>"{$GLOBALS["root"]}customers/"), 
		array("name"=>$name, "url"=>"{$GLOBALS["root"]}customers/customer.php?id=$customerID"),
		array("name"=>"Billing", "url"=>"{$GLOBALS["root"]}billing/customer.php?id=$customerID"), 
		), $postfix));
}

function subscriptionList($customerID)
{
	$output = "";
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Description</th><th>Price</th><th>Discounts</th><th>Renew date</th><th>End date</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("billingSubscription", array("customerID"=>$customerID), array("subscriptionID", "domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
		$description = htmlentities($subscription["description"]);
		
		if($subscription["price"] === null) {
			$baseprice = billingDomainPrice($subscription["domainTldID"]);
		} else {
			$baseprice = $subscription["price"];
		}
		$discount = $baseprice * $subscription["discountPercentage"] / 100 + $subscription["discountAmount"];
		
		$priceHtml = formatPrice($baseprice - $discount);
		
		if($subscription["discountPercentage"] === null && $subscription["discountAmount"] === null) {
			$priceDetail = "None";
		} else {
			$priceDetail = formatPrice($baseprice);
			if($subscription["discountPercentage"] !== null) {
				$priceDetail .= " - " . $subscription["discountPercentage"] . "%";
			}
			if($subscription["discountAmount"] !== null) {
				$priceDetail .= " - " . formatPrice($subscription["discountAmount"]);
			}
		}
		
		if($subscription["frequencyBase"] == "DAY") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? "day" : $subscription["frequencyMultiplier"] . " days");
		} else if($subscription["frequencyBase"] == "MONTH") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? " month" : $subscription["frequencyMultiplier"] . " months");
		} else if($subscription["frequencyBase"] == "YEAR") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? " year" : $subscription["frequencyMultiplier"] . " years");
		}
		
		
		$nextPeriod = date("d-m-Y", $subscription["nextPeriodStart"]);
		
		if($subscription["endDate"] === null) {
			$endDate  = "-";
		} else {
			$endDate = date("d-m-Y", $subscription["endDate"]);
		}
		
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}billing/subscription.php?id={$subscription["subscriptionID"]}\">$description</a></td><td>$priceHtml $frequency</td><td>$priceDetail</td><td>$nextPeriod</td><td>$endDate</td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function customerSubscriptionList()
{
	$output = "";
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Description</th><th>Price</th><th>Discount</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("billingSubscription", array("customerID"=>customerID()), array("subscriptionID", "domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
		$description = htmlentities($subscription["description"]);
		
		if($subscription["price"] === null) {
			$baseprice = billingDomainPrice($subscription["domainTldID"]);
		} else {
			$baseprice = $subscription["price"];
		}
		$discount = $baseprice * $subscription["discountPercentage"] / 100 + $subscription["discountAmount"];
		
		$priceHtml = formatPrice($baseprice - $discount);
		
		if($subscription["discountPercentage"] === null && $subscription["discountAmount"] === null) {
			$priceDetail = "None";
		} else {
			$priceDetail = formatPrice($baseprice);
			if($subscription["discountPercentage"] !== null) {
				$priceDetail .= " - " . $subscription["discountPercentage"] . "%";
			}
			if($subscription["discountAmount"] !== null) {
				$priceDetail .= " - " . formatPrice($subscription["discountAmount"]);
			}
		}
		
		if($subscription["frequencyBase"] == "DAY") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? "day" : $subscription["frequencyMultiplier"] . " days");
		} else if($subscription["frequencyBase"] == "MONTH") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? " month" : $subscription["frequencyMultiplier"] . " months");
		} else if($subscription["frequencyBase"] == "YEAR") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? " year" : $subscription["frequencyMultiplier"] . " years");
		}
		
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}billing/subscription.php?id={$subscription["subscriptionID"]}\">$description</a></td><td>$priceHtml $frequency</td><td>$priceDetail</td></tr>\n";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function subscriptionDetail($subscriptionID)
{
	$subscription = $GLOBALS["database"]->stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("subscriptionID", "domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate"));
	
	$description = htmlentities($subscription["description"]);
		
	if($subscription["price"] === null) {
		$baseprice = billingDomainPrice($subscription["domainTldID"]);
	} else {
		$baseprice = $subscription["price"];
	}
	$discount = $baseprice * $subscription["discountPercentage"] / 100 + $subscription["discountAmount"];
	
	$basepriceHtml = formatPrice($baseprice);
	$priceHtml = formatPrice($baseprice - $discount);
	
	if($subscription["discountPercentage"] !== null) {
		$discountPercentage = $subscription["discountPercentage"] . "% (" . formatPrice($baseprice * $subscription["discountPercentage"] / 100) . ")";
	} else {
		$discountPercentage = "-";
	}
	
	if($subscription["discountAmount"] !== null) {
		$discountAmount = formatPrice($subscription["discountAmount"]);
	} else {
		$discountAmount = "-";
	}
	
	if($subscription["frequencyBase"] == "DAY") {
		$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? "day" : $subscription["frequencyMultiplier"] . " days");
	} else if($subscription["frequencyBase"] == "MONTH") {
		$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? " month" : $subscription["frequencyMultiplier"] . " months");
	} else if($subscription["frequencyBase"] == "YEAR") {
		$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? " year" : $subscription["frequencyMultiplier"] . " years");
	}
	
	if($subscription["invoiceDelay"] == 0) {
		$delay = "None";
	} else if($subscription["invoiceDelay"] > 0) {
		$delay = ceil($subscription["invoiceDelay"] / 86400) . " days later";
	} else {
		$delay = ceil(-1 * $subscription["invoiceDelay"] / 86400) . " days in advance";
	}
	
	$renewDate = date("d-m-Y", $subscription["nextPeriodStart"]);
	
	if($subscription["endDate"] === null) {
		$endDate  = "-";
	} else {
		$endDate = date("d-m-Y", $subscription["endDate"]);
	}
	
	if($subscription["domainTldID"] !== null) {
		$domainID = $GLOBALS["database"]->stdGetTry("dnsDomain", array("subscriptionID"=>$subscriptionID), "domainID");
		if($domainID === null) {
			$domainTldName = "." . $GLOBALS["database"]->stdGet("infrastructureDomainTld", array("domainTldID"=>$subscription["domainTldID"]), "name");
			$domainName = "unknown $domainTldName domain";
		} else {
			$domainName = domainsFormatDomainName($domainID);
		}
	} else {
		$domainName = "-";
	}
	
	return <<<HTML
<div class="operation">
<h2>Subscription</h2>
<table>
<tr><th>Description</th><td>$description</td></tr>
<tr><th>Price</th><td>$priceHtml</td></tr>
<tr><th>Base price</th><td>$basepriceHtml</td></tr>
<tr><th>Discount percentage</th><td>$discountPercentage</td></tr>
<tr><th>Discount amount</th><td>$discountAmount</td></tr>
<tr><th>Frequency</th><td>$frequency</td></tr>
<tr><th>Invoice delay</th><td>$delay</td></tr>
<tr><th>Renew date</th><td>$renewDate</td></tr>
<tr><th>End date</th><td>$endDate</td></tr>
<tr><th>Related domain</th><td>$domainName</td></tr>
</table>
</div>
HTML;
}

function invoiceList($customerID)
{
	$output = "";
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Invoice number</th><th>Date</th><th>Amount</th><th>Remaining amount</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("billingInvoice", array("customerID"=>$customerID), array("invoiceID", "date", "remainingAmount", "invoiceNumber"), array("date"=>"DESC")) as $invoice) {
		$invoiceNumber = htmlentities($invoice["invoiceNumber"]);
		
		$date = date("d-m-Y", $invoice["date"]);
		
		$amount = 0;
		foreach($GLOBALS["database"]->stdList("billingInvoiceLine", array("invoiceID"=>$invoice["invoiceID"]), array("price", "discount")) as $line) {
			$amount += $line["price"] - $line["discount"];
		}
		$amount = formatPrice($amount);
		
		if($invoice["remainingAmount"] == 0) {
			$remainingAmount = "Paid";
		} else {
			$remainingAmount = formatPrice($invoice["remainingAmount"]);
		}
		
		$output .= "<tr><td><a href=\"{$GLOBALS["rootHtml"]}billing/invoicepdf.php?id={$invoice["invoiceID"]}\">$invoiceNumber</a></td><td>$date</td><td>$amount</td><td>$remainingAmount</td></tr>";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function paymentList($customerID)
{
	$output = "";
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<thead>
<tr><th>Date</th><th>Amount</th><th>Description</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("billingPayment", array("customerID"=>$customerID), array("amount", "date", "description"), array("date"=>"DESC")) as $payment) {
		$date = date("d-m-Y", $payment["date"]);
		$amount = formatPrice($payment["amount"]);
		$description = htmlentities($payment["description"]);
		$output .= "<tr><td>$date</td><td>$amount</td><td>$description</td></tr>";
	}
	$output .= <<<HTML
</tbody>
</table>
</div>

HTML;
	return $output;
}

function addPaymentForm($customerID, $error = "", $values = null) {}

function addSubscriptionForm($customerID, $error = "", $values = null) {}
function editSubscriptionForm($subscriptionID, $error = "", $values = null) {}
function deleteSubscriptionForm($subscriptionID, $error = "", $values = null) {}

function addInvoiceLineForm($customerID, $error = "", $values = null) {}
function editInvoiceLineForm($invoiceID, $error = "", $values = null) {}
function removeInvoiceLineForm($invoiceID, $error = "", $values = null) {}

function sendInvoiceForm($customerID, $error = "", $values = null) {}

?>