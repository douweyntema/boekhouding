<?php

require_once(dirname(__FILE__) . "/../common.php");

function doAccounting()
{
	useComponent("accounting");
	$GLOBALS["menuComponent"] = "accounting";
	useCustomer(0);
}

function doAccountingAccount($accountID)
{
	doAccounting();
	if($accountID != 0 && !stdExists("accountingAccount", array("accountID"=>$accountID))) {
		error404();
	}
}

function doAccountingTransaction($transactionID)
{
	if(!stdExists("accountingTransaction", array("transactionID"=>$transactionID))) {
		error404();
	}
	doAccounting();
}

function doAccountingSupplier($supplierID)
{
	if(!stdExists("suppliersSupplier", array("supplierID"=>$supplierID))) {
		error404();
	}
	doAccounting();
}

function doAccountingInvoice($invoiceID)
{
	if(!stdExists("suppliersInvoice", array("invoiceID"=>$invoiceID))) {
		error404();
	}
	doAccounting();
}

function doAccountingPayment($paymentID)
{
	if(!stdExists("suppliersPayment", array("paymentID"=>$paymentID))) {
		error404();
	}
	doAccounting();
}


function crumb($name, $filename)
{
	return array("name"=>$name, "url"=>"{$GLOBALS["root"]}accounting/$filename");
}

function crumbs($name, $filename)
{
	return array(crumb($name, $filename));
}

function accountingBreadcrumbs()
{
	return crumbs("Accounting", "");
}

function accountBreadcrumbs($accountID)
{
	if($accountID == 0) {
		return accountingBreadcrumbs();
	}
	$name = stdGet("accountingAccount", array("accountID"=>$accountID), "name");
	return array_merge(accountingBreadcrumbs(), crumbs("Account " . $name, "account.php?id=$accountID"));
}

function transactionBreadcrumbs($transactionID, $accountID)
{
	$date = stdGet("accountingTransaction", array("transactionID"=>$transactionID), "date");
	return array_merge(accountBreadcrumbs($accountID), crumbs("Transaction on " . date("d-m-Y", $date), "transaction.php?id=$transactionID&accountID=$accountID"));
}

function supplierBreadcrumbs($supplierID)
{
	$name = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "name");
	return array_merge(accountingBreadcrumbs(), crumbs("Supplier " . $name, "supplier.php?id=$supplierID"));
}

function suppliersInvoiceBreadcrumbs($invoiceID)
{
	$invoice = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID", "invoiceNumber"));
	return array_merge(supplierBreadcrumbs($invoice["supplierID"]), crumbs("Invoice " . $invoice["invoiceNumber"], "supplierinvoice.php?id=$invoiceID"));
}

function suppliersPaymentBreadcrumbs($paymentID)
{
	$payment = stdGet("suppliersPayment", array("paymentID"=>$paymentID), array("supplierID", "transactionID"));
	$date = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), "date");
	return array_merge(supplierBreadcrumbs($payment["supplierID"]), crumbs("Payment on " . date("d-m-Y", $date), "supplierpayment.php?id=$paymentID"));
}

function accountSummary($accountID)
{
	$account = stdGet("accountingAccount", array("accountID"=>$accountID), array("parentAccountID", "currencyID", "name", "description", "isDirectory"));
	$currency = stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), array("name", "symbol"));
	
	$rows = array();
	if($account["parentAccountID"] !== null) {
		$parentAccountName = stdGet("accountingAccount", array("accountID"=>$account["parentAccountID"]), "name");
		$rows["Parent account"] = array("url"=>"account.php?id=" . $account["parentAccountID"], "text"=>$parentAccountName);
	}
	$rows["Currency"] = array("html"=>$currency["name"] . " (" . $currency["symbol"] . ")");
	$rows["Balance"] = array("html"=>formatAccountPrice($accountID));
	$rows["Description"] = $account["description"];
	
	return summaryTable("Account {$account["name"]}", $rows);
}

function accountList()
{
	$rootNodes = stdList("accountingAccount", array("parentAccountID"=>null), "accountID");
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		$accountTree = accountingAccountTree($rootNode);
		$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
	}
	
	
	$rows = array();
	foreach($accountList as $account) {
		$text = $account["name"];
		if($account["currencyID"] != $GLOBALS["defaultCurrencyID"]) {
			$text .= " (" . $account["currencyName"] . ")";
		}
		$rows[] = array("id"=>$account["id"], "class"=>($account["parentID"] === null ? null : "child-of-account-{$account["parentAccountID"]}"), "cells"=>array(
			array("url"=>"account.php?id={$account["accountID"]}", "text"=>$text),
			array("html"=>formatAccountPrice($account["accountID"])),
		));
	}
	return listTable(array("Account", "Balance"), $rows, "Accounts", true, "list tree");
}

function transactionList($accountID)
{
	$transactions = transactions($accountID);
	uasort($transactions, function($a, $b) {
		if($a["date"] == $b["date"]) {
			return $a["transactionID"] - $b["transactionID"];
		}
		return $a["date"] - $b["date"];
	});
	$rows = array();
	$balance = 0;
	$subAccounts = subAccountList($accountID);
	
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	$currencySymbol = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	
	foreach($transactions as $transaction) {
		$lines = stdList("accountingTransactionLine", array("transactionID"=>$transaction["transactionID"]), array("transactionLineID", "accountID", "amount"));
		
		$currentLineAmount = 0;
		foreach($lines as $line) {
			if(in_array($line["accountID"], $subAccounts)) {
				$currentLineAmount += $line["amount"];
			}
		}
		
		$balance += $currentLineAmount;
		
		$rows[] = array("id"=>"transaction-{$transaction["transactionID"]}", "class"=>"transaction", "cells"=>array(
			array("text"=>date("d-m-Y", $transaction["date"])),
			array("text"=>$transaction["description"], "url"=>"transaction.php?id={$transaction["transactionID"]}&accountID={$accountID}"),
			array("html"=>formatPrice($currentLineAmount, $currencySymbol)),
			array("html"=>formatPrice($balance, $currencySymbol)),
		));
		
		foreach($lines as $line) {
			$account = stdGet("accountingAccount", array("accountID"=>$line["accountID"]), array("name", "currencyID"));
			$lineCurrencySymbol = stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), "symbol");
			$rows[] = array("id"=>"transactionline-" . $line["transactionLineID"], "class"=>"child-of-transaction-{$transaction["transactionID"]} transactionline", "cells"=>array(
				array("text"=>""),
				array("url"=>"account.php?id={$line["accountID"]}", "text"=>$account["name"]),
				array("html"=>formatPrice($line["amount"], $lineCurrencySymbol)),
				array("text"=>""),
			));
		}
	}
	return listTable(array("Date", "Description", "Amount", "Balance"), $rows, null, true, "list tree");
}

function transactionSummary($transactionID)
{
	$transaction = stdGet("accountingTransaction", array("transactionID"=>$transactionID), array("date", "description"));
	$lines = stdList("accountingTransactionLine", array("transactionID"=>$transactionID), array("transactionLineID", "accountID", "amount"));
	
	$rows[] = array("id"=>"transaction-{$transactionID}", "class"=>"transaction", "cells"=>array(
		array("text"=>date("d-m-Y", $transaction["date"])),
		array("text"=>$transaction["description"], "url"=>"transaction.php?id={$transactionID}"),
		array("text"=>""),
	));
	
	foreach($lines as $line) {
		$account = stdGet("accountingAccount", array("accountID"=>$line["accountID"]), array("name", "currencyID"));
		$lineCurrencySymbol = stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), "symbol");
		$rows[] = array("class"=>"transactionline", "cells"=>array(
			array("text"=>""),
			array("url"=>"account.php?id={$line["accountID"]}", "text"=>$account["name"]),
			array("html"=>formatPrice($line["amount"], $lineCurrencySymbol)),
		));
	}
	return listTable(array("Date", "Description", "Amount"), $rows, null, true, "list");
}

function supplierList()
{
	$suppliers = stdList("suppliersSupplier", array(), array("supplierID", "accountID", "name", "description"));
	
	$rows = array();
	foreach($suppliers as $supplier) {
		$rows[] = array("cells"=>array(
			array("url"=>"supplier.php?id={$supplier["supplierID"]}", "text"=>$supplier["name"]),
			array("text"=>$supplier["description"]),
			array("url"=>"account.php?id={$supplier["accountID"]}", "html"=>formatAccountPrice($supplier["accountID"])),
		));
	}
	return listTable(array("Name", "Description", "Balance"), $rows, "Suppliers", true, "list sortable");
}

function supplierSummary($supplierID)
{
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), array("accountID", "defaultExpenseAccountID", "name", "description"));
	if($supplier["defaultExpenseAccountID"] !== null) {
		$defaultExpenseAccountName = stdGet("accountingAccount",
		array("accountID"=>$supplier["defaultExpenseAccountID"]), "name");
	}
	$currencyID = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "currencyID");
	
	$rows = array();
	if($currencyID != $GLOBALS["defaultCurrencyID"]) {
		$currency = stdGet("accountingCurrency", array("currencyID"=>$currencyID), array("name", "symbol"));
		$rows["Currency"] = array("html"=>$currency["name"] . " (" . $currency["symbol"] . ")");
	}
	$rows["Balance"] = array("url"=>"account.php?id={$supplier["accountID"]}", "html"=>formatAccountPrice($supplier["accountID"]));
	if($supplier["defaultExpenseAccountID"] !== null) {
		$rows["Default expences account"] = array("url"=>"account.php?id={$supplier["defaultExpenseAccountID"]}", "text"=>$defaultExpenseAccountName);
	} else {
		$rows["Default expences account"] = array("html"=>"<i>None</i>");
	}
	$rows["Description"] = $supplier["description"];
	
	return summaryTable("Supplier {$supplier["name"]}", $rows);
}

function supplierInvoiceSummary($invoiceID)
{
	$invoice = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID", "transactionID", "invoiceNumber"));
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$invoice["supplierID"]), array("accountID", "name"));
	$transaction = stdGet("accountingTransaction", array("transactionID"=>$invoice["transactionID"]), array("date", "description"));
	
	$dateHtml = date("d-m-Y", $transaction["date"]);
	$hasPdf = !stdExists("suppliersInvoice", array("invoiceID"=>$invoiceID, "pdf"=>null));
	
	$amountHtml = calculateTransactionAmount($invoice["transactionID"], $supplier["accountID"], true);
	
	$fields = array(
		"Supplier"=>array("url"=>"supplier.php?id={$invoice["supplierID"]}", "text"=>$supplier["name"]),
		"Invoice number"=>array("text"=>$invoice["invoiceNumber"]),
		"Pdf"=>array("url"=>$hasPdf ? "supplierinvoicepdf.php?id={$invoiceID}" : null, "text"=>$hasPdf ? "Yes" : "No"),
		"Date"=>array("text"=>$dateHtml),
		"Description"=>array("text"=>$transaction["description"]),
		"Amount"=>array("url"=>"transaction.php?id={$invoice["transactionID"]}", "html"=>$amountHtml),
	);
	
	return summaryTable("Invoice {$invoice["invoiceNumber"]}", $fields);
}

function supplierInvoiceList($supplierID)
{
	$invoices = stdList("suppliersInvoice", array("supplierID"=>$supplierID), array("invoiceID", "transactionID", "invoiceNumber"));
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	$currencySymbol = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	$rows = array();
	foreach($invoices as $invoice) {
		$transaction = stdGet("accountingTransaction", array("transactionID"=>$invoice["transactionID"]), array("date", "description"));
		$hasPdf = !stdExists("suppliersInvoice", array("invoiceID"=>$invoice["invoiceID"], "pdf"=>null));
		$amountHtml = calculateTransactionAmount($invoice["transactionID"], $accountID, true);
		$rows[] = array("cells"=>array(
			array("url"=>"supplierinvoice.php?id=" . $invoice["invoiceID"], "text"=>date("d-m-Y", $transaction["date"])),
			array("url"=>"transaction.php?id={$invoice["transactionID"]}", "html"=>$amountHtml),
			array("url"=>$hasPdf ? "supplierinvoicepdf.php?id={$invoice["invoiceID"]}" : null, "text"=>$invoice["invoiceNumber"]),
			array("text"=>$transaction["description"]),
		));
	}
	return listTable(array("Date", "Amount", "Invoice number", "Description"), $rows, "Invoices", true, "list sortable");
}

function supplierPaymentSummary($paymentID)
{
	$payment = stdGet("suppliersPayment", array("paymentID"=>$paymentID), array("supplierID", "transactionID"));
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$payment["supplierID"]), array("accountID", "name", "description"));
	$transaction = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), array("date", "description"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "currencyID");
	$currencySymbol = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	
	$dateHtml = date("d-m-Y", $transaction["date"]);
	$amountHtml = calculateTransactionAmount($payment["transactionID"], $supplier["accountID"]);
	
	$fields = array(
		"Supplier"=>array("url"=>"supplier.php?id={$payment["supplierID"]}", "text"=>$supplier["name"]),
		"Amount"=>array("url"=>"transaction.php?id={$payment["transactionID"]}", "html"=>$amountHtml),
		"Date"=>array("text"=>$dateHtml),
		"Description"=>array("text"=>$transaction["description"]),
	);
	
	return summaryTable("Payment on " . $dateHtml, $fields);
}

function supplierPaymentList($supplierID)
{
	$payments = stdList("suppliersPayment", array("supplierID"=>$supplierID), array("paymentID", "transactionID"));
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	$currencySymbol = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	$rows = array();
	foreach($payments as $payment) {
		$transaction = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), array("date", "description"));
		$amountHtml = calculateTransactionAmount($payment["transactionID"], $accountID);
		$rows[] = array("cells"=>array(
			array("url"=>"supplierpayment.php?id=" . $payment["paymentID"], "text"=>date("d-m-Y", $transaction["date"])),
			array("url"=>"transaction.php?id={$payment["transactionID"]}", "html"=>$amountHtml),
			array("text"=>$transaction["description"]),
		));
	}
	return listTable(array("Date", "Amount", "Description"), $rows, "Payments", true, "list sortable");
}

function addAccountForm($accountID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("addaccount.php?id=$accountID", "", "Add account", "Add Account", array(), array());
	}
	
	return operationForm("addaccount.php?id=$accountID", $error, "Add account", "Add Account",
		array(
			array("title"=>"Name", "type"=>"text", "name"=>"name"),
			array("title"=>"Currency", "type"=>"dropdown", "name"=>"currencyID", "options"=>currencyOptions()),
			array("title"=>"Type", "type"=>"radio", "name"=>"type", "options"=>array(
				array("label"=>"Account", "value"=>"account"),
				array("label"=>"Directory", "value"=>"directory")
			)),
			array("title"=>"Description", "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function editAccountForm($accountID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("editaccount.php?id=$accountID", "", "Edit account", "Edit Account", array(), array());
	}
	
	if($values === null || $error === "") {
		$values = stdGet("accountingAccount", array("accountID"=>$accountID), array("name", "description"));
	}
	
	return operationForm("editaccount.php?id=$accountID", $error, "Edit account", "Save",
		array(
			array("title"=>"Name", "type"=>"text", "name"=>"name"),
			array("title"=>"Description", "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function moveAccountForm($accountID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("moveaccount.php?id=$accountID", "", "Move account", "Move Account", array(), array());
	}
	
	$rootNodes = stdList("accountingAccount", array("parentAccountID"=>null), "accountID");
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		if($rootNode == $accountID) {
			continue;
		}
		$accountTree = accountingAccountTree($rootNode, $accountID);
		$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
	}
	
	$options = array();
	$options[] = array("label"=>"Top-level Account", "value"=>"0");
	foreach($accountList as $account) {
		if($account["isDirectory"]) {
			$options[] = array("label"=>str_repeat("&nbsp;&nbsp;&nbsp;", $account["depth"] + 1) . $account["name"], "value"=>$account["accountID"]);
		}
	}
	
	if($values === null || $error === "") {
		$values = array("parentAccountID"=>stdGet("accountingAccount", array("accountID"=>$accountID), "parentAccountID"));
	}
	
	return operationForm("moveaccount.php?id=$accountID", $error, "Move account", "Save", 
		array(
			array("title"=>"Move To", "type"=>"dropdown", "name"=>"parentAccountID", "options"=>$options)
		),
		$values);
}

function deleteAccountForm($accountID, $error = "", $values = null)
{
	return operationForm("deleteaccount.php?id=$accountID", $error, "Delete account", "Delete", array(), $values);
}

function transactionForm($error = "", $values = null, $balance = null)
{
	if(isset($values["date"])) {
		$values["date"] = date("d-m-Y", parseDate($values["date"]));
	}
	$lines = parseArrayField($values, array("accountID", "amount"));
	foreach($lines as $line) {
		normalizePrice($values, "amount-" . $line[""]);
	}
	
	$fields = array();
	$fields[] = array("title"=>"Description", "type"=>"text", "name"=>"description");
	$fields[] = array("title"=>"Date", "type"=>"text", "name"=>"date");
	
	$message = array();
	if($error === null && $balance !== null) {
		if($balance["type"] == "double") {
			$fields = array_merge($fields, transactionExchangeRates($balance));
		} else if($balance["type"] == "multiple") {
			$message["custom"] = "<p class=\"warning\">Warning, the correctness of this transaction cannot be checked because there are 3 or more currencies involved!</p>";
		}
	}
	
	$fields[] = array("type"=>"array", "field"=>array("title"=>"Account", "type"=>"colspan", "columns"=>array(
			array("type"=>"dropdown", "name"=>"accountID", "options"=>accountingAccountOptions(null, true)),
			array("type"=>"text", "name"=>"amount", "fill"=>true),
		)));
	return array("fields"=>$fields, "values"=>$values, "message"=>$message);
}

function addTransactionForm($accountID, $error = "", $values = null, $balance = null)
{
	if($values === null) {
		$values = array("date"=>date("d-m-Y"), "accountID-0"=>$accountID);
	}
	
	$formContent = transactionForm($error, $values, $balance);
	
	return operationForm("addtransaction.php?id=$accountID", $error, "New transaction", "Save", $formContent["fields"], $formContent["values"], $formContent["message"]);
}

function editTransactionForm($transactionID, $accountID, $error = "", $values = null, $balance = null)
{
	if($values === null) {
		$transaction = stdGet("accountingTransaction", array("transactionID"=>$transactionID), array("date", "description"));
		$lines = stdList("accountingTransactionLine", array("transactionID"=>$transactionID), array("accountID", "amount"));
		
		$values = array("date"=>date("d-m-Y", $transaction["date"]), "description"=>$transaction["description"]);
		$index = 0;
		foreach($lines as $line) {
			$values["accountID-$index"] = $line["accountID"];
			$values["amount-$index"] = formatPriceRaw($line["amount"]);
			$index++;
		}
	}
	
	$formContent = transactionForm($error, $values, $balance);
	
	return operationForm("edittransaction.php?id=$transactionID&accountID=$accountID", $error, "Edit transaction", "Save", $formContent["fields"], $formContent["values"], $formContent["message"]);
}

function deleteTransactionForm($transactionID, $accountID, $error = "", $values = null)
{
	return operationForm("deletetransaction.php?id=$transactionID&accountID=$accountID", $error, "Delete transaction", "Delete", array(), $values);
}

function addSupplierForm($error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("addsupplier.php", $error, "Add supplier", "Add Supplier", array(), array());
	}
	
	return operationForm("addsupplier.php", $error, "Add supplier", "Add Supplier",
		array(
			array("title"=>"Name", "type"=>"text", "name"=>"name"),
			array("title"=>"Currency", "type"=>"dropdown", "name"=>"currencyID", "options"=>currencyOptions()),
			array("title"=>"Default expense account", "type"=>"dropdown", "name"=>"defaultExpenseAccountID", "options"=>accountingAccountOptions($GLOBALS["expensesDirectoryAccountID"], true)),
			array("title"=>"Description", "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function editSupplierForm($supplierID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("editsupplier.php?id=$supplierID", $error, "Edit supplier", "Edit Supplier", array(), array());
	}
	
	if($values === null || $error === "") {
		$values = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), array("name", "defaultExpenseAccountID", "description"));
	}
	
	return operationForm("editsupplier.php?id=$supplierID", $error, "Edit supplier", "Save",
		array(
			array("title"=>"Name", "type"=>"text", "name"=>"name"),
			array("title"=>"Default expense account", "type"=>"dropdown", "name"=>"defaultExpenseAccountID", "options"=>accountingAccountOptions($GLOBALS["expensesDirectoryAccountID"], true)),
			array("title"=>"Description", "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function deleteSupplierForm($supplierID, $error = "", $values = null)
{
	return operationForm("deletesupplier.php?id=$supplierID", $error, "Delete supplier", "Delete", array(), $values);
}

function supplierInvoiceForm($supplierID, $fileLink, $error = "", $values = null, $total = null, $balance = null)
{
	$accountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID");
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	$currency = stdGet("accountingCurrency", array("currencyID"=>$currencyID), array("name", "symbol"));
	$defaultCurrency = stdGet("accountingCurrency", array("currencyID"=>$GLOBALS["defaultCurrencyID"]), array("name", "symbol"));
	
	$lines = parseArrayField($values, array("accountID", "amount"));
	foreach($lines as $line) {
		normalizePrice($values, "amount-" . $line[""]);
	}
	normalizePrice($values, "foreignAmount");
	normalizePrice($values, "taxAmount");
	
	$fields = array();
	$fields[] = array("title"=>"Invoice number", "type"=>"text", "name"=>"invoiceNumber");
	$fields[] = array("title"=>"Description", "type"=>"text", "name"=>"description");
	$fields[] = array("title"=>"Date", "type"=>"text", "name"=>"date");
	
	$subforms = array();
	if($fileLink !== null) {
		$fileLinkHtml = htmlentities($fileLink);
		$subforms[] = array("value"=>"current", "label"=>"Keep <a href=\"$fileLinkHtml\">current file</a>", "subform"=>array());
	}
	$subforms[] = array("value"=>"none", "label"=>"None", "subform"=>array());
	$subforms[] = array("value"=>"new", "label"=>"Upload new file", "subform"=>array(
		array("title"=>"File", "type"=>"file", "name"=>"file", "accept"=>"application/pdf")
	));
	$fields[] = array("title"=>"Pdf", "type"=>"subformchooser", "name"=>"pdfType", "subforms"=>$subforms);
	
	if($currencyID != $GLOBALS["defaultCurrencyID"]) {
		$fields[] = array("title"=>"Total in {$currency["name"]}", "type"=>"text", "name"=>"foreignAmount");
	}
	if($error === null && $total !== null) {
		$totalHtml = formatPriceRaw($total, $defaultCurrency["symbol"]);
		$fields[] = array("title"=>"Total in {$defaultCurrency["name"]}", "type"=>"html", "html"=>"<input type=\"text\" value=\"$totalHtml\" readonly=\"readonly\" />");
		if($currencyID != $GLOBALS["defaultCurrencyID"]) {
			$fields = array_merge($fields, transactionExchangeRates($balance));
		}
	}
	$fields[] = array("title"=>"Tax", "type"=>"colspan", "columns"=>array(
		array("type"=>"html", "html"=>""),
		array("type"=>"html", "html"=>htmlentities($defaultCurrency["name"])),
		array("type"=>"text", "name"=>"taxAmount", "fill"=>true)
	));
	$fields[] = array("type"=>"array", "field"=>array("title"=>"Line", "type"=>"colspan", "columns"=>array(
		array("type"=>"dropdown", "name"=>"accountID", "options"=>accountingAccountOptions($GLOBALS["expensesDirectoryAccountID"], true)),
		array("type"=>"html", "html"=>htmlentities($defaultCurrency["name"])),
		array("type"=>"text", "name"=>"amount", "fill"=>true)
	)));
	
	return array("fields"=>$fields, "values"=>$values);
}

function addSupplierInvoiceForm($supplierID, $error = "", $values = null, $total = null, $balance = null)
{
	if($values === null) {
		$values = array();
		$values["pdfType"] = "none";
		
		$defaultExpenseAccountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "defaultExpenseAccountID");
		if($defaultExpenseAccountID !== null) {
			$values["accountID-0"] = $defaultExpenseAccountID;
		}
	}
	
	$formContent = supplierInvoiceForm($supplierID, null, $error, $values, $total, $balance);
	
	return operationForm("addsupplierinvoice.php?id=$supplierID", $error, "Add invoice", "Add Invoice", $formContent["fields"], $formContent["values"]);
}

function editSupplierInvoiceForm($invoiceID, $error = "", $values = null, $total = null, $balance = null)
{
	$invoice = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID", "transactionID", "invoiceNumber"));
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$invoice["supplierID"]), array("accountID", "name"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "currencyID");
	$hasPdf = !stdExists("suppliersInvoice", array("invoiceID"=>$invoiceID, "pdf"=>null));
	
	if($values === null) {
		$transaction = stdGet("accountingTransaction", array("transactionID"=>$invoice["transactionID"]), array("date", "description"));
		$lines = stdList("accountingTransactionLine", array("transactionID"=>$invoice["transactionID"]), array("accountID", "amount"));
		
		$values = array(
			"invoiceNumber"=>$invoice["invoiceNumber"],
			"description"=>$transaction["description"],
			"date"=>date("d-m-Y", $transaction["date"]),
			"pdfType"=>$hasPdf ? "current" : "none",
		);
		
		$index = 0;
		foreach($lines as $line) {
			if($line["accountID"] == $supplier["accountID"]) {
				$values["foreignAmount"] = formatPriceRaw(-1 * $line["amount"]);
			} else if($line["accountID"] == $GLOBALS["taxReceivableAccountID"]) {
				$values["taxAmount"] = formatPriceRaw($line["amount"]);
			} else {
				$values["accountID-$index"] = $line["accountID"];
				$values["amount-$index"] = formatPriceRaw($line["amount"]);
				$index++;
			}
		}
	}
	
	$formContent = supplierInvoiceForm($invoice["supplierID"], $hasPdf ? "supplierinvoicepdf.php?id=$invoiceID" : null, $error, $values, $total, $balance);
	
	return operationForm("editsupplierinvoice.php?id=$invoiceID", $error, "Edit invoice", "Save", $formContent["fields"], $formContent["values"]);
}

function deleteSupplierInvoiceForm($invoiceID, $error = "", $values = null)
{
	return operationForm("deletesupplierinvoice.php?id=$invoiceID", $error, "Delete invoice", "Delete", array(), $values);
}

function supplierPaymentForm($supplierID, $error = "", $values = null, $balance = null)
{
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), array("accountID", "name"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "currencyID");
	
	$bankAccounts = accountingAccountOptions($GLOBALS["bankDirectoryAccountID"]);
	
	$fields = array();
	if($GLOBALS["defaultCurrencyID"] != $currencyID) {
		$defaultCurrency = stdGet("accountingCurrency", array("currencyID"=>$GLOBALS["defaultCurrencyID"]), "name");
		$strangeCurrency = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "name");
		$fields[] = array("title"=>"Amount ($defaultCurrency)", "type"=>"text", "name"=>"amount");
		$fields[] = array("title"=>"Amount ($strangeCurrency)", "type"=>"text", "name"=>"foreignAmount");
		
		if($error === null && $balance !== null) {
			$fields = array_merge($fields, transactionExchangeRates($balance));
		}
	} else {
		$fields[] = array("title"=>"Amount", "type"=>"text", "name"=>"amount");
	}
	$fields[] = array("title"=>"Date", "type"=>"text", "name"=>"date");
	$fields[] = array("title"=>"Description", "type"=>"text", "name"=>"description");
	$fields[] = array("title"=>"Bank account", "type"=>"dropdown", "name"=>"bankAccount", "options"=>$bankAccounts);
	return $fields;
}

function addSupplierPaymentForm($supplierID, $error = "", $values = null, $balance = null)
{
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), array("accountID", "name"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "currencyID");
	
	if($values === null) {
		$values = array("date"=>date("d-m-Y"), "description"=>"Payment for {$supplier["name"]}");
		if(isset($GLOBALS["bankDefaultAccountID"])) {
			$values["bankAccount"] = $GLOBALS["bankDefaultAccountID"];
		}
		$balance = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "balance");
		if($balance < 0) {
			$amount = formatPriceRaw(-1 * $balance);
			if($GLOBALS["defaultCurrencyID"] != $currencyID) {
				$values["foreignAmount"] = $amount;
			} else {
				$values["amount"] = $amount;
			}
		}
	}
	
	$fields = supplierPaymentForm($supplierID, $error, $values);
	
	return operationForm("addsupplierpayment.php?id=$supplierID", $error, "Add payment", "Save", $fields, $values);
}

function editSupplierPaymentForm($paymentID, $error = "", $values = null, $balance = null)
{
	$payment = stdGet("suppliersPayment", array("paymentID"=>$paymentID), array("supplierID", "transactionID"));
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$payment["supplierID"]), array("accountID", "name"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "currencyID");
	
	if($values === null) {
		$transaction = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), array("date", "description"));
		$lines = stdList("accountingTransactionLine", array("transactionID"=>$payment["transactionID"]), array("accountID", "amount"));
		foreach($lines as $line) {
			if($line["accountID"] == $supplier["accountID"]) {
				$foreignAmount = $line["amount"];
			} else {
				$bankAccountID = $line["accountID"];
				$amount = -1 * $line["amount"];
			}
		}
		$values = array(
			"amount"=>formatPriceRaw($amount),
			"foreignAmount"=>formatPriceRaw($foreignAmount),
			"bankAccount"=>$bankAccountID,
			"date"=>date("d-m-Y", $transaction["date"]),
			"description"=>$transaction["description"],
		);
	}
	
	$fields = supplierPaymentForm($payment["supplierID"], $error, $values);
	
	return operationForm("editsupplierpayment.php?id=$paymentID", $error, "Edit payment", "Save", $fields, $values);
}

function deleteSupplierPaymentForm($paymentID, $error = "", $values = null)
{
	return operationForm("deletesupplierpayment.php?id=$paymentID", $error, "Delete payment", "Delete", array(), $values);
}

function transactionExchangeRates($balance)
{
	$currency1 = stdGet("accountingCurrency", array("currencyID"=>$balance["rates"][0]["from"]), array("name", "symbol"));
	$currency2 = stdGet("accountingCurrency", array("currencyID"=>$balance["rates"][0]["to"]), array("name", "symbol"));
	
	$rates1 = array("title"=>"Exchange rate", "type"=>"colspan", "columns"=>array(
		array("type"=>"html", "cellclass"=>"nowrap", "html"=>formatPrice(100, $currency1["symbol"]) . " (" . $currency1["name"] . ")"),
		array("type"=>"html", "fill"=>true, "html"=>"<input type=\"text\" readonly=\"readonly\" value=\"" . formatPrice($balance["rates"][0]["rate"], $currency2["symbol"]) . " (" . $currency2["name"] . ")\" />")
	));
	$rates2 = array("title"=>"Exchange rate", "type"=>"colspan", "columns"=>array(
		array("type"=>"html", "cellclass"=>"nowrap", "html"=>formatPrice(100, $currency2["symbol"]) . " (" . $currency2["name"] . ")"),
		array("type"=>"html", "fill"=>true, "html"=>"<input type=\"text\" readonly=\"readonly\" value=\"" . formatPrice($balance["rates"][1]["rate"], $currency1["symbol"]) . " (" . $currency1["name"] . ")\" />")
	));
	return array($rates1, $rates2);
}

function transactions($accountID)
{
	if(stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory") == 0) {
		return query("SELECT transactionID, date, description FROM accountingTransaction INNER JOIN accountingTransactionLine USING(transactionID) WHERE accountID = '" . dbAddSlashes($accountID) . "'")->fetchMap("transactionID");
	} else {
		$output = array();
		$subAccounts = stdList("accountingAccount", array("parentAccountID"=>$accountID), "accountID");
		foreach($subAccounts as $subAccountID) {
			$transactions = transactions($subAccountID);
			foreach($transactions as $transactionID=>$transaction) {
				$output[$transactionID] = $transaction;
			}
		}
		return $output;
	}
}

function subAccountList($accountID, $currencyID = null)
{
	$output = array($accountID);
	if($currencyID === null) {
		$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	}
	$subAccounts = stdList("accountingAccount", array("parentAccountID"=>$accountID, "currencyID"=>$currencyID), "accountID");
	foreach($subAccounts as $subAccount) {
		$output = array_merge($output, subAccountList($subAccount, $currencyID));
	}
	return $output;
}

function currencyOptions()
{
	$output = array();
	foreach(stdList("accountingCurrency", array(), array("currencyID", "name")) as $currency) {
		$output[] = array("label"=>$currency["name"], "value"=>$currency["currencyID"]);
	}
	return $output;
}

function calculateTransactionAmount($transactionID, $accountID, $negate = false)
{
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	$currencySymbol = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	if($currencyID != $GLOBALS["defaultCurrencyID"]) {
		$lines = stdList("accountingTransactionLine", array("transactionID"=>$transactionID), array("accountID", "amount"));
		$amount = 0;
		foreach($lines as $line) {
			if($line["accountID"] == $accountID) {
				$foreignAmount = $line["amount"];
			} else {
				$amount += -1 * $line["amount"];
			}
		}
		if($negate) {
			$foreignAmount = -1 * $foreignAmount;
			$amount = -1 * $amount;
		}
		$amountHtml = formatPrice($amount) . " / " . formatPrice($foreignAmount, $currencySymbol);
	} else {
		$amount = stdGet("accountingTransactionLine", array("transactionID"=>$transactionID, "accountID"=>$accountID), "amount");
		if($negate) {
			$amount = -1 * $amount;
		}
		$amountHtml = formatPrice($amount);
	}
	return $amountHtml;
}

function formatAccountPrice($accountID)
{
	$account = stdGet("accountingAccount", array("accountID"=>$accountID), array("balance", "currencyID"));
	$currency = stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), "symbol");
	return formatPrice($account["balance"], $currency);
}

function supplierAccountDescription($name)
{
	return "Supplier account for supplier $name";
}

function accountEmpty($accountID)
{
	return
		!stdExists("accountingTransactionLine", array("accountID"=>$accountID)) &&
		!stdExists("accountingAccount", array("parentAccountID"=>$accountID));

}

function supplierEmpty($supplierID)
{
	return
		!stdExists("suppliersInvoice", array("supplierID"=>$supplierID)) &&
		!stdExists("suppliersPayment", array("supplierID"=>$supplierID)) &&
		accountEmpty(stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "accountID"));
}




?>