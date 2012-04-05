<?php

require_once(dirname(__FILE__) . "/../common.php");

function doBilling()
{
	useComponent("billing");
	$GLOBALS["menuComponent"] = "billing";
}

function doBillingAdmin($customerID)
{
	doBilling();
	useCustomer($customerID);
	useCustomer(0);
}

function doInvoice($invoiceID)
{
	doBilling();
	useCustomer($GLOBALS["database"]->stdGetTry("billingInvoice", array("invoiceID"=>$invoiceID), "customerID", false));
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

function addHeader($customerID, $title, $filename)
{
	$header = "<h1>$title</h1>\n";
	
	$breadcrumbs = billingAdminCustomerBreadcrumbs($customerID, array(array("name"=>$title, "url"=>"{$GLOBALS["root"]}billing/$filename")));
	
	return $header . $breadcrumbs;
}

function subscriptionList($customerID)
{
	$output = "";
	
	$output .= <<<HTML
<div class="sortable list">
<table>
<caption>Subscriptions</caption>
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
<caption>Subscriptions</caption>
<thead>
<tr><th>Description</th><th>Price</th></tr>
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
		
		if($subscription["frequencyBase"] == "DAY") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? "day" : $subscription["frequencyMultiplier"] . " days");
		} else if($subscription["frequencyBase"] == "MONTH") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? " month" : $subscription["frequencyMultiplier"] . " months");
		} else if($subscription["frequencyBase"] == "YEAR") {
			$frequency = "per " . ($subscription["frequencyMultiplier"] == 1 ? " year" : $subscription["frequencyMultiplier"] . " years");
		}
		
		$output .= "<tr><td>";
		if($subscription["domainTldID"] !== null) {
			$output .= "<a href=\"{$GLOBALS["rootHtml"]}domains/domain.php?id={$subscription["domainTldID"]}\">";
		}
		$output .= $description;
		if($subscription["domainTldID"] !== null) {
			$output .= "</a>";
		}
		$output .= "</td><td>$priceHtml $frequency</td></tr>\n";
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
<caption>Invoices</caption>
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
<caption>Payments</caption>
<thead>
<tr><th>Date</th><th>Amount</th><th>Description</th></tr>
</thead>
<tbody>
HTML;
	foreach($GLOBALS["database"]->stdList("billingPayment", array("customerID"=>$customerID), array("amount", "date", "description"), array("date"=>"DESC", "paymentID"=>"DESC")) as $payment) {
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

function addPaymentForm($customerID, $error = "", $values = null)
{
	if($values === null) {
		$values = array("date"=>date("d-m-Y"));
	}
	if(isset($values["date"])) {
		$values["date"] = date("d-m-Y", parseDate($values["date"]));
	}
	if(isset($values["amount"])) {
		$values["amount"] = formatPriceRaw(parsePrice($values["amount"]));
	}
	return operationForm("addpayment.php?id=$customerID", $error, "Add payment", "Save",
		array(
			array("title"=>"Amount", "type"=>"text", "name"=>"amount"),
			array("title"=>"Description", "type"=>"text", "name"=>"description"),
			array("title"=>"Date", "type"=>"text", "name"=>"date")
		),
		$values);
}

function addSubscriptionForm($customerID, $error = "", $values = null)
{
	if($values === null) {
		$values = array("discountPercentage"=>0, "discountAmount"=>"0,00", "frequencyMultiplier"=>1, "frequencyBase"=>"MONTH", "nextPeriodStart"=>date("d-m-Y"), "invoiceDelay"=>0);
	}
	if(isset($values["nextPeriodStart"])) {
		$values["nextPeriodStart"] = date("d-m-Y", parseDate($values["nextPeriodStart"]));
	}
	return operationForm("addsubscription.php?id=$customerID", $error, "Add subscription", "Save",
		array(
			array("title"=>"Description", "type"=>"text", "name"=>"description"),
			array("title"=>"Price", "type"=>"text", "name"=>"price"),
			array("title"=>"Discount percentage", "type"=>"text", "name"=>"discountPercentage"),
			array("title"=>"Discount amount", "type"=>"text", "name"=>"discountAmount"),
			array("title"=>"Frequency", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>"per"),
				array("type"=>"text", "name"=>"frequencyMultiplier", "fill"=>true),
				array("type"=>"dropdown", "name"=>"frequencyBase", "options"=>array(
					array("label"=>"year", "value"=>"YEAR"),
					array("label"=>"month", "value"=>"MONTH"),
					array("label"=>"day", "value"=>"YEAR")
				))
			)),
			array("title"=>"Start date", "type"=>"text", "name"=>"nextPeriodStart"),
			array("title"=>"Invoice delay", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"invoiceDelay", "fill"=>true),
				array("type"=>"html", "html"=>"days")
			))
		),
		$values);
}

function editSubscriptionForm($subscriptionID, $error = "", $values = null)
{
	if($values === null) {
		$values = $GLOBALS["database"]->stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("domainTldID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate"));
		$values["price"] = formatPriceRaw($values["price"]);
		$values["discountAmount"] = formatPriceRaw($values["discountAmount"]);
		$values["invoiceDelay"] = round($values["invoiceDelay"] / (24 * 3600));
	}
	return operationForm("editsubscription.php?id=$subscriptionID", $error, "Edit subscription", "Save",
		array(
			array("title"=>"Description", "type"=>"text", "name"=>"description"),
			array("title"=>"Price", "type"=>"text", "name"=>"price"),
			array("title"=>"Discount percentage", "type"=>"text", "name"=>"discountPercentage"),
			array("title"=>"Discount amount", "type"=>"text", "name"=>"discountAmount"),
			array("title"=>"Frequency", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>"per"),
				array("type"=>"text", "name"=>"frequencyMultiplier", "fill"=>true),
				array("type"=>"dropdown", "name"=>"frequencyBase", "options"=>array(
					array("label"=>"year", "value"=>"YEAR"),
					array("label"=>"month", "value"=>"MONTH"),
					array("label"=>"day", "value"=>"YEAR")
				))
			)),
			array("title"=>"Invoice delay", "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"invoiceDelay", "fill"=>true),
				array("type"=>"html", "html"=>"days")
			))
		),
		$values);
}

function endSubscriptionForm($subscriptionID, $error = "", $values = null)
{
	if($values === null) {
		$values = array("endDate"=>date("d-m-Y"));
	}
	return operationForm("endsubscription.php?id=$subscriptionID", $error, "End subscription", "Save", array(array("title"=>"End date", "type"=>"text", "name"=>"endDate")), $values);
}

function addInvoiceLineForm($customerID, $error = "", $values = null)
{
	return operationForm("addinvoiceline.php?id=$customerID", $error, "Add invoice line", "Save", 
		array(
			array("title"=>"Description", "type"=>"text", "name"=>"description"),
			array("title"=>"Price", "type"=>"text", "name"=>"price"),
			array("title"=>"Discount", "type"=>"text", "name"=>"discount")
		),
		$values);
}

function editInvoiceLineForm($invoiceLineID, $error = "", $values = null)
{
	if($values === null) {
		$values = $GLOBALS["database"]->stdGet("billingInvoiceLine", array("invoiceLineID"=>$invoiceLineID), array("description", "price", "discount", "periodStart", "periodEnd"));
		$values["price"] = floor($values["price"] / 100) . "," . str_pad($values["price"] % 100, 2, "0");
		$values["discount"] = floor($values["discount"] / 100) . "," . str_pad($values["discount"] % 100, 2, "0");
		$values["periodStart"] = date("d-m-Y", $values["periodStart"]);
		$values["periodEnd"] = date("d-m-Y", $values["periodEnd"]);
	}
	return operationForm("editinvoiceline.php?id=$invoiceLineID", $error, "Edit invoice line", "Save", 
		array(
			array("title"=>"Description", "type"=>"text", "name"=>"description"),
			array("title"=>"Price", "type"=>"text", "name"=>"price"),
			array("title"=>"Discount", "type"=>"text", "name"=>"discount"),
			array("title"=>"Period start", "type"=>"text", "name"=>"periodStart"),
			array("title"=>"Period end", "type"=>"text", "name"=>"periodEnd")
		),
		$values);
}

function removeInvoiceLineForm($invoiceLineID, $error = "", $values = null)
{
	return operationForm("removeinvoiceline.php?id=$invoiceLineID", $error, "Remove invoice", "Remove", array(), $values);
}

function sendInvoiceForm($customerID, $error = "", $values = null)
{
	$lines = array();
	$lines[] = array("type"=>"colspan", "columns"=>array(
		array("type"=>"html", "html"=>""),
		array("type"=>"html", "html"=>"Description", "fill"=>true),
		array("type"=>"html", "html"=>"Price"),
		array("type"=>"html", "html"=>"Discount"),
		array("type"=>"html", "html"=>"Start date"),
		array("type"=>"html", "html"=>"End date")
	));
	foreach($GLOBALS["database"]->stdList("billingInvoiceLine", array("customerID"=>$customerID, "invoiceID"=>null), array("invoiceLineID", "description", "price", "discount", "periodStart", "periodEnd")) as $invoiceLine) {
		$lines[] = array("type"=>"colspan", "columns"=>array(
			array(/* "type"=>"checkbox"  TODO */ "type"=>"html", "html"=>"<input type=\"checkbox\">"),
			array("type"=>"html", "fill"=>true, "html"=>$invoiceLine["description"]),
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>formatPrice($invoiceLine["price"])),
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>formatPrice($invoiceLine["discount"])),
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>$invoiceLine["periodStart"] == null ? "-" : date("d-m-Y", $invoiceLine["periodStart"])),
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>$invoiceLine["periodEnd"] == null ? "-" : date("d-m-Y", $invoiceLine["periodEnd"]))
		));
	}
	// TODO: add fields to chose from
	return operationForm("sendInvoice.php?id=$customerID", $error, "Send invoice", "Send", $lines, $values);
}

?>