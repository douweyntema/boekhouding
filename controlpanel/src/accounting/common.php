<?php

require_once(dirname(__FILE__) . "/../common.php");

function doAccounting()
{
	useComponent("accounting");
	$GLOBALS["menuComponent"] = "accounting";
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
	return crumbs("Boekhouding", "");
}

function accountBreadcrumbs($accountID)
{
	$name = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "name");
	return array_merge(accountingBreadcrumbs(), crumbs("Rekening " . $name, "account.php?id=$accountID"));
}


function accountList()
{
	$rootNodes = $GLOBALS["database"]->stdList("accountingAccount", array("parentAccountID"=>null), "accountID");
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
			array("html"=>formatPrice($account["balance"], $account["currencySymbol"])),
		));
	}
	return listTable(array("Rekening", "Saldo"), $rows, null, true, "list tree");
}

function accountTree($accountID)
{
	$accountIDSql = $GLOBALS["database"]->addSlashes($accountID);
	$output = $GLOBALS["database"]->query("SELECT accountID, parentAccountID, accountingAccount.name AS name, description, isDirectory, balance, accountingCurrency.symbol AS currencySymbol, accountingCurrency.name AS currencyName FROM accountingAccount INNER JOIN accountingCurrency USING(currencyID) WHERE accountID = '$accountIDSql'")->fetchArray();
	$output["subaccounts"] = array();
	foreach($GLOBALS["database"]->stdList("accountingAccount", array("parentAccountID"=>$accountID), "accountID") as $subAccountID) {
		$output["subaccounts"][] = accountTree($subAccountID);
	}
	return $output;
}

function flattenAccountTree($tree, $parentID = null)
{
	$id = "account-" . $tree["accountID"];
	$output = array();
	
	$output[] = array_merge($tree, array("id"=>$id, "parentID"=>$parentID));
	foreach($tree["subaccounts"] as $account) {
		$output = array_merge($output, flattenAccountTree($account, $id));
	}
	return $output;
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
	
	$currencyID = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	$currencySymbol = $GLOBALS["database"]->stdGet("accountingCurrency", array("currencyID"=>$currencyID), "symbol");
	
	foreach($transactions as $transaction) {
		$lines = $GLOBALS["database"]->stdList("accountingTransactionLine", array("transactionID"=>$transaction["transactionID"]), array("transactionLineID", "accountID", "amount"));
		
		$currentLineAmount = 0;
		foreach($lines as $line) {
			if(in_array($line["accountID"], $subAccounts)) {
				$currentLineAmount += $line["amount"];
			}
		}
		
		$balance += $currentLineAmount;
		
		$rows[] = array("id"=>"transaction-{$transaction["transactionID"]}", "class"=>"transaction", "cells"=>array(
			array("text"=>date("d-m-Y", $transaction["date"])),
			array("text"=>$transaction["description"]),
			array("html"=>formatPrice($currentLineAmount, $currencySymbol)),
			array("html"=>formatPrice($balance, $currencySymbol)),
		));
		
		foreach($lines as $line) {
			$account = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$line["accountID"]), array("name", "currencyID"));
			$lineCurrencySymbol = $GLOBALS["database"]->stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), "symbol");
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

function transactions($accountID)
{
	if($GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory") == 0) {
		return $GLOBALS["database"]->query("SELECT transactionID, date, description FROM accountingTransaction INNER JOIN accountingTransactionLine USING(transactionID) WHERE accountID = '" . $GLOBALS["database"]->addSlashes($accountID) . "'")->fetchMap("transactionID");
	} else {
		$output = array();
		$subAccounts = $GLOBALS["database"]->stdList("accountingAccount", array("parentAccountID"=>$accountID), "accountID");
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
		$currencyID = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	}
	$subAccounts = $GLOBALS["database"]->stdList("accountingAccount", array("parentAccountID"=>$accountID, "currencyID"=>$currencyID), "accountID");
	foreach($subAccounts as $subAccount) {
		$output = array_merge($output, subAccountList($subAccount, $currencyID));
	}
	return $output;
}

function addTransactionForm($accountID, $error = "", $values = null, $balance = null)
{
	if($values === null) {
		$values = array("date"=>date("d-m-Y"), "accountID-0"=>$accountID);
	}
	if(isset($values["date"])) {
		$values["date"] = date("d-m-Y", parseDate($values["date"]));
	}
	$lines = parseArrayField($_POST, array("accountID", "amount"));
	foreach($lines as $line) {
		if(isset($values["amount-" . $line[""]])) {
			$values["amount-" . $line[""]] = formatPriceRaw(parsePrice($values["amount-" . $line[""]]));
		}
	}
	
	$accounts = $GLOBALS["database"]->stdList("accountingAccount", array("isDirectory"=>0), array("accountID", "name", "currencyID"));
	$accountOptions = array(array("label"=>"", "value"=>""));
	foreach($accounts as $account) {
		$currency = $GLOBALS["database"]->stdGet("accountingCurrency", array("currencyID"=>$account["currencyID"]), "name");
		$accountOptions[] = array("label"=>$account["name"] . " ("  . $currency . ")", "value"=>$account["accountID"]);
	}
	
	$message = array();
	$rates1 = null;
	$rates2 = null;
	if($error === null && $balance !== null) {
		if($balance["type"] == "double") {
			$currency1 = $GLOBALS["database"]->stdGet("accountingCurrency", array("currencyID"=>$balance["rates"][0]["from"]), array("name", "symbol"));
			$currency2 = $GLOBALS["database"]->stdGet("accountingCurrency", array("currencyID"=>$balance["rates"][0]["to"]), array("name", "symbol"));
			
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
	
	return operationForm("addtransaction.php?id=$accountID", $error, "New transaction", "Save",
		array(
			array("title"=>"Description", "type"=>"text", "name"=>"description"),
			array("title"=>"Date", "type"=>"text", "name"=>"date"),
			$rates1,
			$rates2,
			array("type"=>"array", "field"=>array("title"=>"Account", "type"=>"colspan", "columns"=>array(
				array("type"=>"dropdown", "name"=>"accountID", "options"=>$accountOptions),
				array("type"=>"text", "name"=>"amount", "fill"=>true),
			))),
		),
		$values, $message);
}

?>