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
		$rows[] = array("id"=>$account["id"], "class"=>($account["parentID"] === null ? null : "child-of-account-{$account["parentAccountID"]}"), "cells"=>array(
			array("url"=>"account.php?id={$account["accountID"]}", "text"=>$account["name"]),
			array("html"=>formatPrice($account["balance"])),
		));
	}
	return listTable(array("Rekening", "Saldo"), $rows, null, true, "list tree");
}

function accountTree($accountID)
{
	$accountIDSql = $GLOBALS["database"]->addSlashes($accountID);
	$output = $GLOBALS["database"]->query("SELECT accountID, parentAccountID, accountingAccount.name AS name, description, isDirectory, balance, accountingCurrency.symbol AS currency FROM accountingAccount INNER JOIN accountingCurrency USING(currencyID) WHERE accountID = '$accountIDSql'")->fetchArray();
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
		return $a["date"] - $b["date"];
	});
	$rows = array();
	$balance = 0;
	$subAccounts = subAccountList($accountID);
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
			array("html"=>formatPrice($currentLineAmount)),
			array("html"=>formatPrice($balance)),
		));
		
		foreach($lines as $line) {
			$accountName = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$line["accountID"]), "name");
			$rows[] = array("id"=>"transactionline-" . $line["transactionLineID"], "class"=>"child-of-transaction-{$transaction["transactionID"]} transactionline", "cells"=>array(
				array("text"=>""),
				array("url"=>"account.php?id={$line["accountID"]}", "text"=>$accountName),
				array("html"=>formatPrice($line["amount"])),
				array("text"=>""),
			));
		}
	}
	return listTable(array("Datum", "Beschrijving", "Bedrag", "Balans"), $rows, null, true, "list tree");
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

function subAccountList($accountID)
{
	$output = array($accountID);
	$subAccounts = $GLOBALS["database"]->stdList("accountingAccount", array("parentAccountID"=>$accountID), "accountID");
	foreach($subAccounts as $subAccount) {
		$output = array_merge($output, subAccountList($subAccount));
	}
	return $output;
}

?>