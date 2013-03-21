<?php

$accountingTitle = "Boekhouding";
$accountingTarget = "admin";

function accountingAddAccount($parentAccountID, $name, $description, $isDirectory, $currency)
{
	if($parentAccountID !== null && $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$parentAccountID)) != 1) {
		throw new AssertionError();
	}
	
	return $GLOBALS["database"]->stdNew("accountingAccount", array("parentAccountID"=>$parentAccountID, "name"=>$name, "description"=>$description, "isDirectory"=>($isDirectory ? 1 : 0), "currency"=>$currency, "balance"=>0));
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
	while($accountID !== null) {
		$amountSql = $GLOBALS["database"]->addSlashes($line["amount"]);
		$accountIDSql = $GLOBALS["database"]->addSlashes($accountID);
		$GLOBALS["database"]->setQuery("UPDATE accountingAccount SET balance = balance + '$amountSql' WHERE accountID = '$accountIDSql'");
		$accountID = $GLOBALS["database"]->stdGet("accountingAccount", array("accountID"=>$accountID), "parentAccountID");
	}
	$GLOBALS["database"]->commitTransaction();
}

function accountingAddTransaction($date, $description, $lines)
{
	$GLOBALS["database"]->startTransaction();
	$transactionID = $GLOBALS["database"]->stdNew("accountingTransaction", array("date"=>$date, "description"=>$description));
	foreach($lines as $line) {
		$accountID = $line["accountID"];
		if(stdGet("accountingAccount", array("accountID"=>$accountID), "isDirectory") != 0) {
			throw new AssertionError();
		}
		
		$GLOBALS["database"]->stdNew("accountTransactionLine", array("transactionID"=>$transactionID, "accountID"=>$accountID, "amount"=>$line["amount"]));
		accountingAddBalance($accountID, $line["accountID"]);
	}
	$GLOBALS["database"]->commitTransaction();
	return $transactionID;
}
