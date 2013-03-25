<?php

$accountingTitle = "Boekhouding";
$accountingTarget = "admin";

function accountingAddAccount($parentAccountID, $currencyID, $name, $description, $isDirectory)
{
	if($parentAccountID !== null && !$GLOBALS["database"]->stdExists("accountingAccount", array("accountID"=>$parentAccountID))) {
		throw new AssertionError();
	}
	
	return $GLOBALS["database"]->stdNew("accountingAccount", array("parentAccountID"=>$parentAccountID, "currencyID"=>$currencyID, "name"=>$name, "description"=>$description, "isDirectory"=>($isDirectory ? 1 : 0), "balance"=>0));
}

function accountingEditAccount($accountID, $name, $description)
{
	$GLOBALS["database"]->stdSet("accountingAccount", array("accountID"=>$accountID), array("name"=>$name, "description"=>$description));
}

function accountingMoveAccount($accountID, $parentAccountID)
{
	if($parentAccountID !== null && $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$parentAccountID)) != 1) {
		throw new AssertionError();
	}
	
	$GLOBALS["database"]->startTransaction();
	$balance = $GLOBALS["database"]->stdGet("accountingAccount", array("account"=>$accountID), "balance");
	$oldParentAccountID = $GLOBALS["database"]->stdGet("accountingAccount", array("account"=>$accountID), "parentAccountID");
	accountingAddBalance($oldParentAccountID, -$balance);
	$GLOBALS["database"]->stdSet("accountingAccount", array("account"=>$accountID), array("parentAccountID"=>$parentAccountID));
	accountingAddBalance($parentAccountID, $balance);
	$GLOBALS["database"]->commitTransaction();
}

function accountingDeleteAccount($accountID)
{
	$GLOBALS["database"]->stdDelete("accountingAccount", array("accountID"=>$accountID));
}

function accountingAddBalance($accountID, $amount)
{
	$GLOBALS["database"]->startTransaction();
	$currencyID = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "currencyID");
	while($accountID !== null) {
		$account = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), array("parentAccountID", "currencyID"));
		if($account["currencyID"] != $currencyID) {
			break;
		}
		
		$amountSql = $GLOBALS["database"]->addSlashes($amount);
		$accountIDSql = $GLOBALS["database"]->addSlashes($accountID);
		$GLOBALS["database"]->setQuery("UPDATE accountingAccount SET balance = balance + '$amountSql' WHERE accountID = '$accountIDSql'");
		
		$accountID = $account["parentAccountID"];
	}
	$GLOBALS["database"]->commitTransaction();
}

function accountingSetTransactionLines($transactionID, $lines)
{
	$GLOBALS["database"]->startTransaction();
	foreach($GLOBALS["database"]->stdList("accountingTransactionLine", array("transactionID"=>$transactionID), array("transactionLineID", "accountID", "amount")) as $line) {
		accountingAddBalance($line["accountID"], -$line["amount"]);
	}
	$GLOBALS["database"]->stdDel("accountingTransactionLine", array("transactionID"=>$transactionID));
	foreach($lines as $line) {
		accountingAddBalance($line["accountID"], $line["amount"]);
		$GLOBALS["database"]->stdNew("accountingTransactionLine", array("transactionID"=>$transactionID, "accountID"=>$line["accountID"], "amount"=>$line["amount"]));
	}
	$GLOBALS["database"]->commitTransaction();
}

function accountingAddTransaction($date, $description, $lines)
{
	$GLOBALS["database"]->startTransaction();
	$transactionID = $GLOBALS["database"]->stdNew("accountingTransaction", array("date"=>$date, "description"=>$description));
	accountingSetTransactionLines($transactionID, $lines);
	$GLOBALS["database"]->commitTransaction();
	return $transactionID;
}

function accountingEditTransaction($transactionID, $date, $description, $lines)
{
	$GLOBALS["database"]->startTransaction();
	$GLOBALS["database"]->stdSet("accountingTransaction", array("transactionID"=>$transactionID), array("date"=>$date, "description"=>$description));
	accountingSetTransactionLines($transactionID, $lines);
	$GLOBALS["database"]->commitTransaction();
}

function accountingDeleteTransaction($transactionID)
{
	$GLOBALS["database"]->startTransaction();
	accountingSetTransactionLines($transactionID, array());
	$GLOBALS["database"]->stdDel("accountingTransaction", array("transactionID"=>$transactionID));
	$GLOBALS["database"]->commitTransaction();
}

function accountingFsck()
{
	$accounts = $GLOBALS["database"]->query("SELECT account.accountID AS accountID, account.balance AS balance, SUM(transactionLine.amount) AS sum FROM accountingAccount AS account LEFT JOIN accountingTransactionLine AS transactionLine USING(accountID) WHERE account.isDirectory = 0 GROUP BY account.accountID, account.balance")->fetchList();
	foreach($accounts as $account) {
		if($account["sum"] === null) {
			$account["sum"] = 0;
		}
		if($account["balance"] != $account["sum"]) {
			throw new AssertionError();
		}
	}
	
	$accounts = $GLOBALS["database"]->query("SELECT account.accountID AS accountID, account.balance AS balance, SUM(subaccount.balance) AS sum FROM accountingAccount AS account LEFT JOIN accountingAccount AS subaccount ON account.accountID = subaccount.parentAccountID AND account.currencyID = subaccount.currencyID WHERE account.isDirectory = 1 GROUP BY account.accountID, account.balance")->fetchList();
	foreach($accounts as $account) {
		if($account["sum"] === null) {
			$account["sum"] = 0;
		}
		if($account["balance"] != $account["sum"]) {
			throw new AssertionError();
		}
	}
	
	$transactions = $GLOBALS["database"]->query("SELECT transactionID, SUM(transactionLine.amount) AS sum FROM accountingTransaction AS transaction INNER JOIN accountingTransactionLine AS transactionLine USING(transactionID) INNER JOIN accountingAccount AS account USING(accountID) GROUP BY transactionID HAVING COUNT(DISTINCT account.currencyID) = 1")->fetchList();
	foreach($transactions as $transaction) {
		if($transaction["sum"] != 0) {
			throw new AssertionError();
		}
	}
}

accountingFsck();
