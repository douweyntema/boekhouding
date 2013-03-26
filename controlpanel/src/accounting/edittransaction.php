<?php

require_once("common.php");

function main()
{
	$transactionID = get("id");
	$accountID = get("accountID");
	
	doAccountingTransaction($transactionID);
	
	$check = function($condition, $error, $balance = null) use($transactionID, $accountID) {
		$date = stdGet("accountingTransaction", array("transactionID"=>$transactionID), "date");
		$content = makeHeader("Transaction on " . date("d-m-Y", $date), transactionBreadcrumbs($transactionID, $accountID));
		if(!$condition) die(page(makeHeader("Transaction on " . date("d-m-Y", $date), transactionBreadcrumbs($transactionID, $accountID), crumbs("Edit transaction", "edittransaction.php?id=$transactionID&accountID=$accountID")) . editTransactionForm($transactionID, $accountID, $error, $_POST, $balance)));
	};
	
	$check(($description = post("description")) != "", "No description given.");
	$check(($date = parseDate(post("date"))) !== null, "Invalid date.");
	
	$lines = parseArrayField($_POST, array("accountID", "amount"));
	
	$parsedLines = array();
	foreach($lines as $line) {
		$amount = parsePrice($line["amount"]);
		if($line["accountID"] == "" && $amount != 0) {
			$check(false, "No account selected.");
		}
		if($amount == 0) {
			continue;
		}
		$parsedLines[] = array("accountID"=>$line["accountID"], "amount"=>$amount);
	}
	$balance = accountingTransactionBalance($parsedLines);
	$check($balance !== false, "No valid transaction lines.");
	if($balance["type"] == "single") {
		$check($balance["status"], "Transaction not in balance");
	}
	$check(post("confirm") !== null, null, $balance);
	
	accountingEditTransaction($transactionID, $date, $description, $parsedLines);
	
	if($accountID !== null) {
		redirect("accounting/account.php?id=$accountID");
	} else {
		redirect("accounting/index.php");
	}
}

main();

?>