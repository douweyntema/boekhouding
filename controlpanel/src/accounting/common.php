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
		$accountTree = accountTree($rootNode);
		$accountList = array_merge($accountList, flattenAccountTree($accountTree));
	}
	
	
	$rows = array();
	foreach($accountList as $account) {
		$text = $account["name"];
		if($account["currencyName"] != $GLOBALS["defaultCurrency"]) {
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
	$defaultExpenseAccountName = stdGet("accountingAccount", array("accountID"=>$supplier["defaultExpenseAccountID"]), "name");
	return summaryTable("Supplier {$supplier["name"]}", array(
		"Balance"=>array("url"=>"account.php?id={$supplier["accountID"]}", "html"=>formatAccountPrice($supplier["accountID"])),
		"Default expences account"=>array("url"=>"account.php?id={$supplier["defaultExpenseAccountID"]}", "text"=>$defaultExpenseAccountName),
		"Description"=>$supplier["description"],
		));
}

function supplierInvoiceSummary($invoiceID)
{
	$invoice = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID", "transactionID", "invoiceNumber"));
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$invoice["supplierID"]), array("accountID", "name"));
	$transaction = stdGet("accountingTransaction", array("transactionID"=>$invoice["transactionID"]), array("date", "description"));
	
	$dateHtml = date("d-m-Y", $transaction["date"]);
	$hasPdf = !stdExists("suppliersInvoice", array("invoiceID"=>$invoiceID, "pdf"=>null));
	$amount = -1 * stdGet("accountingTransactionLine", array("transactionID"=>$invoice["transactionID"], "accountID"=>$supplier["accountID"]), "amount");
	
	$fields = array(
		"Supplier"=>array("url"=>"supplier.php?id={$invoice["supplierID"]}", "text"=>$supplier["name"]),
		"Invoice number"=>array("url"=>$hasPdf ? "supplierinvoicepdf.php?id={$invoiceID}" : null, "text"=>$invoice["invoiceNumber"]),
		"Date"=>array("text"=>$dateHtml),
		"Description"=>array("text"=>$transaction["description"]),
		"Amount"=>array("url"=>"transaction.php?id={$invoice["transactionID"]}", "html"=>formatPrice($amount)),
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
		$amount = -1 * stdGet("accountingTransactionLine", array("transactionID"=>$invoice["transactionID"], "accountID"=>$accountID), "amount");
		$rows[] = array("cells"=>array(
			array("url"=>"supplierinvoice.php?id=" . $invoice["invoiceID"], "text"=>date("d-m-Y", $transaction["date"])),
			array("url"=>"transaction.php?id={$invoice["transactionID"]}", "html"=>formatPrice($amount)),
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
	
	$dateHtml = date("d-m-Y", $transaction["date"]);
	$amount = stdGet("accountingTransactionLine", array("transactionID"=>$payment["transactionID"], "accountID"=>$supplier["accountID"]), "amount");
	
	$fields = array(
		"Supplier"=>array("url"=>"supplier.php?id={$payment["supplierID"]}", "text"=>$supplier["name"]),
		"Amount"=>array("url"=>"transaction.php?id={$payment["transactionID"]}", "html"=>formatPrice($amount)),
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
		$date = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), "date");
		$amount = stdGet("accountingTransactionLine", array("transactionID"=>$payment["transactionID"], "accountID"=>$accountID), "amount");
		$rows[] = array("cells"=>array(
			array("url"=>"supplierpayment.php?id=" . $payment["paymentID"], "text"=>date("d-m-Y", $date)),
			array("url"=>"transaction.php?id={$payment["transactionID"]}", "html"=>formatPrice($amount)),
		));
	}
	return listTable(array("Date", "Amount"), $rows, "Payments", true, "list sortable");
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
		$accountTree = accountTree($rootNode, $accountID);
		$accountList = array_merge($accountList, flattenAccountTree($accountTree));
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
	$lines = parseArrayField($_POST, array("accountID", "amount"));
	foreach($lines as $line) {
		if(isset($values["amount-" . $line[""]])) {
			$values["amount-" . $line[""]] = formatPriceRaw(parsePrice($values["amount-" . $line[""]]));
		}
	}
	
	$message = array();
	$rates1 = null;
	$rates2 = null;
	if($error === null && $balance !== null) {
		if($balance["type"] == "double") {
			$currency1 = stdGet("accountingCurrency", array("currencyID"=>$balance["rates"][0]["from"]), array("name", "symbol"));
			$currency2 = stdGet("accountingCurrency", array("currencyID"=>$balance["rates"][0]["to"]), array("name", "symbol"));
			
			$rates1 = array("title"=>"Exchange rate", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>formatPrice(100, $currency1["symbol"]) . " (" . $currency1["name"] . ")"),
				array("type"=>"html", "fill"=>true, "html"=>"<input type=\"text\" readonly=\"readonly\" value=\"" . formatPrice($balance["rates"][0]["rate"], $currency2["symbol"]) . " (" . $currency2["name"] . ")\" />")
			));
			$rates2 = array("title"=>"Exchange rate", "type"=>"colspan", "columns"=>array(
				array("type"=>"html", "html"=>formatPrice(100, $currency2["symbol"]) . " (" . $currency2["name"] . ")"),
				array("type"=>"html", "fill"=>true, "html"=>"<input type=\"text\" readonly=\"readonly\" value=\"" . formatPrice($balance["rates"][1]["rate"], $currency1["symbol"]) . " (" . $currency1["name"] . ")\" />")
			));
		} else if($balance["type"] == "multiple") {
			$message["custom"] = "<p class=\"warning\">Warning, the correctness of this transaction cannot be checked because there are 3 or more currencies involved!</p>";
		}
	}
	
	
	$fields = array(
		array("title"=>"Description", "type"=>"text", "name"=>"description"),
		array("title"=>"Date", "type"=>"text", "name"=>"date"),
		$rates1,
		$rates2,
		array("type"=>"array", "field"=>array("title"=>"Account", "type"=>"colspan", "columns"=>array(
			array("type"=>"dropdown", "name"=>"accountID", "options"=>accountOptions()),
			array("type"=>"text", "name"=>"amount", "fill"=>true),
		))),
	);
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
			array("title"=>"Default expense account", "type"=>"dropdown", "name"=>"defaultExpenseAccountID", "options"=>accountOptions()),
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
			array("title"=>"Default expense account", "type"=>"dropdown", "name"=>"defaultExpenseAccountID", "options"=>accountOptions()),
			array("title"=>"Description", "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function accountTree($accountID, $excludedAccountID = null)
{
	$accountIDSql = dbAddSlashes($accountID);
	$output = query("SELECT accountID, parentAccountID, accountingAccount.name AS name, description, isDirectory, balance, accountingCurrency.symbol AS currencySymbol, accountingCurrency.name AS currencyName FROM accountingAccount INNER JOIN accountingCurrency USING(currencyID) WHERE accountID = '$accountIDSql'")->fetchArray();
	$output["subaccounts"] = array();
	foreach(stdList("accountingAccount", array("parentAccountID"=>$accountID), "accountID") as $subAccountID) {
		if($excludedAccountID !== null && $subAccountID["accountID"] == $excludedAccountID) {
			continue;
		}
		$output["subaccounts"][] = accountTree($subAccountID);
	}
	return $output;
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

function flattenAccountTree($tree, $parentID = null, $depth = 0)
{
	$id = "account-" . $tree["accountID"];
	$output = array();
	
	$output[] = array_merge($tree, array("id"=>$id, "parentID"=>$parentID, "depth"=>$depth));
	foreach($tree["subaccounts"] as $account) {
		$output = array_merge($output, flattenAccountTree($account, $id, $depth + 1));
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

function accountOptions($allowEmpty = false)
{
	$rootNodes = stdList("accountingAccount", array("parentAccountID"=>null), "accountID");
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		$accountTree = accountTree($rootNode);
		$accountList = array_merge($accountList, flattenAccountTree($accountTree));
	}
	
	$accountOptions = array();
	if($allowEmpty) {
		$accountOptions[] = array("label"=>"", "value"=>0);
	}
	foreach($accountList as $account) {
		$accountOptions[] = array("label"=>str_repeat("&nbsp;&nbsp;&nbsp;", $account["depth"]) . $account["name"], "value"=>$account["accountID"], "disabled"=>$account["isDirectory"] ? true : false);
	}
	
	return $accountOptions;
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

?>