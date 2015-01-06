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
	useCustomer(stdGetTry("billingInvoice", array("invoiceID"=>$invoiceID), "customerID", false));
}

function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}billing/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function customersBillingBreadcrumbs()
{
	return crumbs(_("Billing"), "");
}

function adminCustomerBreadcrumbs($customerID)
{
	$name = stdGet("adminCustomer", array("customerID"=>$customerID), "name");
	return array(
		array("name"=>_("Customers"), "url"=>"{$GLOBALS["root"]}customers/"),
		array("name"=>$name, "url"=>"{$GLOBALS["root"]}customers/customer.php?id=$customerID"),
		crumb(_("Billing"), "customer.php?id=$customerID")
	);
}

function adminSubscriptionBreadcrumbs($subscriptionID)
{
	$subscription = stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("customerID", "description"));
	return array_merge(adminCustomerBreadcrumbs($subscription["customerID"]), crumbs($subscription["description"], "subscription.php?id=$subscriptionID"));
}

function adminPaymentBreadcrumbs($paymentID)
{
	$payment = stdGet("billingPayment", array("paymentID"=>$paymentID), array("customerID", "transactionID"));
	$date = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), "date");
	$name = sprintf(_("Payment on %s"), date("d-m-Y", $date));
	return array_merge(adminCustomerBreadcrumbs($payment["customerID"]), crumbs($name, "payment.php?id=$payment"));
}

function customerSummary($customerID)
{
	$customer = stdGet("adminCustomer", array("customerID"=>$customerID), array("accountID", "name", "invoiceStatus"));
	return summaryTable(sprintf(_("Customer %s"), $customer["name"]), array(
		_("Balance")=>array("url"=>"{$GLOBALS["rootHtml"]}accounting/account.php?id={$customer["accountID"]}", "html"=>accountingFormatAccountPrice($customer["accountID"], true)),
		_("Invoice status")=>ucfirst(strtolower($customer["invoiceStatus"])),
	));
}

function invoiceStatusForm($customerID, $error = "", $values = null)
{
	if($values === null) {
		$values = stdGet("adminCustomer", array("customerID"=>$customerID), array("invoiceStatus"));
	}
	return operationForm("changestatus.php?id=$customerID", $error, _("Change invoice status"), _("Save"),
		array(
			array("title"=>_("Status"), "type"=>"dropdown", "name"=>"invoiceStatus", "options"=>array(
				array("label"=>_("Unset"), "value"=>"UNSET"),
				array("label"=>_("Disabled"), "value"=>"DISABLED"),
				array("label"=>_("Preview"), "value"=>"PREVIEW"),
				array("label"=>_("Enabled"), "value"=>"ENABLED")
			)),
		),
		$values);
}

function subscriptionList($customerID)
{
	$rows = array();
	foreach(stdList("billingSubscription", array("customerID"=>$customerID), array("subscriptionID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
		if($subscription["discountPercentage"] === null && $subscription["discountAmount"] === null) {
			$priceDetail = _("None");
		} else {
			$priceDetail = formatPrice(billingBasePrice($subscription));
			if($subscription["discountPercentage"] !== null) {
				$priceDetail .= " - " . $subscription["discountPercentage"] . "%";
			}
			if($subscription["discountAmount"] !== null) {
				$priceDetail .= " - " . formatPrice($subscription["discountAmount"]);
			}
		}
		
		$nextPeriod = date("d-m-Y", $subscription["nextPeriodStart"]);
		
		if($subscription["endDate"] === null) {
			$endDate  = "-";
		} else {
			$endDate = date("d-m-Y", $subscription["endDate"]);
		}
		
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}billing/subscription.php?id={$subscription["subscriptionID"]}", "text"=>$subscription["description"]),
			array("html"=>formatSubscriptionPrice($subscription)),
			array("html"=>$priceDetail),
			$nextPeriod,
			$endDate
		);
	}
	return listTable(array(_("Description"), _("Price"), _("Discounts"), _("Renew date"), _("End date")), $rows, _("Subscriptions"), true, "sortable list");
}

function customerSubscriptionList()
{
	$rows = array();
	foreach(stdList("billingSubscription", array("customerID"=>customerID()), array("subscriptionID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate")) as $subscription) {
		$rows[] = array(
			array("text"=>$subscription["description"]),
			array("html"=>formatSubscriptionPrice($subscription))
		);
	}
	return listTable(array(_("Description"), _("Price")), $rows, _("Subscriptions"), false, "sortable list");
}

function subscriptionDetail($subscriptionID)
{
	$subscription = stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("revenueAccountID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate"));
	
	if($subscription["discountPercentage"] !== null) {
		$discountPercentage = $subscription["discountPercentage"] . "% (" . formatPrice(billingBasePrice($subscription) * $subscription["discountPercentage"] / 100) . ")";
	} else {
		$discountPercentage = "-";
	}
	
	if($subscription["discountAmount"] !== null) {
		$discountAmount = formatPrice($subscription["discountAmount"]);
	} else {
		$discountAmount = "-";
	}
	
	if($subscription["invoiceDelay"] == 0) {
		$delay = _("None");
	} else if($subscription["invoiceDelay"] > 0) {
		$delay = sprintf(_("%s days later"), ceil($subscription["invoiceDelay"] / 86400));
	} else {
		$delay = sprintf(_("%s days in advance"), ceil(-1 * $subscription["invoiceDelay"] / 86400));
	}
	
	$revenueAccountName = stdGet("accountingAccount", array("accountID"=>$subscription["revenueAccountID"]), "name");
	
	return summaryTable(_("Subscription"), array(
		_("Description")=>$subscription["description"],
		_("Price")=>array("html"=>formatSubscriptionPrice($subscription)),
		_("Base price")=>array("html"=>formatPrice(billingBasePrice($subscription))),
		_("Discount percentage")=>array("html"=>$discountPercentage),
		_("Discount amount")=>array("html"=>$discountAmount),
		_("Frequency")=>frequency($subscription),
		_("Invoice delay")=>$delay,
		_("Renew date")=>date("d-m-Y", $subscription["nextPeriodStart"]),
		_("End date")=>($subscription["endDate"] === null ? "-" : date("d-m-Y", $subscription["endDate"])),
		_("Revenue account")=>array("url"=>"{$GLOBALS["rootHtml"]}accounting/account.php?id={$subscription["revenueAccountID"]}", "text"=>$revenueAccountName),
	));
}

function invoiceList($customerID)
{
	$accountID = stdGet("adminCustomer", array("customerID"=>$customerID), "accountID");
	$balance = -billingBalance($customerID);
	
	$rows = array();
	foreach(billingInvoiceStatusList($customerID) as $invoice) {
		$rows[] = array(
			date("d-m-Y", $invoice["date"]),
			array("url"=>"{$GLOBALS["rootHtml"]}billing/invoicepdf.php?id={$invoice["invoiceID"]}", "text"=>$invoice["invoiceNumber"]),
			array("url"=>"{$GLOBALS["rootHtml"]}accounting/transaction.php?id={$invoice["transactionID"]}", "html"=>formatPrice($invoice["amount"])),
			array("html"=>($invoice["remainingAmount"] == 0 ? "Paid" : formatPrice($invoice["remainingAmount"]))),
			$invoice["remainingAmount"] == 0 ? array("html"=>"") : array("url"=>"reminder.php?id={$invoice["invoiceID"]}", "text"=>"Send reminder"),
			array("url"=>"resend.php?id={$invoice["invoiceID"]}", "text"=>"Resend")
		);
	}
	return listTable(array(_("Date"), _("Invoice number"), _("Amount"), _("Remaining amount"), _("Reminder"), _("Resend")), $rows, _("Invoices"), _("No invoices have been sent so far."), "sortable list");
}

function customerInvoiceList($customerID)
{
	$accountID = stdGet("adminCustomer", array("customerID"=>$customerID), "accountID");
	$balance = -billingBalance($customerID);
	
	$rows = array();
	foreach(billingInvoiceStatusList($customerID) as $invoice) {
		$rows[] = array(
			array("url"=>"{$GLOBALS["rootHtml"]}billing/invoicepdf.php?id={$invoice["invoiceID"]}", "text"=>$invoice["invoiceNumber"]),
			date("d-m-Y", $invoice["date"]),
			array("html"=>formatPrice($invoice["amount"])),
			array("html"=>($invoice["remainingAmount"] == 0 ? "Paid" : formatPrice($invoice["remainingAmount"]))),
		);
	}
	return listTable(array(_("Invoice number"), _("Date"), _("Amount"), _("Remaining amount")), $rows, _("Invoices"), _("No invoices have been sent so far."), "sortable list");
}

function paymentList($customerID)
{
	$accountID = stdGet("adminCustomer", array("customerID"=>$customerID), "accountID");
	$customerIDSql = dbAddSlashes($customerID);
	
	$rows = array();
	foreach(query("SELECT paymentID, transactionID, description, date FROM billingPayment INNER JOIN accountingTransaction USING(transactionID) WHERE customerID='$customerIDSql' ORDER BY date DESC")->fetchList() as $payment) {
		$amount = -stdGet("accountingTransactionLine", array("transactionID"=>$payment["transactionID"], "accountID"=>$accountID), "amount");
		
		$rows[] = array(
			array("url"=>"payment.php?id={$payment["paymentID"]}", "text"=>date("d-m-Y", $payment["date"])),
			array("url"=>"{$GLOBALS["rootHtml"]}accounting/transaction.php?id={$payment["transactionID"]}", "html"=>formatPrice($amount)),
			$payment["description"]
		);
	}
	return listTable(array(_("Date"), _("Amount"), _("Description")), $rows, _("Payments"), _("No payments have been made so far."), "sortable list");
}

function paymentSummary($paymentID)
{
	$payment = stdGet("billingPayment", array("paymentID"=>$paymentID), array("customerID", "transactionID"));
	$customer = stdGet("adminCustomer", array("customerID"=>$payment["customerID"]), array("accountID", "name"));
	$transaction = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), array("date", "description"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$customer["accountID"]), "currencyID");
	$currencySymbol = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	
	$dateHtml = date("d-m-Y", $transaction["date"]);
	$amountHtml = accountingCalculateTransactionAmount($payment["transactionID"], $customer["accountID"], true);
	
	$fields = array(
		_("Customer")=>array("url"=>"customer.php?id={$payment["customerID"]}", "text"=>$customer["name"]),
		_("Amount")=>array("url"=>"{$GLOBALS["rootHtml"]}accounting/transaction.php?id={$payment["transactionID"]}", "html"=>$amountHtml),
		_("Date")=>array("text"=>$dateHtml),
		_("Description")=>array("text"=>$transaction["description"]),
	);
	
	return summaryTable(sprintf(_("Payment on %s"), $dateHtml), $fields);
}

function addPaymentForm($customerID, $error = "", $values = null)
{
	if($values === null) {
		$values = array("date"=>date("d-m-Y"), "bankAccountID"=>$GLOBALS["bankDefaultAccountID"], "description"=>"Payment from " . stdGet("adminCustomer", array("customerID"=>$customerID), "name"));
	}
	if(isset($values["date"])) {
		$values["date"] = date("d-m-Y", parseDate($values["date"]));
	}
	if(isset($values["amount"])) {
		$values["amount"] = formatPriceRaw(parsePrice($values["amount"]));
	}
	return operationForm("addpayment.php?id=$customerID", $error, _("Add payment"), _("Save"),
		array(
			array("title"=>_("Amount"), "type"=>"text", "name"=>"amount"),
			array("title"=>_("Description"), "type"=>"text", "name"=>"description"),
			array("title"=>_("Date"), "type"=>"text", "name"=>"date"),
			array("title"=>_("Bank account"), "type"=>"dropdown", "name"=>"bankAccountID", "options"=>accountingAccountOptions($GLOBALS["bankDirectoryAccountID"], true)),
		),
		$values);
}

function editPaymentForm($paymentID, $error = "", $values = null)
{
	$payment = stdGet("billingPayment", array("paymentID"=>$paymentID), array("customerID", "transactionID"));
	$customer = stdGet("adminCustomer", array("customerID"=>$payment["customerID"]), array("accountID", "name"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$customer["accountID"]), "currencyID");
	
	if($values === null) {
		$transaction = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), array("date", "description"));
		$lines = stdList("accountingTransactionLine", array("transactionID"=>$payment["transactionID"]), array("accountID", "amount"));
		foreach($lines as $line) {
			if($line["accountID"] == $customer["accountID"]) {
				$amount = -1 * $line["amount"];
			} else {
				$bankAccountID = $line["accountID"];
			}
		}
		$values = array(
			"amount"=>formatPriceRaw($amount),
			"bankAccountID"=>$bankAccountID,
			"date"=>date("d-m-Y", $transaction["date"]),
			"description"=>$transaction["description"],
		);
	}
	
	return operationForm("editpayment.php?id=$paymentID", $error, _("Edit payment"), _("Save"), array(
			array("title"=>_("Amount"), "type"=>"text", "name"=>"amount"),
			array("title"=>_("Description"), "type"=>"text", "name"=>"description"),
			array("title"=>_("Date"), "type"=>"text", "name"=>"date"),
			array("title"=>_("Bank account"), "type"=>"dropdown", "name"=>"bankAccountID", "options"=>accountingAccountOptions($GLOBALS["bankDirectoryAccountID"], true)),
		), $values);
}

function deletePaymentForm($paymentID, $error = "", $values = null)
{
	return operationForm("deletepayment.php?id=$paymentID", $error, _("Delete payment"), _("Delete"), array(), $values);
}

function addSubscriptionForm($customerID, $error = "", $values = null)
{
	if($values === null) {
		$values = array("discountPercentage"=>0, "discountAmount"=>"0,00", "frequencyMultiplier"=>1, "frequencyBase"=>"MONTH", "nextPeriodStart"=>date("d-m-Y"), "invoiceDelay"=>0);
	}
	if(isset($values["nextPeriodStart"])) {
		$values["nextPeriodStart"] = date("d-m-Y", parseDate($values["nextPeriodStart"]));
	}
	return operationForm("addsubscription.php?id=$customerID", $error, _("Add subscription"), _("Save"),
		array(
			array("title"=>_("Description"), "type"=>"text", "name"=>"description"),
			array("title"=>_("Price"), "type"=>"text", "name"=>"price"),
			array("title"=>_("Discount percentage"), "type"=>"text", "name"=>"discountPercentage"),
			array("title"=>_("Discount amount"), "type"=>"text", "name"=>"discountAmount"),
			array("title"=>_("Frequency"), "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>_("per")),
				array("type"=>"text", "name"=>"frequencyMultiplier", "fill"=>true),
				array("type"=>"dropdown", "name"=>"frequencyBase", "options"=>array(
					array("label"=>_("year"), "value"=>"YEAR"),
					array("label"=>_("month"), "value"=>"MONTH"),
					array("label"=>_("day"), "value"=>"DAY")
				))
			)),
			array("title"=>_("Start date"), "type"=>"text", "name"=>"nextPeriodStart"),
			array("title"=>_("Invoice delay"), "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"invoiceDelay", "fill"=>true),
				array("type"=>"html", "html"=>_("days"))
			)),
			array("title"=>_("Revenue account"), "type"=>"dropdown", "name"=>"revenueAccountID", "options"=>accountingAccountOptions($GLOBALS["revenueDirectoryAccountID"], true)),
		),
		$values);
}

function editSubscriptionForm($subscriptionID, $error = "", $values = null)
{
	if($values === null) {
		$values = stdGet("billingSubscription", array("subscriptionID"=>$subscriptionID), array("revenueAccountID", "description", "price", "discountPercentage", "discountAmount", "frequencyBase", "frequencyMultiplier", "invoiceDelay", "nextPeriodStart", "endDate"));
		$values["priceType"] = $values["price"] === null ? "domain" : "custom";
		$values["price"] = formatPriceRaw($values["price"]);
		$values["discountAmount"] = formatPriceRaw($values["discountAmount"]);
		$values["invoiceDelay"] = round($values["invoiceDelay"] / (24 * 3600));
		if($values["discountPercentage"] === null) {
			$values["discountPercentage"] = 0;
		}
	}
	return operationForm("editsubscription.php?id=$subscriptionID", $error, _("Edit subscription"), _("Save"),
		array(
			array("title"=>_("Description"), "type"=>"text", "name"=>"description"),
			array("title"=>_("Price"), "type"=>"text", "name"=>"price"),
			array("title"=>_("Discount percentage"), "type"=>"text", "name"=>"discountPercentage"),
			array("title"=>_("Discount amount"), "type"=>"text", "name"=>"discountAmount"),
			array("title"=>_("Frequency"), "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>_("per")),
				array("type"=>"text", "name"=>"frequencyMultiplier", "fill"=>true),
				array("type"=>"dropdown", "name"=>"frequencyBase", "options"=>array(
					array("label"=>_("year"), "value"=>"YEAR"),
					array("label"=>_("month"), "value"=>"MONTH"),
					array("label"=>_("day"), "value"=>"DAY")
				))
			)),
			array("title"=>_("Invoice delay"), "type"=>"colspan", "columns"=>array(
				array("type"=>"text", "name"=>"invoiceDelay", "fill"=>true),
				array("type"=>"html", "html"=>_("days"))
			)),
			array("title"=>_("Revenue account"), "type"=>"dropdown", "name"=>"revenueAccountID", "options"=>accountingAccountOptions($GLOBALS["revenueDirectoryAccountID"])),
		),
		$values);
}

function endSubscriptionForm($subscriptionID, $error = "", $values = null)
{
	if($values === null) {
		$values = array("endDate"=>date("d-m-Y"));
	}
	return operationForm("endsubscription.php?id=$subscriptionID", $error, _("End subscription"), _("Save"), array(array("title"=>_("End date"), "type"=>"text", "name"=>"endDate")), $values);
}

function addInvoiceLineForm($customerID, $error = "", $values = null)
{
	return operationForm("addinvoiceline.php?id=$customerID", $error, _("Add invoice line"), _("Save"),
		array(
			array("title"=>_("Description"), "type"=>"text", "name"=>"description"),
			array("title"=>_("Price"), "type"=>"text", "name"=>"price"),
			array("title"=>_("Discount"), "type"=>"text", "name"=>"discount"),
			array("title"=>_("Revenue account"), "type"=>"dropdown", "name"=>"revenueAccountID", "options"=>accountingAccountOptions($GLOBALS["revenueDirectoryAccountID"], true)),
		),
		$values);
}

function sendInvoiceForm($customerID, $error = "", $values = null)
{
	if($values === null) {
		$values = array();
	}
	$lines = array();
	$lines[] = array("type"=>"colspan", "columns"=>array(
		array("type"=>"html", "html"=>"", "celltype"=>"th"),
		array("type"=>"html", "html"=>_("Description"), "celltype"=>"th", "fill"=>true),
		array("type"=>"html", "html"=>_("Price"), "celltype"=>"th"),
		array("type"=>"html", "html"=>_("Discount"), "celltype"=>"th"),
		array("type"=>"html", "html"=>_("Start date"), "celltype"=>"th"),
		array("type"=>"html", "html"=>_("End date"), "celltype"=>"th")
	));
	$subscriptionLineIDs = array();
	foreach(stdList("billingSubscriptionLine", array("customerID"=>$customerID), array("subscriptionLineID", "revenueAccountID", "description", "price", "discount", "periodStart", "periodEnd")) as $subscriptionLine) {
		if($error === null && !isset($values["subscriptionline-{$subscriptionLine["subscriptionLineID"]}"])) {
			continue;
		}
		$lines[] = array("type"=>"colspan", "columns"=>array(
			array("type"=>"checkbox", "name"=>"subscriptionline-{$subscriptionLine["subscriptionLineID"]}", "label"=>""),
			array("type"=>"html", "fill"=>true, "html"=>$subscriptionLine["description"]),
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>formatPrice($subscriptionLine["price"])),
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>formatPrice($subscriptionLine["discount"])),
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>$subscriptionLine["periodStart"] == null ? "-" : date("d-m-Y", $subscriptionLine["periodStart"])),
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>$subscriptionLine["periodEnd"] == null ? "-" : date("d-m-Y", $subscriptionLine["periodEnd"]))
		));
		$subscriptionLineIDs[] = $subscriptionLine["subscriptionLineID"];
	}
	$lines[] = array("type"=>"typechooser", "options"=>array(
		array("title"=>_("Delete"), "submitcaption"=>_("Delete"), "name"=>"delete", "summary"=>_("Delete selected subscription lines"), "subform"=>array()),
		array("title"=>_("Create invoice"), "submitcaption"=>_("Create Invoice"), "name"=>"create", "summary"=>_("Create and send an invoice with the selected subscription lines"), "subform"=>array(
			array("title"=>_("Send email"), "type"=>"checkbox", "name"=>"sendmail", "label"=>_("Send an email to the customer"))
		)),
	));
	
	$messages = array();
	if($error === null) {
		$messages["custom"] = "<p><a href=\"invoicepreview.php?customerID=$customerID&lines=" . implode(",", $subscriptionLineIDs) . "\">" . _("Preview invoice") . "</a></p>";
	}
	
	return operationForm("sendinvoice.php?id=$customerID", $error, _("Subscription lines"), _("Create Invoice"), $lines, $values, $messages);
}

function frequency($subscription)
{
	if($subscription["frequencyBase"] == "DAY") {
		return ($subscription["frequencyMultiplier"] == 1 ? _("per day") : sprintf(_("per %s days"), $subscription["frequencyMultiplier"]));
	} else if($subscription["frequencyBase"] == "MONTH") {
		return ($subscription["frequencyMultiplier"] == 1 ? _("per month") : sprintf(_("per %s months"), $subscription["frequencyMultiplier"]));
	} else if($subscription["frequencyBase"] == "YEAR") {
		return ($subscription["frequencyMultiplier"] == 1 ? _("per year") : sprintf(_("per %s years"), $subscription["frequencyMultiplier"]));
	} else {
		return _("unknown");
	}
}

function formatSubscriptionPrice($subscription)
{
	$percentageDiscount = billingBasePrice($subscription) * $subscription["discountPercentage"] / 100;
	$price = (int)(billingBasePrice($subscription) - $percentageDiscount - $subscription["discountAmount"]);
	return formatPrice($price) . " " . frequency($subscription);
}

?>