<?php

$accountingTitle = "Accounting";
$accountingTarget = "admin";

function accountingAddAccount($parentAccountID, $currencyID, $name, $description, $isDirectory)
{
	if($parentAccountID !== null && stdGet("accountingAccount", array("accountID"=>$parentAccountID), "isDirectory") != 1) {
		throw new AssertionError();
	}
	
	return stdNew("accountingAccount", array("parentAccountID"=>$parentAccountID, "currencyID"=>$currencyID, "name"=>$name, "description"=>$description, "isDirectory"=>($isDirectory ? 1 : 0), "balance"=>0));
}

function accountingEditAccount($accountID, $name, $description)
{
	stdSet("accountingAccount", array("accountID"=>$accountID), array("name"=>$name, "description"=>$description));
}

function accountingMoveAccount($accountID, $parentAccountID)
{
	if($parentAccountID !== null && stdGet("accountingAccount", array("accountID"=>$parentAccountID), "isDirectory") != 1) {
		throw new AssertionError();
	}
	
	startTransaction();
	$balance = stdGet("accountingAccount", array("accountID"=>$accountID), "balance");
	$oldParentAccountID = stdGet("accountingAccount", array("accountID"=>$accountID), "parentAccountID");
	accountingAddBalance($oldParentAccountID, -$balance);
	stdSet("accountingAccount", array("accountID"=>$accountID), array("parentAccountID"=>$parentAccountID));
	accountingAddBalance($parentAccountID, $balance);
	commitTransaction();
}

function accountingDeleteAccount($accountID)
{
	stdDel("accountingAccount", array("accountID"=>$accountID));
}

function accountingAddBalance($accountID, $amount)
{
	if($accountID === null) {
		return;
	}
	startTransaction();
	$currencyID = stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	while($accountID !== null) {
		$account = stdGet("accountingAccount", array("accountID"=>$accountID), array("parentAccountID", "currencyID"));
		if($account["currencyID"] != $currencyID) {
			break;
		}
		
		$amountSql = dbAddSlashes($amount);
		$accountIDSql = dbAddSlashes($accountID);
		setQuery("UPDATE accountingAccount SET balance = balance + '$amountSql' WHERE accountID = '$accountIDSql'");
		
		$accountID = $account["parentAccountID"];
	}
	commitTransaction();
}

function accountingSetTransactionLines($transactionID, $lines)
{
	startTransaction();
	foreach(stdList("accountingTransactionLine", array("transactionID"=>$transactionID), array("transactionLineID", "accountID", "amount")) as $line) {
		accountingAddBalance($line["accountID"], -$line["amount"]);
	}
	stdDel("accountingTransactionLine", array("transactionID"=>$transactionID));
	foreach($lines as $line) {
		accountingAddBalance($line["accountID"], $line["amount"]);
		stdNew("accountingTransactionLine", array("transactionID"=>$transactionID, "accountID"=>$line["accountID"], "amount"=>$line["amount"]));
	}
	commitTransaction();
}

function accountingAddTransaction($date, $description, $lines)
{
	startTransaction();
	$transactionID = stdNew("accountingTransaction", array("date"=>$date, "description"=>$description));
	accountingSetTransactionLines($transactionID, $lines);
	commitTransaction();
	return $transactionID;
}

function accountingEditTransaction($transactionID, $date, $description, $lines)
{
	startTransaction();
	stdSet("accountingTransaction", array("transactionID"=>$transactionID), array("date"=>$date, "description"=>$description));
	accountingSetTransactionLines($transactionID, $lines);
	commitTransaction();
}

function accountingDeleteTransaction($transactionID)
{
	startTransaction();
	accountingSetTransactionLines($transactionID, array());
	stdDel("accountingTransaction", array("transactionID"=>$transactionID));
	commitTransaction();
}

function accountingTransactionBalance($lines)
{
	$valutaTotal = array();
	$currencies = array();
	foreach($lines as $line) {
		$currencyID = stdGet("accountingAccount", array("accountID"=>$line["accountID"]), "currencyID");
		if(!isset($valutaTotal[$currencyID])) {
			$valutaTotal[$currencyID] = 0;
			$currencies[] = $currencyID;
		}
		$valutaTotal[$currencyID] += $line["amount"];
	}
	if(count($valutaTotal) == 0) {
		return false;
	} else if(count($valutaTotal) == 1) {
		return array("type"=>"single", "status"=>($valutaTotal[$currencies[0]] == 0));
	} else if(count($valutaTotal) == 2) {
		return array("type"=>"double", "rates"=>array(
			array("from"=>$currencies[0], "to"=>$currencies[1], "rate"=>$valutaTotal[$currencies[1]] / $valutaTotal[$currencies[0]] * -100),
			array("from"=>$currencies[1], "to"=>$currencies[0], "rate"=>$valutaTotal[$currencies[0]] / $valutaTotal[$currencies[1]] * -100),
		));
	} else {
		return array("type"=>"multiple");
	}
}

function accountingFsck()
{
	$accounts = query("SELECT account.accountID AS accountID, account.balance AS balance, SUM(transactionLine.amount) AS sum FROM accountingAccount AS account LEFT JOIN accountingTransactionLine AS transactionLine USING(accountID) WHERE account.isDirectory = 0 GROUP BY account.accountID, account.balance")->fetchList();
	foreach($accounts as $account) {
		if($account["sum"] === null) {
			$account["sum"] = 0;
		}
		if($account["balance"] != $account["sum"]) {
			throw new AssertionError();
		}
	}
	
	$accounts = query("SELECT account.accountID AS accountID, account.balance AS balance, SUM(subaccount.balance) AS sum FROM accountingAccount AS account LEFT JOIN accountingAccount AS subaccount ON account.accountID = subaccount.parentAccountID AND account.currencyID = subaccount.currencyID WHERE account.isDirectory = 1 GROUP BY account.accountID, account.balance")->fetchList();
	foreach($accounts as $account) {
		if($account["sum"] === null) {
			$account["sum"] = 0;
		}
		if($account["balance"] != $account["sum"]) {
			throw new AssertionError();
		}
	}
	
	$transactions = query("SELECT transactionID, SUM(transactionLine.amount) AS sum FROM accountingTransaction AS transaction INNER JOIN accountingTransactionLine AS transactionLine USING(transactionID) INNER JOIN accountingAccount AS account USING(accountID) GROUP BY transactionID HAVING COUNT(DISTINCT account.currencyID) = 1")->fetchList();
	foreach($transactions as $transaction) {
		if($transaction["sum"] != 0) {
			throw new AssertionError();
		}
	}
}

/// TODO: weggooien!
accountingFsck();
