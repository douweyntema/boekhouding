<?php

require_once("common.php");

function main()
{
	$transactionID = get("id");
	$accountID = get("accountID");
	
	doAccountingTransaction($transactionID);
	
	$check = function($condition, $error, $balance = null, $message = null) use($transactionID, $accountID) {
		$date = stdGet("accountingTransaction", array("transactionID"=>$transactionID), "date");
		$content = makeHeader(sprintf(_("Transaction on %s"), date("d-m-Y", $date)), transactionBreadcrumbs($transactionID, $accountID));
		if(!$condition) die(page(makeHeader(sprintf(_("Transaction on %s"), date("d-m-Y", $date)), transactionBreadcrumbs($transactionID, $accountID), crumbs(_("Edit transaction"), "edittransaction.php?id=$transactionID&accountID=$accountID")) . editTransactionForm($transactionID, $accountID, $error, $_POST, $balance)));
	};
	
	$check(($description = post("description")) != "", _("No description given."));
	$check(($date = parseDate(post("date"))) !== null, _("Invalid date."));
	
	$lines = parseArrayField($_POST, array("accountID", "amount"));
	
	$parsedLines = array();
	foreach($lines as $line) {
		$amount = parsePrice($line["amount"]);
		if($line["accountID"] == "" && $amount != 0) {
			$check(false, _("No account selected."));
		}
		if($amount == 0) {
			continue;
		}
		$parsedLines[] = array("accountID"=>$line["accountID"], "amount"=>$amount);
	}
	$balance = accountingTransactionBalance($parsedLines);
	$check($balance !== false, _("No valid transaction lines."));
	if($balance["type"] == "single") {
		$check($balance["status"], _("Transaction not in balance"));
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