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
	doAccounting();
	if(!stdExists("accountingTransaction", array("transactionID"=>$transactionID))) {
		error404();
	}
}

function doAccountingSupplier($supplierID)
{
	doAccounting();
	if(!stdExists("suppliersSupplier", array("supplierID"=>$supplierID))) {
		error404();
	}
}

function doAccountingInvoice($invoiceID)
{
	doAccounting();
	if(!stdExists("suppliersInvoice", array("invoiceID"=>$invoiceID))) {
		error404();
	}
}

function doAccountingPayment($paymentID)
{
	doAccounting();
	if(!stdExists("suppliersPayment", array("paymentID"=>$paymentID))) {
		error404();
	}
}

function doAccountingFixedAsset($fixedAssetID)
{
	doAccounting();
	if(!stdExists("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID))) {
		error404();
	}
}

function doAccountingBalanceView($balanceViewID)
{
	doAccounting();
	if(!stdExists("accountingBalanceView", array("balanceViewID"=>$balanceViewID))) {
		error404();
	}
}

function doAccountingIncomeExpenseView($incomeExpenseViewID)
{
	doAccounting();
	if(!stdExists("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$incomeExpenseViewID))) {
		error404();
	}
}

function doAccountingCar($carID)
{
	doAccounting();
	if(!stdExists("accountingCar", array("carID"=>$carID))) {
		error404();
	}
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
	return crumbs(_("Accounting"), "");
}

function accountBreadcrumbs($accountID, $toDate = null, $fromDate = null, $balanceViewID = null, $incomeExpenseViewID = null)
{
	if($accountID == 0) {
		return accountingBreadcrumbs();
	}
	$name = stdGet("accountingAccount", array("accountID"=>$accountID), "name");
	$url = "account.php?id=$accountID";
	if($toDate !== null) {
		$url .= "&toDate=$toDate";
	}
	if($fromDate !== null) {
		$url .= "&fromDate=$fromDate";
	}
	if($balanceViewID !== null) {
		$url .= "&balanceViewID=$balanceViewID";
	}
	if($incomeExpenseViewID !== null) {
		$url .= "&incomeExpenseViewID=$incomeExpenseViewID";
	}
	
	if($balanceViewID !== null) {
		return array_merge(balanceViewBreadcrumbs($balanceViewID), crumbs(sprintf(_("Account %s"), $name), $url));
	} else if($incomeExpenseViewID !== null) {
		return array_merge(incomeExpenseViewBreadcrumbs($incomeExpenseViewID), crumbs(sprintf(_("Account %s"), $name), $url));
	} else {
		return array_merge(accountingBreadcrumbs(), crumbs(sprintf(_("Account %s"), $name), $url));
	}
}

function transactionBreadcrumbs($transactionID, $accountID)
{
	$date = stdGet("accountingTransaction", array("transactionID"=>$transactionID), "date");
	return array_merge(accountBreadcrumbs($accountID), crumbs(sprintf(_("Transaction on %s"), date("d-m-Y", $date)), "transaction.php?id=$transactionID&accountID=$accountID"));
}

function suppliersBreadcrumbs()
{
	return array_merge(accountingBreadcrumbs(), crumbs(_('Suppliers'), "suppliers.php"));
}

function supplierBreadcrumbs($supplierID)
{
	$name = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "name");
	return array_merge(suppliersBreadcrumbs(), crumbs(sprintf(_('Supplier %s'), $name), "supplier.php?id=$supplierID"));
}

function suppliersInvoiceBreadcrumbs($invoiceID)
{
	$invoice = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID", "invoiceNumber"));
	return array_merge(supplierBreadcrumbs($invoice["supplierID"]), crumbs(sprintf(_("Invoice %s"), $invoice["invoiceNumber"]), "supplierinvoice.php?id=$invoiceID"));
}

function suppliersPaymentBreadcrumbs($paymentID)
{
	$payment = stdGet("suppliersPayment", array("paymentID"=>$paymentID), array("supplierID", "transactionID"));
	$date = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), "date");
	return array_merge(supplierBreadcrumbs($payment["supplierID"]), crumbs(sprintf(_("Payment on %s"), date("d-m-Y", $date)), "supplierpayment.php?id=$paymentID"));
}

function fixedAssetBreadcrumbs($fixedAssetID)
{
	$name = stdGet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), "name");
	return array_merge(accountingBreadcrumbs(), crumbs(sprintf(_("Fixed asset %s"), $name), "fixedasset.php?id=$fixedAssetID"));
}

function balanceViewBreadcrumbs($balanceViewID)
{
	$name = stdGet("accountingBalanceView", array("balanceViewID"=>$balanceViewID), "name");
	return array_merge(accountingBreadcrumbs(), crumbs(sprintf(_("Balance %s"), $name), "balanceview.php?id=$balanceViewID"));
}

function incomeExpenseViewBreadcrumbs($incomeExpenseViewID)
{
	$name = stdGet("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$incomeExpenseViewID), "name");
	return array_merge(accountingBreadcrumbs(), crumbs(sprintf(_("Income / Expense view %s"), $name), "incomeexpenseview.php?id=$incomeExpenseViewID"));
}

function carBreadcrumbs($carID)
{
	$name = stdGet("accountingCar", array("carID"=>$carID), "name");
	return array_merge(accountingBreadcrumbs(), crumbs($name, "car.php?id=$carID"));
}

function accountSummary($accountID, $toDate = null, $fromDate = null, $balanceViewID = null, $incomeExpenseViewID = null)
{
	$account = stdGet("accountingAccount", array("accountID"=>$accountID), array("parentAccountID", "currencyID", "name", "description", "isDirectory"));
	$currency = stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), array("name", "symbol"));
	
	$extraFields = "";
	if($fromDate !== null) {
		$extraFields .= "&fromDate=$fromDate";
	}
	if($toDate !== null) {
		$extraFields .= "&toDate=$toDate";
	}
	if($balanceViewID !== null) {
		$extraFields .= "&balanceViewID=$balanceViewID";
	}
	if($incomeExpenseViewID !== null) {
		$extraFields .= "&incomeExpenseViewID=$incomeExpenseViewID";
	}
	
	$rows = array();
	if($account["parentAccountID"] !== null) {
		$parentAccountName = stdGet("accountingAccount", array("accountID"=>$account["parentAccountID"]), "name");
		$rows[_("Parent account")] = array("url"=>"account.php?id={$account["parentAccountID"]}{$extraFields}", "text"=>$parentAccountName);
	}
	$rows[_("Currency")] = array("html"=>$currency["name"] . " (" . $currency["symbol"] . ")");
	if($fromDate === null) {
		if($toDate !== null) {
			$rows[_("Date")] = date("d-m-Y", $toDate);
		}
		$balance  = accountingBalance($toDate);
		$rows[_("Balance")] = array("html"=>formatPrice($balance[$accountID], $currency["symbol"]));
	} else {
		$startBalance = accountingBalance($fromDate);
		$endBalance = accountingBalance($toDate);
		
		$rows[_("Date")] = sprintf(_('%1$s to %2$s'), date("d-m-Y", $fromDate), date("d-m-Y", $toDate));
		$rows[_("Start Balance")] = array("html"=>formatPrice($startBalance[$accountID], $currency["symbol"]));
		$rows[_("End Balance")] = array("html"=>formatPrice($endBalance[$accountID], $currency["symbol"]));
		$rows[_("Difference")] = array("html"=>formatPrice($endBalance[$accountID] - $startBalance[$accountID], $currency["symbol"]));
	}
	$rows[_("Description")] = $account["description"];
	
	return summaryTable(sprintf(_("Account %s"), $account["name"]), $rows, array("editLink"=>"editaccount.php?id=$accountID", "deleteLink"=>accountEmpty($accountID) ? "deleteaccount.php?id=$accountID" : null));
}

function accountList($accountID = null, $toDate = null, $fromDate = null, $balanceViewID = null, $incomeExpenseViewID = null)
{
	$rootNodes = stdList("accountingAccount", array("parentAccountID"=>null), "accountID", array("name"=>"ASC"));
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		$accountTree = accountingAccountTree($rootNode);
		$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
	}
	
	if($accountID === null) {
		$tree = array();
		foreach($accountList as $account) {
			if($account["parentAccountID"] === null) {
				$account["visibility"] = "VISIBLE";
			} else {
				$account["visibility"] = "COLLAPSED";
			}
			$tree[] = $account;
		}
	} else {
		$parents = array();
		$depth = null;
		$tree = array();
		foreach($accountList as $account) {
			if($depth === null) {
				$parents[$account["depth"]] = $account;
				if($account["accountID"] == $accountID) {
					for($i = 0; $i <= $account["depth"]; $i++) {
						$tree[] = $parents[$i];
					}
					$depth = $account["depth"];
				}
			} else {
				if($account["depth"] <= $depth) {
					break;
				}
				$account["visibility"] = "COLLAPSED";
				$tree[] = $account;
			}
		}
	}
	
	return doAccountList($tree, $toDate, $fromDate, $balanceViewID, $incomeExpenseViewID, $accountID !== null && !stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory") ? null : array("addNewLink"=>"addaccount.php" . ($accountID === null ? "" : "?id=$accountID")));
}

function balanceViewSummary($balanceViewID, $now)
{
	$balanceView = stdGet("accountingBalanceView", array("balanceViewID"=>$balanceViewID), array("name", "description", "dateBase", "dateOffsetType", "dateOffsetAmount"));
	$date = renderRelativeTime($balanceView["dateBase"], $balanceView["dateOffsetType"], $balanceView["dateOffsetAmount"], $now);
	
	return summaryTable(sprintf(_("Balance %s"), $balanceView["name"]), array(
		_("Name")=>$balanceView["name"],
		_("Description")=>$balanceView["description"],
		_("Date")=>date("d-m-Y", $date)
	), array("editLink"=>"editview.php?id=$balanceViewID&type=balance", "deleteLink"=>"deletebalanceview.php?id=$balanceViewID"));
}

function balanceViewList($balanceViewID, $now)
{
	$balanceView = stdGet("accountingBalanceView", array("balanceViewID"=>$balanceViewID), array("dateBase", "dateOffsetType", "dateOffsetAmount"));
	$date = renderRelativeTime($balanceView["dateBase"], $balanceView["dateOffsetType"], $balanceView["dateOffsetAmount"], $now);
	$visibilityMap = stdMap("accountingBalanceViewAccount", array("balanceViewID"=>$balanceViewID), "accountID", "visibility");
	
	$rootNodes = stdList("accountingAccount", array("parentAccountID"=>null), "accountID", array("name"=>"ASC"));
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		if(isset($visibilityMap[$rootNode]) && $visibilityMap[$rootNode] != "HIDDEN") {
			$accountTree = accountingAccountTree($rootNode, $visibilityMap);
			$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
		}
	}
	foreach($rootNodes as $rootNode) {
		if(!isset($visibilityMap[$rootNode]) || $visibilityMap[$rootNode] == "HIDDEN") {
			$accountTree = accountingAccountTree($rootNode, $visibilityMap);
			$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
		}
	}
	
	return doAccountList($accountList, $date, null, $balanceViewID);
}

function incomeExpenseViewSummary($incomeExpenseViewID, $now)
{
	$incomeExpenseView = stdGet("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$incomeExpenseViewID), array("name", "description", "startDateBase", "startDateOffsetType", "startDateOffsetAmount", "endDateBase", "endDateOffsetType", "endDateOffsetAmount"));
	$startDate = renderRelativeTime($incomeExpenseView["startDateBase"], $incomeExpenseView["startDateOffsetType"], $incomeExpenseView["startDateOffsetAmount"], $now);
	$endDate = renderRelativeTime($incomeExpenseView["endDateBase"], $incomeExpenseView["endDateOffsetType"], $incomeExpenseView["endDateOffsetAmount"], $now);
	
	return summaryTable(sprintf(_("Balance %s"), $incomeExpenseView["name"]), array(
		_("Name")=>$incomeExpenseView["name"],
		_("Description")=>$incomeExpenseView["description"],
		_("Start date")=>date("d-m-Y", $startDate),
		_("End date")=>date("d-m-Y", $endDate),
	), array("editLink"=>"editview.php?id=$incomeExpenseViewID&type=incomeexpence", "deleteLink"=>"deleteincomeexpenceview.php?id=$incomeExpenseViewID"));
}

function incomeExpenseViewList($incomeExpenseViewID, $now)
{
	$incomeExpenseView = stdGet("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$incomeExpenseViewID), array("startDateBase", "startDateOffsetType", "startDateOffsetAmount", "endDateBase", "endDateOffsetType", "endDateOffsetAmount"));
	$startDate = renderRelativeTime($incomeExpenseView["startDateBase"], $incomeExpenseView["startDateOffsetType"], $incomeExpenseView["startDateOffsetAmount"], $now);
	$endDate = renderRelativeTime($incomeExpenseView["endDateBase"], $incomeExpenseView["endDateOffsetType"], $incomeExpenseView["endDateOffsetAmount"], $now);
	$visibilityMap = stdMap("accountingIncomeExpenseViewAccount", array("incomeExpenseViewID"=>$incomeExpenseViewID), "accountID", "visibility");
	
	$rootNodes = stdList("accountingAccount", array("parentAccountID"=>null), "accountID", array("name"=>"ASC"));
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		if(isset($visibilityMap[$rootNode]) && $visibilityMap[$rootNode] != "HIDDEN") {
			$accountTree = accountingAccountTree($rootNode, $visibilityMap);
			$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
		}
	}
	foreach($rootNodes as $rootNode) {
		if(!isset($visibilityMap[$rootNode]) || $visibilityMap[$rootNode] == "HIDDEN") {
			$accountTree = accountingAccountTree($rootNode, $visibilityMap);
			$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
		}
	}
	
	return doAccountList($accountList, $endDate, $startDate, null, $incomeExpenseViewID);
}

function doAccountList($tree, $toDate, $fromDate, $balanceViewID = null, $incomeExpenseViewID = null, $properties = null)
{
	$toBalance = accountingBalance($toDate);
	if($fromDate !== null) {
		$fromBalance = accountingBalance($fromDate);
	} else {
		$fromBalance = null;
	}
	
	$rows = array();
	foreach($tree as $account) {
		$text = $account["name"];
		if($account["currencyID"] != $GLOBALS["defaultCurrencyID"]) {
			$text .= " (" . $account["currencyName"] . ")";
		}
		$type = accountingAccountType($account["accountID"]);
		$typeUrl = null;
		if($type["type"] == "CUSTOMER") {
			$typeUrl = "{$GLOBALS["rootHtml"]}billing/customer.php?id={$type["customerID"]}";
		}
		if($type["type"] == "SUPPLIER") {
			$typeUrl = "supplier.php?id={$type["supplierID"]}";
		}
		if($type["type"] == "FIXEDASSETVALUE" || $type["type"] == "FIXEDASSETDEPRICIATION") {
			$typeUrl = "fixedasset.php?id={$type["fixedAssetID"]}";
		}
		
		$currency = stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), "symbol");
		
		$extraFields = "";
		if($fromDate !== null) {
			$extraFields .= "&fromDate=$fromDate";
		}
		if($toDate !== null) {
			$extraFields .= "&toDate=$toDate";
		}
		if($balanceViewID !== null) {
			$extraFields .= "&balanceViewID=$balanceViewID";
		}
		if($incomeExpenseViewID !== null) {
			$extraFields .= "&incomeExpenseViewID=$incomeExpenseViewID";
		}
		
		$row = array();
		$row[] = array("html"=>"<a href=\"account.php?id={$account["accountID"]}$extraFields\">$text</a>" . ($typeUrl === null ? "" : "<a href=\"$typeUrl\" class=\"rightalign\"><img src=\"{$GLOBALS["rootHtml"]}css/images/external.png\" alt=\"Direct link\" /></a>"));
		if($fromBalance !== null) {
			$row[] = array("html"=>formatPrice($fromBalance[$account["accountID"]], $currency), "class"=>"nowrap");
		}
		$row[] = array("html"=>formatPrice($toBalance[$account["accountID"]], $currency), "class"=>"nowrap");
		if($fromBalance !== null) {
			$row[] = array("html"=>formatPrice($toBalance[$account["accountID"]] - $fromBalance[$account["accountID"]], $currency), "class"=>"nowrap");
		}
		
		$class = null;
		if($account["visibility"] == "HIDDEN") {
			$class = "hidden collapsed";
		} else if($account["visibility"] == "COLLAPSED") {
			$class = "collapsed";
		}
		if($account["parentID"] !== null) {
			$extraClass = "child-of-account-{$account["parentAccountID"]}";
			if($class === null) {
				$class = $extraClass;
			} else {
				$class .= " " . $extraClass;
			}
		}
		
		$rows[] = array("id"=>$account["id"], "class"=>$class, "cells"=>$row);
	}
	if($properties === null) {
		$properties = array();
	}
	$properties = array_merge($properties, array("divclass"=>"list tree"));
	if($fromBalance !== null) {
		return listTable(array(array("text"=>_("Account"), "class"=>"stretch"), array("text"=>_("Start Balance"), "class"=>"nowrap"), array("text"=>_("End Balance"), "class"=>"nowrap"), array("text"=>_("Difference"), "class"=>"nowrap")), $rows, _("Accounts "), true, $properties);
	} else {
		return listTable(array(_("Account"), _("Balance")), $rows, _("Accounts "), true, $properties);
	}
}

function transactionList($accountID, $toDate = null, $fromDate = null)
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
	$startBalance = $balance;
	
	foreach($transactions as $transaction) {
		$lines = stdList("accountingTransactionLine", array("transactionID"=>$transaction["transactionID"]), array("transactionLineID", "accountID", "amount"));
		
		$currentLineAmount = 0;
		foreach($lines as $line) {
			if(in_array($line["accountID"], $subAccounts)) {
				$currentLineAmount += $line["amount"];
			}
		}
		
		$balance += $currentLineAmount;
		
		if($fromDate !== null && $transaction["date"] < $fromDate) {
			$startBalance = $balance;
			continue;
		}
		if($toDate !== null && $transaction["date"] >= $toDate) {
			break;
		}
		
		$lines = array_reverse($lines);
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
		
		$rows[] = array("id"=>"transaction-{$transaction["transactionID"]}", "class"=>"transaction collapsed", "cells"=>array(
			array("text"=>date("d-m-Y", $transaction["date"])),
			array("html"=>($transaction["description"] == "" ? "<i>" . _("None") . "</i>" : htmlentities($transaction["description"])), "url"=>"transaction.php?id={$transaction["transactionID"]}&accountID={$accountID}"),
			array("html"=>formatPrice($currentLineAmount, $currencySymbol)),
			array("html"=>formatPrice($balance, $currencySymbol)),
		));
	}
	if($fromDate !== null) {
		$rows = array_merge(array(array("class"=>"transaction", "cells"=>array(
			array("text"=>date("d-m-Y", $fromDate)),
			array("text"=>sprintf(_("Value on %s"), date("d-m-Y", $fromDate))),
			array("text"=>""),
			array("html"=>formatPrice($startBalance, $currencySymbol)),
		))), $rows);
	}
	$rows = array_reverse($rows);
	return listTable(array(_("Date"), _("Description"), _("Amount"), _("Balance")), $rows, null, true, "list tree");
}

function transactionSummary($transactionID)
{
	$transaction = stdGet("accountingTransaction", array("transactionID"=>$transactionID), array("date", "description"));
	$lines = stdList("accountingTransactionLine", array("transactionID"=>$transactionID), array("transactionLineID", "accountID", "amount"));
	
	$rows[] = array("id"=>"transaction-{$transactionID}", "class"=>"transaction", "cells"=>array(
		array("text"=>date("d-m-Y", $transaction["date"])),
		array("html"=>($transaction["description"] == "" ? "<i>" . _("None") . "</i>" : htmlentities($transaction["description"]))),
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
	return listTable(array(_("Date"), _("Description"), _("Amount")), $rows, null, true, "list");
}

function supplierList()
{
	$suppliers = stdList("suppliersSupplier", array(), array("supplierID", "accountID", "name", "description"), array("name"=>"ASC"));
	
	$rows = array();
	foreach($suppliers as $supplier) {
		$rows[] = array("cells"=>array(
			array("url"=>"supplier.php?id={$supplier["supplierID"]}", "text"=>$supplier["name"]),
			array("text"=>$supplier["description"]),
			array("url"=>"account.php?id={$supplier["accountID"]}", "html"=>accountingFormatAccountPrice($supplier["accountID"])),
		));
	}
	return listTable(array(_("Name"), _("Description"), _("Balance")), $rows, _("Suppliers"), true, array("divclass"=>"list sortable", "addNewLink"=>"addsupplier.php"));
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
		$rows[_("Currency")] = array("html"=>$currency["name"] . " (" . $currency["symbol"] . ")");
	}
	$rows[_("Balance")] = array("url"=>"account.php?id={$supplier["accountID"]}", "html"=>accountingFormatAccountPrice($supplier["accountID"]));
	if($supplier["defaultExpenseAccountID"] !== null) {
		$rows[_("Default expences account")] = array("url"=>"account.php?id={$supplier["defaultExpenseAccountID"]}", "text"=>$defaultExpenseAccountName);
	} else {
		$rows[_("Default expences account")] = array("html"=>"<i>" . _("None") . "</i>");
	}
	$rows[_("Description")] = $supplier["description"];
	
	return summaryTable(sprintf(_("Supplier %s"), $supplier["name"]), $rows, array("editLink"=>"editsupplier.php?id=$supplierID", "deleteLink"=>supplierEmpty($supplierID) ? "deletesupplier.php?id=$supplierID" : null));
}

function supplierInvoiceSummary($invoiceID)
{
	$invoice = stdGet("suppliersInvoice", array("invoiceID"=>$invoiceID), array("supplierID", "transactionID", "invoiceNumber"));
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$invoice["supplierID"]), array("accountID", "name"));
	$transaction = stdGet("accountingTransaction", array("transactionID"=>$invoice["transactionID"]), array("date", "description"));
	
	$dateHtml = date("d-m-Y", $transaction["date"]);
	$hasPdf = !stdExists("suppliersInvoice", array("invoiceID"=>$invoiceID, "pdf"=>null));
	
	$amountHtml = accountingCalculateTransactionAmount($invoice["transactionID"], $supplier["accountID"], true);
	
	$fields = array(
		_("Supplier")=>array("url"=>"supplier.php?id={$invoice["supplierID"]}", "text"=>$supplier["name"]),
		_("Invoice number")=>array("text"=>$invoice["invoiceNumber"]),
		_("Pdf")=>array("url"=>$hasPdf ? "supplierinvoicepdf.php?id={$invoiceID}" : null, "text"=>$hasPdf ? _("Yes") : _("No")),
		_("Date")=>array("text"=>$dateHtml),
		_("Description")=>array("text"=>$transaction["description"]),
		_("Amount")=>array("url"=>"transaction.php?id={$invoice["transactionID"]}", "html"=>$amountHtml),
	);
	
	return summaryTable(sprintf(_("Invoice %s"), $invoice["invoiceNumber"]), $fields);
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
		$amountHtml = accountingCalculateTransactionAmount($invoice["transactionID"], $accountID, true);
		$rows[] = array("cells"=>array(
			array("url"=>"supplierinvoice.php?id=" . $invoice["invoiceID"], "text"=>date("d-m-Y", $transaction["date"])),
			array("url"=>"transaction.php?id={$invoice["transactionID"]}", "html"=>$amountHtml),
			array("url"=>$hasPdf ? "supplierinvoicepdf.php?id={$invoice["invoiceID"]}" : null, "text"=>$invoice["invoiceNumber"]),
			array("text"=>$transaction["description"]),
		));
	}
	return listTable(array(_("Date"), _("Amount"), _("Invoice number"), _("Description")), $rows, _("Invoices"), true, "list sortable");
}

function supplierPaymentSummary($paymentID)
{
	$payment = stdGet("suppliersPayment", array("paymentID"=>$paymentID), array("supplierID", "transactionID"));
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$payment["supplierID"]), array("accountID", "name", "description"));
	$transaction = stdGet("accountingTransaction", array("transactionID"=>$payment["transactionID"]), array("date", "description"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "currencyID");
	$currencySymbol = stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	
	$dateHtml = date("d-m-Y", $transaction["date"]);
	$amountHtml = accountingCalculateTransactionAmount($payment["transactionID"], $supplier["accountID"]);
	
	$fields = array(
		_("Supplier")=>array("url"=>"supplier.php?id={$payment["supplierID"]}", "text"=>$supplier["name"]),
		_("Amount")=>array("url"=>"transaction.php?id={$payment["transactionID"]}", "html"=>$amountHtml),
		_("Date")=>array("text"=>$dateHtml),
		_("Description")=>array("text"=>$transaction["description"]),
	);
	
	return summaryTable(sprintf(_("Payment on %s"), $dateHtml), $fields);
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
		$amountHtml = accountingCalculateTransactionAmount($payment["transactionID"], $accountID);
		$rows[] = array("cells"=>array(
			array("url"=>"supplierpayment.php?id=" . $payment["paymentID"], "text"=>date("d-m-Y", $transaction["date"])),
			array("url"=>"transaction.php?id={$payment["transactionID"]}", "html"=>$amountHtml),
			array("text"=>$transaction["description"]),
		));
	}
	return listTable(array(_("Date"), _("Amount"), _("Description")), $rows, _("Payments"), true, "list sortable");
}

function fixedAssetList()
{
	$rows = array();
	foreach(stdList("accountingFixedAsset", array(), array("fixedAssetID", "accountID", "name", "description", "nextDepreciationDate")) as $asset) {
		$rows[] = array("cells"=>array(
			array("url"=>"fixedasset.php?id={$asset["fixedAssetID"]}", "text"=>$asset["name"]),
			array("text"=>$asset["description"]),
			array("url"=>"account.php?id={$asset["accountID"]}", "html"=>formatPrice(stdGet("accountingAccount", array("accountID"=>$asset["accountID"]), "balance"))),
			array("text"=>date("d-m-Y", $asset["nextDepreciationDate"])),
		));
	}
	
	return listTable(array(_("Name"), _("Description"), _("Value"), _("Next depreciation date")), $rows, _("Fixed assets"), _("No fixed assets."), array("divclass"=>"list sortable", "addNewLink"=>"addfixedasset.php"));
}

function carList()
{
	$rows = array();
	foreach(stdList("accountingCar", array(), array("carID", "name", "description")) as $car) {
		$rows[] = array("cells"=>array(
			array("url"=>"car.php?id={$car["carID"]}", "text"=>$car["name"]),
			array("text"=>$car["description"]),
		));
	}
	
	return listTable(array(_("Name"), _("Description")), $rows, _("Cars"), _("No cars."), array("divclass"=>"list sortable"));
}

function fixedAssetSummary($fixedAssetID)
{
	$asset = stdGet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("accountID", "depreciationAccountID", "name", "description", "purchaseDate", "depreciationFrequencyBase", "depreciationFrequencyMultiplier", "nextDepreciationDate", "totalDepreciations", "performedDepreciations", "residualValuePercentage", "automaticDepreciation"));
	
	$frequencyBase = strtolower($asset["depreciationFrequencyBase"]);
	$nameHtml = htmlentities($asset["name"]);
	
	$currentValue = stdGet("accountingAccount", array("accountID"=>$asset["accountID"]), "balance");
	$depreciatedValue = stdGet("accountingAccount", array("accountID"=>$asset["depreciationAccountID"]), "balance");
	
	return summaryTable(sprintf(_("Fixed asset %s"), $nameHtml), array(
		_("Name")=>array("text"=>$asset["name"]),
		_("Description")=>array("text"=>$asset["description"]),
		_("Current value")=>array("html"=>formatPrice($currentValue), "url"=>"account.php?id={$asset["accountID"]}"),
		_("Depreciated value")=>array("html"=>formatPrice($depreciatedValue), "url"=>"account.php?id={$asset["depreciationAccountID"]}"),
		_("Purchase value")=>array("html"=>formatPrice($currentValue + $depreciatedValue)),
		_("Purchase date")=>array("text"=>date("d-m-Y", $asset["purchaseDate"])),
		_("Depreciation interval")=>array("text"=>"per {$asset["depreciationFrequencyMultiplier"]} {$frequencyBase}s"),
		_("Depreciations")=>array("text"=>"{$asset["performedDepreciations"]} / {$asset["totalDepreciations"]}"),
		_("Residual value")=>array("text"=>"{$asset["residualValuePercentage"]}%"),
		_("Automatic depreciation")=>array("text"=>$asset["automaticDepreciation"] ? _("Yes") : _("No"))
		));
}

function viewList()
{
	$now = time();
	$rows = array();
	foreach(stdList("accountingBalanceView", array(), array("balanceViewID", "name", "dateBase", "dateOffsetType", "dateOffsetAmount")) as $view) {
		$date = renderRelativeTime($view["dateBase"], $view["dateOffsetType"], $view["dateOffsetAmount"], $now);
		$rows[] = array("cells"=>array(
			array("url"=>"balanceview.php?id={$view["balanceViewID"]}", "text"=>$view["name"]),
			array("text"=>date("d-m-Y", $date)),
		));
	}
	foreach(stdList("accountingIncomeExpenseView", array(), array("incomeExpenseViewID", "name", "startDateBase", "startDateOffsetType", "startDateOffsetAmount", "endDateBase", "endDateOffsetType", "endDateOffsetAmount")) as $view) {
		$startDate = renderRelativeTime($view["startDateBase"], $view["startDateOffsetType"], $view["startDateOffsetAmount"], $now);
		$endDate = renderRelativeTime($view["endDateBase"], $view["endDateOffsetType"], $view["endDateOffsetAmount"], $now);
		$rows[] = array("cells"=>array(
			array("url"=>"incomeexpenseview.php?id={$view["incomeExpenseViewID"]}", "text"=>$view["name"]),
			array("text"=> sprintf(_('%1$s to %2$s'), date("d-m-Y", $startDate), date("d-m-Y", $endDate))),
		));
	}
	return listTable(array(_("Name"), _("Date")), $rows, _("Views"), null, array("divclass"=>"list sortable", "addNewLink"=>"addview.php"));
}

function addAccountForm($accountID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("addaccount.php?id=$accountID", "", _("Add account"), _("Add Account"), array(), array());
	}
	
	return operationForm("addaccount.php?id=$accountID", $error, _("Add account"), _("Add Account"),
		array(
			array("title"=>_("Name"), "type"=>"text", "name"=>"name"),
			array("title"=>_("Currency"), "type"=>"dropdown", "name"=>"currencyID", "options"=>currencyOptions()),
			array("title"=>_("Type"), "type"=>"radio", "name"=>"type", "options"=>array(
				array("label"=>_("Account"), "value"=>"account"),
				array("label"=>_("Directory"), "value"=>"directory")
			)),
			array("title"=>_("Description"), "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function editAccountForm($accountID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("editaccount.php?id=$accountID", "", _("Edit account"), _("Edit account"), array(), array());
	}
	
	if($values === null || $error === "") {
		$values = stdGet("accountingAccount", array("accountID"=>$accountID), array("name", "description"));
	}
	
	return operationForm("editaccount.php?id=$accountID", $error, _("Edit account"), _("Save"),
		array(
			array("title"=>_("Name"), "type"=>"text", "name"=>"name"),
			array("title"=>_("Description"), "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function moveAccountForm($accountID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("moveaccount.php?id=$accountID", "", _("Move account"), _("Move account"), array(), array());
	}
	
	$rootNodes = stdList("accountingAccount", array("parentAccountID"=>null), "accountID", array("name"=>"ASC"));
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		if($rootNode == $accountID) {
			continue;
		}
		$accountTree = accountingAccountTree($rootNode);
		$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
	}
	
	$disabledDepth = null;
	$options = array();
	$options[] = array("label"=>_("Top-level Account"), "value"=>"0");
	foreach($accountList as $account) {
		if($disabledDepth !== null) {
			if($account["depth"] <= $disabledDepth) {
				$disabledDepth = null;
			}
		} else if($account["accountID"] == $accountID) {
			$disabledDepth = $account["depth"];
		}
		
		if($disabledDepth !== null) {
			continue;
		}
		
		if($account["isDirectory"]) {
			$options[] = array("label"=>str_repeat("&nbsp;&nbsp;&nbsp;", $account["depth"] + 1) . $account["name"], "value"=>$account["accountID"]);
		}
	}
	
	if($values === null || $error === "") {
		$values = array("parentAccountID"=>stdGet("accountingAccount", array("accountID"=>$accountID), "parentAccountID"));
	}
	
	return operationForm("moveaccount.php?id=$accountID", $error, _("Move account"), _("Save"), 
		array(
			array("title"=>_("Move To"), "type"=>"dropdown", "name"=>"parentAccountID", "options"=>$options)
		),
		$values);
}

function deleteAccountForm($accountID, $error = "", $values = null)
{
	return operationForm("deleteaccount.php?id=$accountID", $error, _("Delete account"), _("Delete"), array(), $values);
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
	$fields[] = array("title"=>_("Description"), "type"=>"text", "name"=>"description");
	$fields[] = array("title"=>_("Date"), "type"=>"text", "name"=>"date");
	
	$message = array();
	if($error === null && $balance !== null) {
		if($balance["type"] == "double") {
			$fields = array_merge($fields, transactionExchangeRates($balance));
		} else if($balance["type"] == "multiple") {
			$message["custom"] = "<p class=\"warning\">" . _("Warning, the correctness of this transaction cannot be checked because there are 3 or more currencies involved!") . "</p>";
		}
	}
	
	$fields[] = array("type"=>"array", "field"=>array("title"=>_("Account"), "type"=>"colspan", "columns"=>array(
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
	
	return operationForm("addtransaction.php?id=$accountID", $error, _("New transaction"), _("Save"), $formContent["fields"], $formContent["values"], $formContent["message"]);
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
	
	$type = accountingTransactionType($transactionID);
	if($type["type"] != "NONE") {
		if(!isset($formContent["message"]["custom"])) {
			$formContent["message"]["custom"] = "";
		}
		$formContent["message"]["custom"] .= "<p class=\"warning\">" . _("Warning, this transaction is part of a ");
		if($type["type"] == "CUSTOMERINVOICE") {
			$customerID = stdGet("billingInvoice", array("invoiceID"=>$type["invoiceID"]), "customerID");
			$formContent["message"]["custom"] .= "<a href=\"{$GLOBALS["rootHtml"]}billing/customer.php?id={$customerID}\">" . _("customer invoice") . "</a>";
		}
		if($type["type"] == "CUSTOMERPAYMENT") {
			$formContent["message"]["custom"] .= "<a href=\"{$GLOBALS["rootHtml"]}billing/payment.php?id={$type["paymentID"]}\">" . _("customer payment") . "</a>";
		}
		if($type["type"] == "SUPPLIERINVOICE") {
			$formContent["message"]["custom"] .= "<a href=\"supplierinvoice.php?id={$type["invoiceID"]}\">" . _("supplier invoice") . "</a>";
		}
		if($type["type"] == "SUPPLIERPAYMENT") {
			$formContent["message"]["custom"] .= "<a href=\"supplierpayment.php?id={$type["paymentID"]}\">" . _("supplier payment") . "</a>";
		}
		$formContent["message"]["custom"] .= ".</p>";
	}
	
	return operationForm("edittransaction.php?id=$transactionID&accountID=$accountID", $error, _("Edit transaction"), _("Save"), $formContent["fields"], $formContent["values"], $formContent["message"]);
}

function deleteTransactionForm($transactionID, $accountID, $error = "", $values = null)
{
	return operationForm("deletetransaction.php?id=$transactionID&accountID=$accountID", $error, _("Delete transaction"), _("Delete"), array(), $values);
}

function addSupplierForm($error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("addsupplier.php", $error, _("Add supplier"), _("Add Supplier"), array(), array());
	}
	
	return operationForm("addsupplier.php", $error, _("Add supplier"), _("Add Supplier"),
		array(
			array("title"=>_("Name"), "type"=>"text", "name"=>"name"),
			array("title"=>_("Currency"), "type"=>"dropdown", "name"=>"currencyID", "options"=>currencyOptions()),
			array("title"=>_("Default expense account"), "type"=>"dropdown", "name"=>"defaultExpenseAccountID", "options"=>accountingAccountOptions($GLOBALS["expensesDirectoryAccountID"], true)),
			array("title"=>_("Description"), "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function editSupplierForm($supplierID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("editsupplier.php?id=$supplierID", $error, _("Edit supplier"), _("Edit supplier"), array(), array());
	}
	
	if($values === null || $error === "") {
		$values = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), array("name", "defaultExpenseAccountID", "description"));
	}
	
	return operationForm("editsupplier.php?id=$supplierID", $error, _("Edit supplier"), _("Save"),
		array(
			array("title"=>_("Name"), "type"=>"text", "name"=>"name"),
			array("title"=>_("Default expense account"), "type"=>"dropdown", "name"=>"defaultExpenseAccountID", "options"=>accountingAccountOptions($GLOBALS["expensesDirectoryAccountID"], true)),
			array("title"=>_("Description"), "type"=>"textarea", "name"=>"description")
		),
		$values);
}

function deleteSupplierForm($supplierID, $error = "", $values = null)
{
	return operationForm("deletesupplier.php?id=$supplierID", $error, _("Delete supplier"), _("Delete"), array(), $values);
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
	$fields[] = array("title"=>_("Invoice number"), "type"=>"text", "name"=>"invoiceNumber");
	$fields[] = array("title"=>_("Description"), "type"=>"text", "name"=>"description");
	$fields[] = array("title"=>_("Date"), "type"=>"text", "name"=>"date");
	
	$subforms = array();
	if($fileLink !== null) {
		$fileLinkHtml = htmlentities($fileLink);
		$subforms[] = array("value"=>"current", "label"=>sprintf(_('Keep %1$scurrent file%2$s'), "<a href=\"$fileLinkHtml\">", "</a>"), "subform"=>array());
	}
	$subforms[] = array("value"=>"none", "label"=>_("None"), "subform"=>array());
	$subforms[] = array("value"=>"new", "label"=>_("Upload new file"), "subform"=>array(
		array("title"=>_("File"), "type"=>"file", "name"=>"file", "accept"=>"application/pdf")
	));
	$fields[] = array("title"=>_("Pdf"), "type"=>"subformchooser", "name"=>"pdfType", "subforms"=>$subforms);
	
	$bankAccounts = accountingAccountOptions($GLOBALS["bankDirectoryAccountID"]);
	$subforms = array();
	$subforms[] = array("value"=>"no", "label"=>_("No payment"), "subform"=>array());
	$subforms[] = array("value"=>"yes", "label"=>_("Direct payment"), "subform"=>array(
		array("title"=>_("Bank account"), "type"=>"dropdown", "name"=>"paymentBankAccount", "options"=>$bankAccounts),
		array("title"=>_("Payment date"), "type"=>"text", "name"=>"paymentDate"),
	));
	$fields[] = array("title"=>_("Payment"), "type"=>"subformchooser", "name"=>"payment", "subforms"=>$subforms);
	
	if($currencyID != $GLOBALS["defaultCurrencyID"]) {
		$fields[] = array("title"=>sprintf(_("Total in %s"), $currency["name"]), "type"=>"text", "name"=>"foreignAmount");
	}
	if($error === null && $total !== null) {
		$totalHtml = formatPriceRaw($total, $defaultCurrency["symbol"]);
		$fields[] = array("title"=>"Total in {$defaultCurrency["name"]}", "type"=>"html", "html"=>"<input type=\"text\" value=\"$totalHtml\" readonly=\"readonly\" />");
		if($currencyID != $GLOBALS["defaultCurrencyID"]) {
			$fields = array_merge($fields, transactionExchangeRates($balance));
		}
	}
	
	
	$fields[] = array("title"=>_("Tax"), "type"=>"colspan", "columns"=>array(
		array("type"=>"html", "html"=>""),
		array("type"=>"html", "html"=>htmlentities($defaultCurrency["name"])),
		array("type"=>"text", "name"=>"taxAmount", "fill"=>true)
	));
	$fields[] = array("type"=>"array", "field"=>array("title"=>_("Line"), "type"=>"colspan", "columns"=>array(
		array("type"=>"dropdown", "name"=>"accountID", "options"=>accountingAccountOptions(array($GLOBALS["expensesDirectoryAccountID"], $GLOBALS["assetsDirectoryAccountID"]), true)),
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
		$values["payment"] = "no";
		$values["date"] = date("d-m-Y");
		if(isset($GLOBALS["bankDefaultAccountID"])) {
			$values["paymentBankAccount"] = $GLOBALS["bankDefaultAccountID"];
		}
		
		$defaultExpenseAccountID = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), "defaultExpenseAccountID");
		if($defaultExpenseAccountID !== null) {
			$values["accountID-0"] = $defaultExpenseAccountID;
		}
	}
	
	$formContent = supplierInvoiceForm($supplierID, null, $error, $values, $total, $balance);
	
	return operationForm("addsupplierinvoice.php?id=$supplierID", $error, _("Add invoice"), _("Add invoice"), $formContent["fields"], $formContent["values"]);
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
	
	return operationForm("editsupplierinvoice.php?id=$invoiceID", $error, _("Edit invoice"), _("Save"), $formContent["fields"], $formContent["values"]);
}

function deleteSupplierInvoiceForm($invoiceID, $error = "", $values = null)
{
	return operationForm("deletesupplierinvoice.php?id=$invoiceID", $error, _("Delete invoice"), _("Delete"), array(), $values);
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
		$fields[] = array("title"=>sprintf(_("Amount (%s)"), $defaultCurrency), "type"=>"text", "name"=>"amount");
		$fields[] = array("title"=>sprintf(_("Amount (%s)"), $strangeCurrency), "type"=>"text", "name"=>"foreignAmount");
		
		if($error === null && $balance !== null) {
			$fields = array_merge($fields, transactionExchangeRates($balance));
		}
	} else {
		$fields[] = array("title"=>_("Amount"), "type"=>"text", "name"=>"amount");
	}
	$fields[] = array("title"=>_("Date"), "type"=>"text", "name"=>"date");
	$fields[] = array("title"=>_("Description"), "type"=>"text", "name"=>"description");
	$fields[] = array("title"=>_("Bank account"), "type"=>"dropdown", "name"=>"bankAccount", "options"=>$bankAccounts);
	return $fields;
}

function addSupplierPaymentForm($supplierID, $error = "", $values = null, $balance = null)
{
	$supplier = stdGet("suppliersSupplier", array("supplierID"=>$supplierID), array("accountID", "name"));
	$currencyID = stdGet("accountingAccount", array("accountID"=>$supplier["accountID"]), "currencyID");
	
	if($values === null) {
		$values = array("date"=>date("d-m-Y"), "description"=>"Payment for supplier {$supplier["name"]}");
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
	
	return operationForm("addsupplierpayment.php?id=$supplierID", $error, _("Add payment"), _("Save"), $fields, $values);
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
	
	return operationForm("editsupplierpayment.php?id=$paymentID", $error, _("Edit payment"), _("Save"), $fields, $values);
}

function deleteSupplierPaymentForm($paymentID, $error = "", $values = null)
{
	return operationForm("deletesupplierpayment.php?id=$paymentID", $error, _("Delete payment"), _("Delete"), array(), $values);
}

function addFixedAssetForm($error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("addfixedasset.php", $error, _("Add fixed asset"), _("Add fixed asset"), array(), array());
	}
	if($values === null || count($values) == 0) {
		$values = array(
			"depreciationFrequencyMultiplier"=>1,
			"depreciationFrequencyBase"=>"YEAR",
		);
	}
	
	return operationForm("addfixedasset.php", $error, _("Add fixed asset"), _("Add"), array(
		array("title"=>_("Name"), "type"=>"text", "name"=>"name"),
		array("title"=>_("Description"), "type"=>"text", "name"=>"description"),
		array("title"=>_("Purchase date"), "type"=>"text", "name"=>"purchaseDate"),
		array("title"=>_("Depreciation interval"), "type"=>"colspan", "columns"=>array(
			array("type"=>"html", "html"=>_("per")),
			array("type"=>"text", "name"=>"depreciationFrequencyMultiplier", "fill"=>true),
			array("type"=>"dropdown", "name"=>"depreciationFrequencyBase", "options"=>dropdown(array("DAY"=>_("days"), "MONTH"=>_("months"), "YEAR"=>_("years"))))
		)),
		array("title"=>_("Depreciation terms"), "type"=>"text", "name"=>"depreciationTerms"),
		array("title"=>_("Residual value percentage"), "type"=>"text", "name"=>"residualValue"),
	), $values);
}

function editFixedAssetForm($fixedAssetID, $error = "", $values = null)
{
	if($values === null) {
		$values = stdGet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("name", "description", "automaticDepreciation"));
		if(!$values["automaticDepreciation"]) {
			unset($values["automaticDepreciation"]);
		}
	}
	
	return operationForm("editfixedasset.php?id=$fixedAssetID", $error, _("Edit fixed asset"), _("Save"), array(
		array("title"=>_("Name"), "type"=>"text", "name"=>"name"),
		array("title"=>_("Description"), "type"=>"text", "name"=>"description"),
		array("title"=>_("Automatic depreciation"), "type"=>"checkbox", "name"=>"automaticDepreciation", "label"=>_("Enable automatic depreciation")),
	), $values);
}

function deleteFixedAssetForm($fixedAssetID, $error = "", $values = null)
{
	return operationForm("deletefixedasset.php?id=$fixedAssetID", $error, _("Delete fixed asset"), _("Delete"), array(), $values);
}

function depreciateFixedAssetForm($fixedAssetID, $error = "", $values = null)
{
	if($values === null) {
		$values = array("until"=>date("d-m-Y"));
	}
	
	return operationForm("depreciatefixedasset.php?id=$fixedAssetID", $error, _("Depreciate fixed asset"), _("Depreciate"), array(
		array("title"=>_("Until"), "type"=>"text", "name"=>"until")
	), $values);
}

function recomputeBalancesForm($error = "", $values = null)
{
	return operationForm("recomputebalances.php", $error, _("Recompute balances"), _("Recompute"), array(), $values);
}

function relativeTimeChooser($title, $namePrefix)
{
	return array("title"=>"$title", "type"=>"subformchooser", "name"=>"{$namePrefix}DateType", "subforms"=>array(
		array("value"=>"ABSOLUTE", "label"=>_("Absolute date"), "subform"=>array(
			array("title"=>_("Absolute date"), "type"=>"date", "name"=>"{$namePrefix}AbsDate"),
		)),
		array("value"=>"RELATIVE", "label"=>_("Relative date"), "subform"=>array(
			array("title"=>_("Relative date"), "type"=>"colspan", "columns"=>array(
				array("type"=>"dropdown", "name"=>"{$namePrefix}Base", "options"=>array(
					array("label"=>_("Now"), "value"=>"NOW"),
					array("label"=>_("Start of month"), "value"=>"STARTMONTH"),
					array("label"=>_("Start of quarter"), "value"=>"STARTQUARTER"),
					array("label"=>_("Start of year"), "value"=>"STARTYEAR"),
				)),
				array("type"=>"html", "html"=>"+"),
				array("type"=>"text", "name"=>"{$namePrefix}OffsetAmount", "fill"=>true),
				array("type"=>"dropdown", "name"=>"{$namePrefix}OffsetType", "options"=>array(
					array("label"=>_("Seconds"), "value"=>"SECONDS"),
					array("label"=>_("Days"), "value"=>"DAYS"),
					array("label"=>_("Months"), "value"=>"MONTHS"),
					array("label"=>_("Years"), "value"=>"YEARS"),
				)),
			)),
		)),
	));
}

function viewForm()
{
	$rootNodes = stdList("accountingAccount", array("parentAccountID"=>null), "accountID", array("name"=>"ASC"));
	$accountList = array();
	foreach($rootNodes as $rootNode) {
		$accountTree = accountingAccountTree($rootNode);
		$accountList = array_merge($accountList, accountingFlattenAccountTree($accountTree));
	}
	
	$rows = array();
	foreach($accountList as $account) {
		if(!$account["isDirectory"]) {
			continue;
		}
		$options = array();
		if($account["parentID"] !== null) {
			$options[] = array("label"=>"", "value"=>"INHERIT");
		}
		$options[] = array("label"=>_("Visible"), "value"=>"VISIBLE");
		$options[] = array("label"=>_("Collapsed"), "value"=>"COLLAPSED");
		$options[] = array("label"=>_("Hidden"), "value"=>"HIDDEN");
		$rows[] = array("type"=>"colspan", "rowid"=>"view-{$account["id"]}", "rowclass"=>($account["parentID"] === null ? null : "child-of-view-{$account["parentID"]} ") . (isset($values[$account["id"]]) && $values[$account["id"]] == "VISIBLE" ? "" : "collapsed"), "columns"=>array(
			array("type"=>"html", "html"=>$account["name"], "fill"=>true),
			array("type"=>"dropdown", "name"=>$account["id"], "options"=>$options),
		));
	}

	return array(
		array("title"=>_("Name"), "type"=>"text", "name"=>"name"),
		array("title"=>_("Description"), "type"=>"text", "name"=>"description"),
		array("caption"=>_("Accounts "), "type"=>"table", "tableclass"=>"list tree", "subform"=>$rows),
		array("title"=>_("Type"), "type"=>"typechooser", "options"=>array(
			array("title"=>_("Balance view"), "submitcaption"=>_("Create balance view"), "name"=>"balance", "subform"=>array(
				relativeTimeChooser(_("Date"), "balance")
			)),
			array("title"=>_("Income / expences view"), "submitcaption"=>_("Create income / expences view"), "name"=>"incomeexpences", "subform"=>array(
				relativeTimeChooser(_("Start date"), "incomeExpencesStart"),
				relativeTimeChooser(_("End date"), "incomeExpencesEnd"),
			)),
		)),
	);
}

function addViewForm($error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("addview.php", "", _("Add view"), _("Add view"), array(), array());
	}
	if($values === null || count($values) == 0) {
		$values = array();
		$values["balanceOffsetAmount"] = 0;
		$values["incomeExpencesStartOffsetAmount"] = 0;
		$values["incomeExpencesEndOffsetAmount"] = 0;
	}
	$form = viewForm();
	return operationForm("addview.php", $error, _("Add view"), _("Save"), $form, $values);
}

function editBalanceViewForm($balanceViewID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("editview.php?id=$balanceViewID&type=balance", "", _("Edit view"), _("Edit view"), array(), array());
	}
	if($values === null || count($values) == 0) {
		$values = array();
		$view = stdGet("accountingBalanceView", array("balanceViewID"=>$balanceViewID), array("name", "description", "dateBase", "dateOffsetType", "dateOffsetAmount"));
		$values["name"] = $view["name"];
		$values["description"] = $view["description"];
		if($view["dateBase"] == "ABSOLUTE") {
			$values["balanceDateType"] = "ABSOLUTE";
			$values["balanceAbsDate"] = date("d-m-Y", $view["dateOffsetAmount"]);
			$values["balanceOffsetAmount"] = 0;
		} else {
			$values["balanceDateType"] = "RELATIVE";
			$values["balanceBase"] = $view["dateBase"];
			$values["balanceOffsetType"] = $view["dateOffsetType"];
			$values["balanceOffsetAmount"] = $view["dateOffsetAmount"];
		}
		$values["incomeExpencesStartOffsetAmount"] = 0;
		$values["incomeExpencesEndOffsetAmount"] = 0;
		
		$accounts = stdMap("accountingBalanceViewAccount", array("balanceViewID"=>$balanceViewID), "accountID", "visibility");
		foreach($accounts as $accountID=>$visibility) {
			$values["account-$accountID"] = $visibility;
		}
	}
	$form = viewForm();
	return operationForm("editview.php?id=$balanceViewID&type=balance", $error, _("Edit view"), _("Save"), $form, $values);
}

function editIncomeExpenseViewForm($incomeExpenseViewID, $error = "", $values = null)
{
	if($error == "STUB") {
		return operationForm("editview.php?id=$incomeExpenseViewID&type=incomeexpence", "", _("Edit view"), _("Edit view"), array(), array());
	}
	if($values === null || count($values) == 0) {
		$values = array();
		$view = stdGet("accountingIncomeExpenseView", array("incomeExpenseViewID"=>$incomeExpenseViewID), array("name", "description", "startDateBase", "startDateOffsetType", "startDateOffsetAmount", "endDateBase", "endDateOffsetType", "endDateOffsetAmount"));
		$values["name"] = $view["name"];
		$values["description"] = $view["description"];
		if($view["startDateBase"] == "ABSOLUTE") {
			$values["incomeExpencesStartDateType"] = "ABSOLUTE";
			$values["incomeExpencesStartAbsDate"] = date("d-m-Y", $view["startDateOffsetAmount"]);
			$values["incomeExpencesStartOffsetAmount"] = 0;
		} else {
			$values["incomeExpencesStartDateType"] = "RELATIVE";
			$values["incomeExpencesStartBase"] = $view["startDateBase"];
			$values["incomeExpencesStartOffsetType"] = $view["startDateOffsetType"];
			$values["incomeExpencesStartOffsetAmount"] = $view["startDateOffsetAmount"];
		}
		if($view["endDateBase"] == "ABSOLUTE") {
			$values["incomeExpencesEndDateType"] = "ABSOLUTE";
			$values["incomeExpencesStartAbsDate"] = date("d-m-Y", $view["endDateOffsetAmount"]);
			$values["incomeExpencesEndOffsetAmount"] = 0;
		} else {
			$values["incomeExpencesEndDateType"] = "RELATIVE";
			$values["incomeExpencesEndBase"] = $view["endDateBase"];
			$values["incomeExpencesEndOffsetType"] = $view["endDateOffsetType"];
			$values["incomeExpencesEndOffsetAmount"] = $view["endDateOffsetAmount"];
		}
		$values["balanceOffsetAmount"] = 0;
		
		$accounts = stdMap("accountingIncomeExpenseViewAccount", array("incomeExpenseViewID"=>$incomeExpenseViewID), "accountID", "visibility");
		foreach($accounts as $accountID=>$visibility) {
			$values["account-$accountID"] = $visibility;
		}
	}
	$form = viewForm();
	return operationForm("editview.php?id=$incomeExpenseViewID&type=incomeexpence", $error, _("Edit view"), _("Save"), $form, $values);
}

function deleteBalanceViewForm($viewID, $error = "", $values = null)
{
	return operationForm("deletebalanceview.php?id=$viewID", $error, _("Delete view"), _("Delete"), array(), $values);
}

function deleteIncomeExpenceViewForm($viewID, $error = "", $values = null)
{
	return operationForm("deleteincomeexpenceview.php?id=$viewID", $error, _("Delete view"), _("Delete"), array(), $values);
}

function addTravelExpencesForm($carID, $error = "", $values = null, $balance = null)
{
	if($values === null || $error === "") {
		$values = array();
		$values["date"] = date("d-m-Y");
		$values["accountID"] = stdGet("accountingCar", array("carID"=>$carID), "defaultBankAccountID");
	}
	
	$bankAccounts = accountingAccountOptions($GLOBALS["bankDirectoryAccountID"]);
	
	$fields = array();
	
	$message = array();
	if($error === null) {
		$km = $values["endKm"] - $values["startKm"];
		if($km > 500) {
			$message["custom"] = "<p class=\"warning\">" . _("Warning, this trip is more than 500 km, are you sure it is correct?") . "</p>";
		}
	}
	$fields[] = array("title"=>_("Date"), "type"=>"date", "name"=>"date");
	$fields[] = array("title"=>_("Start Km"), "type"=>"text", "name"=>"startKm");
	$fields[] = array("title"=>_("End Km"), "type"=>"text", "name"=>"endKm");
	$fields[] = array("title"=>_("Occasion"), "type"=>"text", "name"=>"occasion");
	$fields[] = array("title"=>_("Destination"), "type"=>"text", "name"=>"destination");
	$fields[] = array("title"=>_("Bank account"), "type"=>"dropdown", "name"=>"accountID", "options"=>$bankAccounts);
	
	if($error === null) {
		$fields[] = array("title"=>_("Trip length"), "type"=>"colspan", "columns"=>array(
			array("type"=>"html", "cellclass"=>"nowrap", "html"=>$km . " km")
		));
	}
	
	return operationForm("addtravelexpences.php?id=$carID", $error, _("Add travel expences"), _("Save"), $fields, $values, $message);
}

function transactionExchangeRates($balance)
{
	$currency1 = stdGet("accountingCurrency", array("currencyID"=>$balance["rates"][0]["from"]), array("name", "symbol"));
	$currency2 = stdGet("accountingCurrency", array("currencyID"=>$balance["rates"][0]["to"]), array("name", "symbol"));
	
	$rates1 = array("title"=>_("Exchange rate"), "type"=>"colspan", "columns"=>array(
		array("type"=>"html", "cellclass"=>"nowrap", "html"=>formatPrice(100, $currency1["symbol"]) . " (" . $currency1["name"] . ")"),
		array("type"=>"html", "fill"=>true, "html"=>"<input type=\"text\" readonly=\"readonly\" value=\"" . formatPrice($balance["rates"][0]["rate"], $currency2["symbol"]) . " (" . $currency2["name"] . ")\" />")
	));
	$rates2 = array("title"=>_("Exchange rate"), "type"=>"colspan", "columns"=>array(
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

function supplierAccountDescription($name)
{
	return sprintf(_("Supplier account for supplier %s"), $name);
}

function depreciationAccountDescription($name)
{
	return sprintf(_("Depreciation account for fixed asset %s"), $name);
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

function fixedAssetEmpty($fixedAssetID)
{
	$fixedAsset = stdGet("accountingFixedAsset", array("fixedAssetID"=>$fixedAssetID), array("accountID", "depreciationAccountID"));
	return
		accountEmpty($fixedAsset["accountID"]) &&
		accountEmpty($fixedAsset["depreciationAccountID"]);
}

function parseRelativeTime($values, $namePrefix)
{
	if(!isset($values[$namePrefix . "DateType"])) {
		return null;
	}
	if(!in_array($values[$namePrefix . "DateType"], array("ABSOLUTE", "RELATIVE"))) {
		return null;
	}
	if($values[$namePrefix . "DateType"] == "ABSOLUTE") {
		if(!isset($values[$namePrefix . "AbsDate"])) {
			return null;
		}
		$date = parseDate($values[$namePrefix . "AbsDate"]);
		if($date === null) {
			return null;
		}
		return array(
			"base"=>"ABSOLUTE",
			"offsetType"=>"SECONDS",
			"offsetAmount"=>$date,
		);
	} else {
		if(!isset($values[$namePrefix . "Base"])) {
			return null;
		}
		if(!in_array($values[$namePrefix . "Base"], array("NOW", "STARTMONTH", "STARTQUARTER", "STARTYEAR"))) {
			return null;
		}
		if(!isset($values[$namePrefix . "OffsetAmount"])) {
			return null;
		}
		$offsetAmount = parseInt($values[$namePrefix . "OffsetAmount"]);
		if($offsetAmount === null) {
			return null;
		}
		if(!isset($values[$namePrefix . "OffsetType"])) {
			return null;
		}
		if(!in_array($values[$namePrefix . "OffsetType"], array("SECONDS", "DAYS", "MONTHS", "YEARS"))) {
			return null;
		}
		return array(
			"base"=>$values[$namePrefix . "Base"],
			"offsetType"=>$values[$namePrefix . "OffsetType"],
			"offsetAmount"=>$offsetAmount,
		);
	}
}

function renderRelativeTime($base, $offsetType, $offsetAmount, $now = null)
{
	if($now === null) {
		$now = time();
	}
	if($base == "NOW") {
		$baseTime = $now;
	} else if($base == "STARTMONTH") {
		$year = date("Y", $now);
		$month = date("m", $now);
		$baseTime = mktime(0, 0, 0, $month, 1, $year);
	} else if($base == "STARTQUARTER") {
		$year = date("Y", $now);
		$month = date("m", $now);
		$baseTime = mktime(0, 0, 0, $month - (($month - 1) % 3), 1, $year);
	} else if($base == "STARTYEAR") {
		$year = date("Y", $now);
		$baseTime = mktime(0, 0, 0, 1, 1, $year);
	} else if($base == "ABSOLUTE") {
		$baseTime = 0;
	}
	
	$year = date("Y", $baseTime);
	$month = date("m", $baseTime);
	$day = date("j", $baseTime);
	$hour = date("H", $baseTime);
	$minute = date("i", $baseTime);
	$second = date("s", $baseTime);
	
	if($offsetType == "SECONDS") {
		$second += $offsetAmount;
	} else if($offsetType == "DAYS") {
		$day += $offsetAmount;
	} else if($offsetType == "MONTHS") {
		$month += $offsetAmount;
	} else if($offsetType == "YEARS") {
		$year += $offsetAmount;
	}
	
	return mktime($hour, $minute, $second, $month, $day, $year);
}

?>